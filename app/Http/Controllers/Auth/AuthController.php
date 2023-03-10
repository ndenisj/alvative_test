<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private RegisterService $registerService;

    public function __construct(RegisterService $registerService)
    {
        $this->registerService = $registerService;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->registerService->create($request->validated());

        $data['token'] = $user->createToken(request()->userAgent())->plainTextToken;
        $data['user'] = $user;

        return $this->responseOK($data, 'Registration successful');
    }

    public function login(LoginRequest $request)
    {
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];
        if (auth()->attempt($credentials)) {
            $user = Auth::user();
            $data['token'] = $user->createToken(request()->userAgent())->plainTextToken;
            $data['user'] = $user;
            return $this->responseOK($data, 'Login successful');
        }

        return $this->responseUnauthorise(null, 'Unauthorised');
    }
}
