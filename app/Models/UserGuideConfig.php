<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGuideConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'configuration',
    ];

    protected $casts = [
        'configuration' => 'array'
    ];

    const LOCATIONS = ['welcome', 'list', 'post', 'team', 'group'];

    public static function getUserGuideConfig($uid, $location)
    {
        $config = self::where('uid', $uid)->first()?->configuration ?? [];
        if (empty($config)) {
            self::create(['uid' => $uid, 'configuration' => array_fill_keys(self::LOCATIONS, false)]);
        }
        return $config[$location] ?? false;
    }
}
