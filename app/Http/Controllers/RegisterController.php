<?php

namespace App\Http\Controllers;

use App\Services\CustomMailer;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;



class RegisterController extends Controller
{
    public function index()
    {
        return view('register.register');
    }

    public function doRegister(Request $request)
    {
        $ip = $request->ip();
        if (empty($ip)) {
            show_json(0, '系统错误！');
        }
        $params = $request->all();
        // 验证请求参数
        $rules = [
            'email'           => 'required|email',
            'vercode'         => 'required|integer|min:100000|max:999999',
            'password'        => 'required|min:6|max:16',
            'confirmPassword' => 'required|same:password',
            'nickname'        => 'required|min:2|max:12',
        ];

        $messages = [
            'vercode.required'         => '缺少:attribute信息',
            'vercode.integer'          => ':attribute参数错误',
            'vercode.min'              => ':attribute参数错误',
            'vercode.max'              => ':attribute参数错误',
            'email.required'           => '邮箱是必填的',
            'email.email'              => '请输入有效的邮箱地址',
            'password.required'        => '密码是必填的',
            'password.min'             => '密码长度必须至少为6个字符',
            'confirmPassword.required' => '确认密码是必填的',
            'confirmPassword.same'     => '确认密码必须与密码一致',
            'nickname.required'        => '昵称是必填的',
            'nickname.min'             => '昵称长度必须至少为2个字符',
            'nickname.max'             => '昵称长度不能超过12个字符',
        ];

        $paramsName = [
            'email'           => '邮箱',
            'vercode'         => '验证码',
            'password'        => '密码',
            'confirmPassword' => '确认密码',
            'nickname'        => '昵称',
        ];

        
        $cacheKey = 'emailCode' . $ip;
        $code = Cache::get($cacheKey);
        if (empty($code)) {
            show_json(0, '验证码已过期，请重新获取验证码！');
        }
        if ($params['vercode'] != $code) {
            show_json(0, '验证码错误！');
        }

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

        $res = UserService::register($ip, $params['email'], $params['password'], $params['nickname']);

        return $res ? show_json(1) : show_json(0);
    }

    public function sendEmail(Request $request)
    {
        $ip   = $request->ip();
        $email = $request->input('email');

        // 验证请求参数
        $rules = [
            'email' => 'required|email',
        ];

        $messages = [
            'email.required' => '缺少:attribute信息',
            'email.email'    => ':attribute参数错误',
        ];

        $paramsName = [
            'email' => 'email',
        ];
// show_json(1);
        // 创建验证器
        $validator = Validator::make(['email' => $email], $rules, $messages, $paramsName);

        if ($validator->fails()) {
            $errorMessages = '';
            $errorArr      = $validator->errors()->toArray();
            foreach ($errorArr as $key => $value) {
                $errorMessages = $value[0];
            }
            show_json(0, $errorMessages);
        }

        // 验证是否已经注册
        $ok = DB::table('users')->where('email', $email)->first();
        if ($ok) {
            show_json(0, '该邮箱已注册！');
        }

        $customMailer = new CustomMailer;
        $cacheKey     = 'emailCode' . $ip;
        $code         = Cache::get($cacheKey);
        if (empty($code)) {
            $code = rand(100000, 999999);
            Cache::put($cacheKey, $code, 300);
        }
        $message = '您的验证码为【' . $code . '】，5分钟内有效';
        $status  = $customMailer->send($email, '公主连结分刀助手', $message);
        show_json((int)$status);
    }
}
