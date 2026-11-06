<?php

namespace App\Http\Middleware;

use App\Features\Stock\Models\Stock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route level ownership check for /stocks/{stock} and /admin/users/{user}/stocks.
 *
 * Members may only reach their own stocks. Admins may view other users' stocks
 * through the admin pages, but they may never perform write requests on them.
 */
class EnsureStockOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($request->route('user') !== null) {
            $routeUser = $request->route('user');

            if (! $user->isAdmin() && (int) $user->id !== (int) $routeUser) {
                abort(403, 'Anda tidak berhak mengakses stok pengguna lain.');
            }

            return $next($request);
        }

        $stock = $request->route('stock');

        if ($stock === null) {
            return $next($request);
        }

        if (! $stock instanceof Stock) {
            $stock = Stock::findOrFail($stock);
        }

        if (! $user->isAdmin() && ! $this->owns($user, $stock)) {
            abort(403, 'Anda tidak berhak mengakses stok ini.');
        }

        if (! $request->isMethodSafe() && ! $this->owns($user, $stock)) {
            abort(403, 'Anda tidak berhak mengubah stok ini.');
        }

        return $next($request);
    }

    private function owns($user, Stock $stock): bool
    {
        return (int) $user->id === (int) $stock->user_id;
    }
}
