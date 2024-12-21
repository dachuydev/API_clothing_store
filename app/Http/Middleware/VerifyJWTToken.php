<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;

class VerifyJWTToken
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $request->bearerToken();
            
            if (!$token) {
                throw new \Exception('Token không được cung cấp');
            }

            $decoded = JWT::decode($token, new Key(config('jwt.secret'), 'HS256'));
            
            // Thêm thông tin user vào request
            $request->merge(['auth_user' => $decoded->data]);
            
            return $next($request);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }
    }
}