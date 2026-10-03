param(
    [ValidateSet('up', 'down', 'status', 'logs', 'verify')]
    [string] $Action = 'up'
)

$ErrorActionPreference = 'Stop'

$root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$composeFile = Join-Path $root 'docker-compose.staging.yml'
$marketplaceEnv = Join-Path $root '.env.staging.docker.local'
$mlEnv = Join-Path $root 'ml-service\.env.staging.docker.local'

function New-HexSecret {
    param([int] $Bytes = 32)

    $buffer = New-Object byte[] $Bytes
    $generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()

    try {
        $generator.GetBytes($buffer)
    } finally {
        $generator.Dispose()
    }

    return -join ($buffer | ForEach-Object { $_.ToString('x2') })
}

function New-AppKey {
    $buffer = New-Object byte[] 32
    $generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()

    try {
        $generator.GetBytes($buffer)
    } finally {
        $generator.Dispose()
    }

    return 'base64:' + [Convert]::ToBase64String($buffer)
}

function Read-EnvironmentValue {
    param(
        [string] $Path,
        [string] $Name
    )

    $line = Get-Content -LiteralPath $Path |
        Where-Object { $_ -match ('^' + [regex]::Escape($Name) + '=') } |
        Select-Object -First 1

    if (-not $line) {
        throw "No se encontro $Name en $Path."
    }

    return ($line -split '=', 2)[1].Trim('"')
}

function Initialize-StagingEnvironment {
    if (-not (Test-Path -LiteralPath $marketplaceEnv)) {
        $appKey = New-AppKey
        $databasePassword = New-HexSecret 32
        $databaseRootPassword = New-HexSecret 40
        $redisPassword = New-HexSecret 32
        $meiliKey = New-HexSecret 32
        $mlToken = New-HexSecret 32
        $mlWebhookSecret = New-HexSecret 32

        $content = @"
APP_NAME="Atlantia Supermarket Staging"
APP_ENV=staging
APP_DEBUG=false
APP_KEY=$appKey
APP_URL=http://127.0.0.1:8180
APP_TIMEZONE=America/Guatemala
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
LOG_CHANNEL=operations
LOG_LEVEL=info
ATLANTIA_LOG_STACK=daily,stderr
ATLANTIA_INCIDENT_CHANNELS=stderr
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=atlantia_supermarket_staging
DB_USERNAME=atlantia_staging
ATLANTIA_DB_PASSWORD=$databasePassword
MYSQL_DATABASE=atlantia_supermarket_staging
MYSQL_USER=atlantia_staging
MYSQL_PASSWORD=$databasePassword
MYSQL_ROOT_PASSWORD=$databaseRootPassword
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_CONNECTION=session
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=false
REDIS_CLIENT=phpredis
REDIS_SCHEME=tcp
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=$redisPassword
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3
REDIS_CACHE_CONNECTION=cache
REDIS_QUEUE_CONNECTION=queue
REDIS_SESSION_CONNECTION=session
SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=false
SCOUT_AFTER_COMMIT=true
SCOUT_PREFIX=atlantia_staging_
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=$meiliKey
FILESYSTEM_DISK=local
PRIVATE_FILESYSTEM_DISK=local
MAIL_MAILER=log
BROADCAST_CONNECTION=log
ML_SERVICE_URL=http://ml-api:8000/api/v1
ML_SERVICE_TOKEN=$mlToken
ML_WEBHOOK_SECRET=$mlWebhookSecret
ML_TIMEOUT_SECONDS=10
ATLANTIA_FEATURE_ADVANCED_ML_AUTOMATION=true
FIREBASE_ENABLED=false
INFILE_MOCK=true
RECAPTCHA_ENABLED=false
ATLANTIA_SCHEDULER_HEARTBEAT_KEY=atlantia:ops:scheduler-heartbeat
ATLANTIA_SCHEDULER_STALE_AFTER_MINUTES=3
ATLANTIA_QUEUE_FAILED_JOBS_WARNING=1
ATLANTIA_QUEUE_FAILED_JOBS_ERROR=10
"@

        [IO.File]::WriteAllText($marketplaceEnv, $content, [Text.UTF8Encoding]::new($false))
        Write-Host 'Variables y secretos locales de staging creados.'
    }

    if (-not (Test-Path -LiteralPath $mlEnv)) {
        $redisPassword = Read-EnvironmentValue -Path $marketplaceEnv -Name 'REDIS_PASSWORD'
        $mlToken = Read-EnvironmentValue -Path $marketplaceEnv -Name 'ML_SERVICE_TOKEN'
        $mlWebhookSecret = Read-EnvironmentValue -Path $marketplaceEnv -Name 'ML_WEBHOOK_SECRET'

        $content = @"
APP_ENV=staging
APP_DEBUG=false
ML_SERVICE_TOKEN=$mlToken
ML_WEBHOOK_SECRET=$mlWebhookSecret
MARKETPLACE_BASE_URL=http://nginx
MARKETPLACE_TOKEN=$mlToken
REDIS_URL=redis://:$redisPassword@redis:6379/0
CELERY_BROKER_URL=redis://:$redisPassword@redis:6379/4
CELERY_RESULT_BACKEND=redis://:$redisPassword@redis:6379/5
MLFLOW_TRACKING_URI=file:/app/mlruns
DRIFT_THRESHOLD=0.25
CELERY_CONCURRENCY=2
"@

        [IO.File]::WriteAllText($mlEnv, $content, [Text.UTF8Encoding]::new($false))
        Write-Host 'Variables locales del servicio ML creadas.'
    }
}

function Invoke-Compose {
    param([Parameter(ValueFromRemainingArguments = $true)][string[]] $Arguments)

    & docker compose --env-file $marketplaceEnv -f $composeFile @Arguments

    if ($LASTEXITCODE -ne 0) {
        throw "docker compose termino con codigo $LASTEXITCODE."
    }
}

function Assert-Docker {
    & docker info --format '{{.ServerVersion}}' *> $null

    if ($LASTEXITCODE -ne 0) {
        throw 'Docker Desktop no esta disponible.'
    }
}

function Test-Staging {
    Invoke-Compose ps
    Invoke-WebRequest -UseBasicParsing -Uri 'http://127.0.0.1:8180/health' -TimeoutSec 15 | Out-Null
    Invoke-WebRequest -UseBasicParsing -Uri 'http://127.0.0.1:18000/api/v1/ready' -TimeoutSec 15 | Out-Null
    Invoke-WebRequest -UseBasicParsing -Uri 'http://127.0.0.1:17700/health' -TimeoutSec 15 | Out-Null
    Invoke-Compose -Arguments @(
        'exec',
        '-T',
        'ml-worker',
        'celery',
        '-A',
        'app.workers.celery_app.celery_app',
        'inspect',
        'ping',
        '--timeout=5'
    )
    Invoke-Compose exec -T app php artisan migrate:status
    Invoke-Compose exec -T app php artisan atlantia:ops-snapshot --json
}

Set-Location $root
Initialize-StagingEnvironment
Assert-Docker
Invoke-Compose config --quiet

switch ($Action) {
    'up' {
        Invoke-Compose build app nginx ml-api ml-worker
        Invoke-Compose up -d --wait mysql redis meilisearch ml-api ml-worker
        Invoke-Compose run --rm app php artisan migrate --force
        Invoke-Compose run --rm app php artisan db:seed --class=RolePermissionSeeder --force
        Invoke-Compose run --rm app php artisan passport:keys --force
        Invoke-Compose run --rm app php artisan scout:sync-index-settings
        Invoke-Compose run --rm app php artisan scout:import 'App\Models\Producto'
        Invoke-Compose up -d --wait app worker scheduler nginx
        Invoke-Compose exec -T app php artisan schedule:run --no-interaction
        Test-Staging
    }
    'down' {
        Invoke-Compose down
    }
    'status' {
        Invoke-Compose ps
    }
    'logs' {
        Invoke-Compose logs --tail=200
    }
    'verify' {
        Test-Staging
    }
}
