<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected $except = [
        // Tambahkan endpoint yang perlu dikecualikan di sini jika pakai Sanctum + API
        // 'api/*'
    ];
}
