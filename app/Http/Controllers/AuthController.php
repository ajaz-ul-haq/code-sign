<?php

namespace App\Http\Controllers;

use App\Http\Requests\TokenRequest;
use App\Models\Client;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    public function login(TokenRequest $request): JsonResponse
    {
        $client = Client::where('LICENSE_CODE', $request->LICENSE_CODE)
            ->where('APP_URL', $request->APP_URL)->firstOr(function () {
                throw new HttpResponseException($this->errorResponse('License code not found', 404));
            });

        $client->tokens()->delete();

        return $this->successResponse('', [
            'token' => $client->createToken('code_sign_token', ['code-sign'])->plainTextToken,
        ]);
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
