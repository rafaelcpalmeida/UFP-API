<?php

namespace App\Http\Middleware;

use Closure;

class APITokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(empty($request->input('api_token'))) {
            abort(403, 'Access denied');
        }
        
        return $next($request);
    }
}
