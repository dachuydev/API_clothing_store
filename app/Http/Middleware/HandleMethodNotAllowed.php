<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class HandleMethodNotAllowed
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (MethodNotAllowedHttpException $e) {
            throw $e;
        }
    }
} 