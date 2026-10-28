<?php

namespace App\Models;

use App\Security\BlindIndex;
use App\Security\HasEncryptedFields;
use App\Security\KeyVault;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Conta de acesso. type = professional | patient. O e-mail fica cifrado com a chave do ambiente
 * e é encontrado pelo índice cego email_hash.
 */
class User extends Authenticatable
{
    use HasEncryptedFields, HasUlids;

    protected array $encrypted = ['email'];

    protected $guarded = ['id'];

    protected $hidden = ['password', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $rememberTokenName = false;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function encryptionSubject(): string
    {
        return KeyVault::ENV;
    }

    public static function findByEmail(string $email): ?self
    {
        return self::where('email_hash', BlindIndex::email($email))->first();
    }

    public function setEmailAddress(string $email): void
    {
        $email = mb_strtolower(trim($email));
        $this->email = $email;
        $this->email_hash = BlindIndex::email($email);
    }

    public function professional(): HasOne
    {
        return $this->hasOne(Professional::class, 'user_id');
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class, 'user_id');
    }

    public function isProfessional(): bool
    {
        return $this->type === 'professional';
    }

    public function isPatient(): bool
    {
        return $this->type === 'patient';
    }

    public function isMaster(): bool
    {
        return $this->isProfessional() && $this->professional?->role === 'master';
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    public function displayName(): string
    {
        return $this->isProfessional() ? (string) $this->professional?->name : (string) $this->patient?->name;
    }

    /** Nome que vai para a auditoria. Paciente aparece pelo papel, sem nome em claro no log. */
    public function auditName(): string
    {
        if ($this->isProfessional()) {
            return $this->professional?->name.' ('.($this->isMaster() ? 'master' : 'equipe').')';
        }

        return 'paciente';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
    }
}
