<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() ||
            ! $request->user() instanceof MustVerifyEmail ||
            $request->user()->hasVerifiedEmail() ||
            $request->session()->has('verified')) {  // Check for our custom verified session flag
            return $next($request);
        }

        return $request->expectsJson()
            ? abort(403, 'Your email address is not verified.')
            : Redirect::guest(URL::route('verification.notice'));
    }
}