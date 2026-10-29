<?php

use App\Http\Middleware\PastikanPeranSiswa;
use App\Http\Middleware\PastikanSiswaTerverifikasi;
use App\Http\Middleware\PastikanStatusSiswa;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(
            fn (Request $request): ?string => $request->is('api/*') ? null : '/admin/login',
        );

        $middleware->alias([
            'siswa' => PastikanPeranSiswa::class,
            'siswa.terverifikasi' => PastikanSiswaTerverifikasi::class,
            'siswa.status' => PastikanStatusSiswa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => 'Tidak terautentikasi.',
            ], 401);
        });

        $exceptions->render(function (ValidationException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->status);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => 'Terlalu banyak percobaan. Silakan coba lagi setelah satu menit.',
            ], 429, $e->getHeaders());
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => 'Endpoint atau data tidak ditemukan.',
            ], 404, $e->getHeaders());
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Permintaan tidak dapat diproses.',
            ], $e->getStatusCode(), $e->getHeaders());
        });

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') || $e instanceof HttpResponseException) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server.',
            ], 500);
        });
    })->create();
