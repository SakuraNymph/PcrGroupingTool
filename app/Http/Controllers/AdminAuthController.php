<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        // dd(Auth::guard('admin')->id());
        $insert = [
            'username' => 'chuang',
            'password' => Hash::make('2104041'),
            'status' => '1',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        // DB::table('admins')->insert($insert);
        return view('auth.admin-login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');
        if (Auth::guard('admin')->attempt($credentials)) {
            return redirect()->intended('admin/index');
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ]);
    }

    public function index(Request $request)
    {
        return view('admin/index');
    }
}
