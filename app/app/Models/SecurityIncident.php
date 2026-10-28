<?php

namespace App\Models;

use App\Security\HasEncryptedFields;
use App\Security\KeyVault;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SecurityIncident extends Model
{
    use HasEncryptedFields, HasUlids;

    protected array $encrypted = ['description'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['detected_at' => 'datetime', 'anpd_notified_at' => 'datetime', 'patients_notified_at' => 'datetime'];
    }

    public function encryptionSubject(): string
    {
        return KeyVault::ENV;
    }
}
