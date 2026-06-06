<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class CheckAppStatus
{
    // Rutas que nunca se bloquean aunque la app esté deshabilitada
    private const RUTAS_LIBRES = ['app-deshabilitada', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is(self::RUTAS_LIBRES)) {
            return $next($request);
        }

        $habilitada = Cache::remember('remote_app_status', 3600, function () {
            $url = config('services.killswitch.url');
            $key = config('services.killswitch.key');

            if (!$url || !$key) {
                return true; // Sin configurar → asumir habilitada
            }

            try {
                $response = Http::withHeaders(['X-Api-Key' => $key])
                    ->timeout(5)
                    ->get($url);

                if ($response->successful()) {
                    return (bool) $response->json('enabled', true);
                }
            } catch (\Exception) {
                // Error de red → no bloquear la app
            }

            return true;
        });

        if (!$habilitada) {
            return redirect()->route('app.deshabilitada');
        }

        return $next($request);
    }
}
