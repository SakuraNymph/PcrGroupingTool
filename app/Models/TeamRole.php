<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamRole extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'team_id',
        'role_id',
        'status',
    ];

    public function team() {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function role() {
        return $this->hasOne(Role::class, 'role_id', 'role_id');
    }
}
