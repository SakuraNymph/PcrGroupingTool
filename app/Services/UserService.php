<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public static function register($ip, $email, $password, $nickname)
    {
        $status = User::where('ip', $ip)->value('status');
        if ($status) {
            return false;
        }
        $update = [
            'nickname' => $nickname,
            'status'   => 1,
            'email'    => $email,
            'password' => Hash::make($password),
        ];
        return User::where('ip', $ip)->update($update);
    }

    public static function login($email, $password)
    {
        $userPassword = User::where('email', $email)->value('password');
        if (empty($userPassword)) {
            return false;   
        }
        if (Hash::check($password, $userPassword)) {
            return User::where('email', $email)->first();
            return true;
        }
        return false;
    }

    public static function aaaa()
    {
        return Hash::make(666666);
    }

}
