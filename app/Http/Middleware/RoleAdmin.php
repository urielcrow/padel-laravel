<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;

class RoleAdmin
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
     
        if(auth()->user()->access !== 3 ){
            return response()->json(['status' => 'Forbidden'],403);
        }
        
        return $next($request);
    }
}
