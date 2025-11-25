<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards)
    {
        if (Auth::check()) {
            return redirect('/dashboard'); // ganti sesuai kebutuhan
        }

        return $next($request);
    }
}
