<?php

namespace App\Services\Auth;

use App\Models\User;

class RegisterService
{
    public function create(array $userData): User
    {
        $user = User::create($userData);
        return $user;
    }
}
