<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CommercialOperation
{
    public const PATHS = [
        'compras/online', 'bolsa/confirmar',
        'vendedor/ventas', 'vendedor/reservas',
        'superadmin/comercial/ventas', 'superadmin/comercial/reservas',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('POST') || !$request->is(...self::PATHS)) {
            return $next($request);
        }

        if ($request->is('compras/online', 'bolsa/confirmar')) {
            app(\App\Services\Superadmin\SaleCheckoutService::class)->ensureCustomerCanCheckout($request->user());
        }

        $request->validate(['operation_id' => ['required', 'uuid']], [
            'operation_id.required' => 'Recarga el formulario para iniciar una operación segura.',
            'operation_id.uuid' => 'El identificador de operación no es válido. Recarga el formulario.',
        ]);
        $id = strtolower($request->string('operation_id')->toString());
        $hash = hash('sha256', json_encode($this->canonical($request->except(['operation_id', '_token'])), JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($request, $next, $id, $hash) {
            // PostgreSQL waits on a concurrent insert of the same primary key.
            // The intent and its sale/reservation commit or roll back together.
            $claimed = DB::table('commercial_operations')->insertOrIgnore([
                'id' => $id,
                'user_id' => $request->user()->id,
                'path' => $request->path(),
                'request_hash' => $hash,
                'created_at' => now(),
            ]);
            $operation = DB::table('commercial_operations')->where('id', $id)->lockForUpdate()->first();

            if (!$claimed) {
                if ((int) $operation->user_id !== (int) $request->user()->id
                    || $operation->path !== $request->path() || !hash_equals($operation->request_hash, $hash)) {
                    throw ValidationException::withMessages(['operation_id' => 'Esta intención ya se utilizó con otros datos. Abre un nuevo formulario.']);
                }

                abort_unless($operation->redirect_to, 409, 'La operación todavía no está disponible.');

                return redirect($operation->redirect_to, 303);
            }

            $response = $next($request);
            // Routing may convert a validation exception into a redirect. Do not
            // retain failed intentions: the corrected form must remain usable.
            if ($response->exception ?? null) {
                throw $response->exception;
            }
            if (!$response->isRedirect()) {
                throw new HttpResponseException($response);
            }

            $location = $response->headers->get('Location');
            $target = (parse_url($location, PHP_URL_PATH) ?: '/');
            if ($query = parse_url($location, PHP_URL_QUERY)) {
                $target .= '?'.$query;
            }
            DB::table('commercial_operations')->where('id', $id)->update(['redirect_to' => $target]);

            return $response;
        });
    }

    private function canonical(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return ['sha256' => hash_file('sha256', $value->getRealPath())];
        }
        if (is_array($value)) {
            ksort($value);

            return array_map(fn ($item) => $this->canonical($item), $value);
        }

        return $value === null ? '' : (string) $value;
    }
}
