<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function __construct()
    {
        // $this->middleware('web');
    }

    public function index()
    {
        $error = false;
        return view('login.login', ['error' => $error]);
    }

    public function doLogin(Request $request)
    {
        $ip = $request->ip();
        if (empty($ip)) {
            show_json(0, '系统错误！');
        }

        $params = $request->all();

        // dd($params);
        // 验证请求参数
        $rules = [
            'email'    => 'required|email',
            'password' => 'required|min:6|max:16',
        ];

        $messages = [
            'email.required'    => '邮箱是必填的',
            'email.email'       => '请输入有效的邮箱地址',
            'password.required' => '密码是必填的',
            'password.min'      => '密码长度必须至少为6个字符',
        ];

        $paramsName = [
            'email'    => '邮箱',
            'password' => '密码',
        ];

        // 创建验证器
        $validator = Validator::make($params, $rules, $messages, $paramsName);

        if ($validator->fails()) {
            $errorMessages = '';
            $errorArr      = $validator->errors()->toArray();
            foreach ($errorArr as $key => $value) {
                $errorMessages = $value[0];
            }
            show_json(0, $errorMessages);
        }

        $userInfo = UserService::login($params['email'], $params['password']);

        if ($userInfo) {
            Auth::login($userInfo);
            return redirect('/');
        }
        return view('login.login', ['error' => true]);
    }
}
