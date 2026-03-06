<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * 全项目统一获取当前用户 UID（登录用户优先 → 临时访客）
     * 在任何继承本类的控制器中直接调用 $this->uid()
     */
    protected function uid(): ?int
    {
        // 优先已登录用户
        if ($uid = auth('user')->id()) {
            return (int) $uid;
        }

        // 再取临时访客 ID
        $guestId = session('guest_id');
        return is_numeric($guestId) && $guestId > 0 ? (int) $guestId : null;
    }
}