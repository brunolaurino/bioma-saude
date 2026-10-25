<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Professional extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public const SPECIALTIES = [
        'Medicina' => ['icon' => 'user-doctor', 'council' => 'CRM'],
        'Nutrição' => ['icon' => 'apple-whole', 'council' => 'CRN'],
        'Educação física' => ['icon' => 'person-running', 'council' => 'CREF'],
        'Psicologia' => ['icon' => 'brain', 'council' => 'CRP'],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function icon(): string
    {
        return self::SPECIALTIES[$this->specialty]['icon'] ?? 'user-doctor';
    }

    public function isMaster(): bool
    {
        return $this->role === 'master';
    }
}
