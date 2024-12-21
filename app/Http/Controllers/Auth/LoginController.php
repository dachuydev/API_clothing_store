<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Exception;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || $request->password !== $user->password) {
                return $this->errorResponse('Email hoặc mật khẩu không chính xác', 401);
            }

            return $this->successResponse('Đăng nhập thành công', ['user' => $user]);
        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $e->errors()
            );
        } catch (Exception $e) {
            return $this->errorResponse('Đăng nhập thất bại', 401, $e->getMessage());
        }
    }
} 