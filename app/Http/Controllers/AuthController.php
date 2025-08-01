<?php

namespace App\Http\Controllers;

use App\Http\Requests\TokenRequest;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{

    public function login(TokenRequest $request): JsonResponse
    {
        $attempt = Auth::attempt($request->only(['username', 'password']));

        if (!$attempt || !($authUser = Auth::user())) {
            return $this->errorResponse('Invalid Username or Password');
        }

        $authUser->tokens()->delete();

        return $this->successResponse('', [
            'token' => $authUser->createToken('code_sign_token', ['code-sign'])->plainTextToken,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        (new InstallationController())->handleInstall($request->input('username'), $response);

         return $response['success'] ? response()->json($response) : $this->errorResponse($response['message']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->successResponse('Logged out successfully');
    }


    public function info(): JsonResponse
    {
        return $this->successResponse('', Auth::user());
    }
}
