<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'ip', 'status', 'nickname', 'is_subscribe', 'sub_start', 'sub_end'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function getUserInfoByIp($ip)
    {
        $exists = self::where('ip', $ip)->exists();
        if (!$exists) {
            self::create(['ip' => $ip, 'status' => 0, 'is_subscribe' => 0, 'sub_start' => '00:00:00', 'sub_end' => '23:59:59']);
        }
        $user_info = self::where('ip', $ip)->first();
        $user_info = $user_info ? $user_info->toArray() : [];
        self::where('ip', $ip)->update(['updated_at' => timeToStr()]);
        return $user_info;
    }
}
