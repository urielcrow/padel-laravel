<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Illuminate\Support\Facades\DB;

class JwtMiddleware extends BaseMiddleware
{
	public function handle($request, Closure $next)
	{
		try {
		   	$user = JWTAuth::parseToken()->authenticate();//default
		} 
		catch(Exception $e) {
        if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException)
		    	return response()->json(['status' => 'Token is Invalid'], 403);
		  	else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException)
					return response()->json(['status' => 'Token is Expired'], 401);
		  	else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenBlacklistedException)
					return response()->json(['status' => 'Bad request'], 400);
		  	else
		      return response()->json(['status' => 'Token Not Found'], 401);
		}
    return $next($request);
	}
}

