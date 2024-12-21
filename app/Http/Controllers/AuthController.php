<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use App\Models\User;
use GuzzleHttp\Client;
use Exception;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Lấy token từ header và kiểm tra
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'message' => 'Token không được cung cấp'
                ], 401);
            }

            Log::info('Received token:', ['token' => $token]);

            // Phân biệt loại token
            $provider = $this->detectTokenProvider($token);
            
            if ($provider === 'github') {
                $userData = $this->verifyGithubToken($token);
            } else {
                $userData = $this->verifyGoogleToken($token);
            }

            if (!$userData) {
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'message' => 'Token không hợp lệ'
                ], 401);
            }

            $user = User::where('email', $userData['email'])->first();

            if (!$user) {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'avatar' => $userData['avatar'],
                    'provider' => $provider,
                    'provider_id' => $userData['provider_id']
                ]);
            } else {
                $user->update([
                    'avatar' => $userData['avatar'],
                    'provider' => $provider,
                    'provider_id' => $userData['provider_id']
                ]);
            }

            return response()->json([
                'status' => 'success',
                'ok' => true,
                'message' => 'Đăng nhập thành công',
                'user' => $user
            ]);
        } catch (Exception $e) {
            Log::error('Login failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'ok' => false,
                'message' => 'Đăng nhập thất bại',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    public function loginWithPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || $request->password !== $user->password) {
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'message' => 'Email hoặc mật khẩu không chính xác'
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'ok' => true,
                'message' => 'Đăng nhập thành công',
                'user' => $user
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'ok' => false,
                'message' => 'Đăng nhập thất bại',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    private function verifyGoogleToken($token)
    {
        $client = new Google_Client(['client_id' => config('services.google.client_id')]);
        $payload = $client->verifyIdToken($token);
        
        if (!$payload) return null;

        return [
            'email' => $payload['email'],
            'name' => $payload['name'],
            'avatar' => $payload['picture'] ?? null,
            'provider_id' => $payload['sub']
        ];
    }

    private function verifyGithubToken($token)
    {
        try {
            if (!str_starts_with($token, 'token ')) {
                $token = 'token ' . $token;
            }
            
            $client = new Client();
            
            // Lấy thông tin user
            $response = $client->get('https://api.github.com/user', [
                'headers' => [
                    'Authorization' => $token,
                    'Accept' => 'application/json',
                ]
            ]);
            
            $userData = json_decode($response->getBody(), true);

            // Lấy email từ API riêng
            $emailResponse = $client->get('https://api.github.com/user/emails', [
                'headers' => [
                    'Authorization' => $token,
                    'Accept' => 'application/json',
                ]
            ]);

            $emails = json_decode($emailResponse->getBody(), true);
            
            // Lấy email chính (primary email)
            $primaryEmail = collect($emails)->first(function ($email) {
                return $email['primary'] === true;
            });

            return [
                'email' => $primaryEmail ? $primaryEmail['email'] : null,
                'name' => $userData['name'] ?? $userData['login'],
                'avatar' => $userData['avatar_url'],
                'provider_id' => (string) $userData['id']
            ];
        } catch (Exception $e) {
            Log::error('GitHub verification failed: ' . $e->getMessage());
            throw new Exception('Token không hợp lệ');
        }
    }

    private function detectTokenProvider($token)
    {
        // GitHub tokens thường là 40 ký tự hex
        if (preg_match('/^gh[pousr]_[A-Za-z0-9_]{36}$/', $token) || 
            preg_match('/^[a-f0-9]{40}$/', $token)) {
            return 'github';
        }
        
        // Google tokens là JWT, có format: xxx.yyy.zzz
        if (str_contains($token, '.') && count(explode('.', $token)) === 3) {
            return 'google';
        }
        
        // Mặc định xử lý như Google token
        return 'google';
    }

    public function register(Request $request)
    {
        try {
            // Validate đầu vào
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed'
            ], [
                'name.required' => 'Vui lòng nhập tên',
                'email.required' => 'Vui lòng nhập email',
                'email.email' => 'Email không hợp lệ',
                'email.unique' => 'Email đã tồn tại',
                'password.required' => 'Vui lòng nhập mật khẩu',
                'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
                'password.confirmed' => 'Xác nhận mật khẩu không khớp'
            ]);

            // Tạo user mới
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password
            ]);

            return response()->json([
                'status' => 'success',
                'ok' => true,
                'message' => 'Đăng ký thành công',
                'user' => $user
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'ok' => false,
                'message' => 'Đăng ký thất bại',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
