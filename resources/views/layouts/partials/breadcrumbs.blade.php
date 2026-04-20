@isset($breadcrumbs)
    <nav class="mb-4 text-sm text-slate-600" aria-label="Migaja de pan">
        <ol class="flex flex-wrap items-center gap-2">
            @foreach ($breadcrumbs as $label => $url)
                <li class="flex items-center gap-2">
                    @if (! $loop->first)
                        <span class="text-slate-400" aria-hidden="true">/</span>
                    @endif

                    @if ($url && ! $loop->last)
                        <a href="{{ $url }}" class="hover:text-emerald-700">{{ $label }}</a>
                    @else
                        <span class="font-medium text-slate-900">{{ $label }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endisset
