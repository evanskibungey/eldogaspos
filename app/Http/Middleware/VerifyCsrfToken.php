<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // The SMS gateway posts inbound messages here and has no session to
        // carry a token. It is protected by the secret in the URL instead.
        'sms/inbound/*',
    ];
}
