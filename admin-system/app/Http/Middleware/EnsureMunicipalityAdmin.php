<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMunicipalityAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login.form');
        }

        if (!auth()->user()->isMunicipalityAdmin()) {
            abort(403, 'Unauthorized access. Municipality admin privileges required.');
        }

        if (!auth()->user()->municipality_id || !auth()->user()->municipality) {
            \Log::warning('Municipality admin access attempt without municipality assignment', [
                'user_id' => auth()->user()->id,
                'email' => auth()->user()->email,
                'municipality_id' => auth()->user()->municipality_id,
            ]);
            
            return redirect()->route('login.form')->with('error', 'Your municipality has not been assigned. Please contact the super admin.');
        }

        return $next($request);
    }
}
