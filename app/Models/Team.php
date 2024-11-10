<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'boss',
        'score',
        'difficulty',
        'open',
        'status',
        'remark',
        'created_at',
        'updated_at',
    ];

    public function teamRoles(): HasMany
    {
        return $this->hasMany(TeamRole::class);
    }
}
