<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstallationController extends Controller
{

    public function handleInstall(string $username, &$response)
    {
        if (User::where('username', $username)->exists()) {
            $response = [
                'success' => false,
                'message' => 'User already exists.',
            ];
            return;
        }

        User::create([
            'username' => $username,
            'password' => Hash::make($password = Str::random(37)),
            'identifier' => 'application',
        ]);

        $response = [
            'success' => true,
            'data' => [
                'username' => $username,
                'password' => $password
            ]
        ];
    }
}
