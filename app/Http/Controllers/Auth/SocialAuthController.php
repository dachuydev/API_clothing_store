<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuthService;
use App\Services\Auth\GithubAuthService;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponse;

/**
 * Controller xử lý đăng nhập qua mạng xã hội (Google, Github)
 */
class SocialAuthController extends Controller
{
    use ApiResponse;

    private $googleAuth;
    private $githubAuth;

    /**
     * Khởi tạo các service xác thực Google và Github
     */
    public function __construct(GoogleAuthService $googleAuth, GithubAuthService $githubAuth)
    {
        $this->googleAuth = $googleAuth;
        $this->githubAuth = $githubAuth;
    }

    /**
     * Xử lý đăng nhập qua mạng xã hội
     * Kiểm tra token, xác thực và tạo/cập nhật thông tin user
     */
    public function login(Request $request)
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return $this->errorResponse('Token không được cung cấp', 401);
            }

            Log::info('Received token:', ['token' => $token]);

            $provider = $this->detectTokenProvider($token);
            $userData = $this->verifyToken($token, $provider);

            if (!$userData) {
                return $this->errorResponse('Token không hợp lệ', 401);
            }

            $user = $this->findOrCreateUser($userData, $provider);

            return $this->successResponse('Đăng nhập thành công', ['user' => $user]);
        } catch (Exception $e) {
            Log::error('Login failed: ' . $e->getMessage());
            return $this->errorResponse('Đăng nhập thất bại', 401, $e->getMessage());
        }
    }

    /**
     * Xác thực token với provider tương ứng (Google hoặc Github)
     */
    private function verifyToken($token, $provider)
    {
        return $provider === 'github'
            ? $this->githubAuth->verifyToken($token)
            : $this->googleAuth->verifyToken($token);
    }

    /**
     * Phát hiện loại token thuộc provider nào dựa vào định dạng
     * Github token có định dạng gh[pousr]_[36 ký tự] hoặc 40 ký tự hex
     * Còn lại được coi là Google token
     */
    private function detectTokenProvider($token)
    {
        if (
            preg_match('/^gh[pousr]_[A-Za-z0-9_]{36}$/', $token) ||
            preg_match('/^[a-f0-9]{40}$/', $token)
        ) {
            return 'github';
        }

        return 'google';
    }

    /**
     * Tìm user theo email hoặc tạo mới nếu chưa tồn tại
     * Cập nhật thông tin provider nếu user đã tồn tại
     */
    private function findOrCreateUser($userData, $provider)
    {
        $user = User::where('email', $userData['email'])->first();

        if (!$user) {
            return User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'avatar' => $userData['avatar'],
                'provider' => $provider,
                'provider_id' => $userData['provider_id']
            ]);
        }

        $user->update([
            'avatar' => $userData['avatar'],
            'provider' => $provider,
            'provider_id' => $userData['provider_id']
        ]);

        return $user;
    }
} 