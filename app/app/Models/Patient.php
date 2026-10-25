<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasEncryptedFields, HasUlids, SoftDeletes;

    protected array $encrypted = ['name', 'birth_date', 'phone', 'height', 'objective'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['started_on' => \App\Casts\DateOnly::class, 'is_minor' => 'boolean'];
    }

    public function encryptionSubject(): string
    {
        return (string) $this->getKey();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(PatientGoal::class);
    }

    public function careTeam(): HasMany
    {
        return $this->hasMany(CareTeam::class);
    }

    public function goal(string $type, mixed $default = null): mixed
    {
        $goal = $this->goals->firstWhere('type', $type);

        return $goal ? $goal->target : $default;
    }

    public function firstName(): string
    {
        return explode(' ', trim((string) $this->name))[0] ?? '';
    }

    public function age(): ?int
    {
        return $this->birth_date ? CarbonImmutable::parse($this->birth_date)->age : null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
