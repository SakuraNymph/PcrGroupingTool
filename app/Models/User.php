<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'ip', 'status', 'nickname', 
        'is_subscribe', 'sub_start', 'sub_end'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'sub_start'         => 'string',
        'sub_end'           => 'string',
        'is_subscribe'      => 'boolean',
        'status'            => 'integer',
    ];

    /**
     * 获取或创建基于 IP 的临时访客用户，并返回 ID
     */
    public static function getOrCreateGuestIdByIp(string $ip): ?int
    {
        if (empty($ip) || in_array($ip, ['127.0.0.1', '::1'])) {
            return null;
        }

        $cacheKey = 'guest_user:' . md5($ip);

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($ip) {
            $user = self::firstOrCreate(
                ['ip' => $ip],
                [
                    'status'       => 0,
                    'name'         => '访客_' . substr(md5($ip), 0, 8),
                    'nickname'     => null,
                    'is_subscribe' => 0,
                    'sub_start'    => '00:00:00',    // 或 '09:00' 作为默认提醒开始时间
                    'sub_end'      => '23:59:59',    // 或 '18:00'
                ]
            );

            return $user->id;
        });
    }
}