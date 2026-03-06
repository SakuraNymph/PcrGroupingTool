<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DailyUserVisit extends Model
{
    protected $table = 'daily_user_visits';

    protected $fillable = [
        'user_id',
        'visit_date',
        'ip',
    ];

    /**
     * 记录或更新用户当天的访问记录
     * - 第一次访问：插入新记录
     * - 同一天后续访问：仅更新 updated_at
     *
     * @param int $userId
     * @param string|null $ip
     * @return void
     */
    public static function recordOrUpdateVisit(int $userId, ?string $ip = null): void
    {
        $today = now()->toDateString();

        // 尝试查找今天是否已有记录
        $visit = self::where('user_id', $userId)
            ->where('visit_date', $today)
            ->first();

        if ($visit) {
            // 已存在 → 只更新 updated_at（和 ip，如果需要）
            $visit->update([
                'updated_at' => now(),
                'ip' => $ip,           // 可选：更新最后一次的 IP
            ]);
        } else {
            // 不存在 → 创建新记录
            self::create([
                'user_id'    => $userId,
                'visit_date' => $today,
                'ip'         => $ip,
                // created_at 和 updated_at 会自动设置为 now()
            ]);
        }
    }
}