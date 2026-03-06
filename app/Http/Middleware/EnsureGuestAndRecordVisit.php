<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\DailyUserVisit;   // 后面会创建
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestAndRecordVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $uid = Auth::guard('user')->id();

        // 已登录用户 → 跳过访客创建
        if (!$uid) {
            $ip = $request->ip();
            $guestId = User::getOrCreateGuestIdByIp($ip);

            if ($guestId && !session('guest_id')) {
                session(['guest_id' => $guestId]);
            }
        }

        // 记录每日访问（每人每天只记一次）
        $currentUid = $uid ?? session('guest_id');
        if ($currentUid) {
            DailyUserVisit::recordOrUpdateVisit($currentUid, $request->ip());
        }

        return $next($request);
    }
}