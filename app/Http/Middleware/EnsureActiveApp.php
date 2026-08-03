<?php

namespace App\Http\Middleware;

use App\Support\ActiveApp;
use Closure;
use Illuminate\Http\Request;

class EnsureActiveApp
{
    public function handle(Request $request, Closure $next)
    {
        // Make sure session has active app id (no redirect loops).
        ActiveApp::ensureId();

        return $next($request);
    }
}
