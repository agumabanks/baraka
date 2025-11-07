<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/success',
        '/cancel',
        '/fail',
        '/ipn',
        '/pay-via-ajax', // only required to run example codes. Please see bellow.

        // admin
        '/admin/payout/success',
        '/admin/payout/cancel',
        '/admin/payout/fail',
        '/admin/payout/ipn',
        '/admin/payout/pay-via-ajax', // only required to run example codes. Please see bellow.

        // aamarpay
        '/aamarpay-success',
        '/aamarpay-fail',
    ];
    public function handle($request, Closure $next)
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
