<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

/**
 * Handler clasico para excepciones de dominio.
 */
class Handler extends ExceptionHandler
{
    /**
     * Registra callbacks de renderizado.
     */
    public function register(): void
    {
        $this->renderable(function (AtlantiaDomainException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->publicMessage(),
                ], $exception->statusCode());
            }

            return back()
                ->withInput()
                ->with('error', $exception->publicMessage())
                ->with('error_type', class_basename($exception));
        });
    }

    /**
     * Reporta excepciones con la logica base de Laravel.
     */
    public function report(Throwable $e): void
    {
        parent::report($e);
    }
}
