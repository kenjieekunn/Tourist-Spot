<?php

namespace App\Http\Middleware;

use App\Models\AdminTempCredential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTemporaryStaffPasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->isMunicipalityStaff()) {
            return $next($request);
        }

        $hasTemporaryCredential = AdminTempCredential::where('user_id', $user->id)->exists();
        if (!$hasTemporaryCredential || $request->routeIs(
            'account.password.change',
            'account.password.update',
            'logout'
        )) {
            return $next($request);
        }

        return redirect()->route('account.password.change');
    }
}