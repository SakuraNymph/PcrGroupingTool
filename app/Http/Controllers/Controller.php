<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct(Request $request)
    {
        $user_ip = $request->ip();
        if (empty($user_ip)) {
            $user_ip = 0;
        }
        $user_info = User::getUserInfoByIp($user_ip);
        if ($user_info) {
            if (!session('id')) {
                session($user_info);
            }
        }
    }
}
