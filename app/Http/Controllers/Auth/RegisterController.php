<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Exception;
use App\Traits\ApiResponse;

class RegisterController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password
            ]);

            return $this->successResponse('Đăng ký thành công', ['user' => $user]);
        } catch (Exception $e) {
            return $this->errorResponse('Đăng ký thất bại', 400, $e->getMessage());
        }
    }
} 