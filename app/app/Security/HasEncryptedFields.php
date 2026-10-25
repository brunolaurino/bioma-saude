<?php

namespace App\Security;

use Illuminate\Support\Str;

/**
 * Campos cifrados com a chave do titular. O modelo declara:
 *   protected array $encrypted = ['note', 'text'];
 * e, se não for o próprio paciente, tem a coluna patient_id. Os valores ficam em claro só na
 * memória da requisição; o banco recebe texto cifrado ligado a tabela, coluna e id da linha.
 */
trait HasEncryptedFields
{
    /** @var array<string, mixed> */
    private array $plainValues = [];

    /** @var array<string, true> */
    private array $pendingEncryption = [];

    public static function bootHasEncryptedFields(): void
    {
        static::saving(function ($model) {
            $model->encryptPendingFields();
        });
    }

    public function encryptionSubject(): string
    {
        return (string) $this->getAttribute('patient_id');
    }

    public function getAttribute($key)
    {
        if (in_array($key, $this->encrypted, true)) {
            if (! array_key_exists($key, $this->plainValues)) {
                $raw = $this->attributes[$key] ?? null;
                $this->plainValues[$key] = Crypto::isCiphertext($raw)
                    ? Crypto::decrypt($this->encryptionSubject(), $this->encryptionContext($key), $raw)
                    : $raw;
            }

            return $this->plainValues[$key];
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encrypted, true)) {
            $this->plainValues[$key] = $value;
            $this->pendingEncryption[$key] = true;
            // Marcador provisório só para o Eloquent ver a coluna como alterada; vira cifra no saving.
            $this->attributes[$key] = 'pending:'.Str::random(16);

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->plainValues = [];
        $this->pendingEncryption = [];

        return parent::setRawAttributes($attributes, $sync);
    }

    protected function encryptPendingFields(): void
    {
        if (! $this->pendingEncryption) {
            return;
        }

        if (! $this->getKey()) {
            $this->setAttribute($this->getKeyName(), $this->newUniqueId());
        }

        foreach (array_keys($this->pendingEncryption) as $key) {
            $this->attributes[$key] = Crypto::encrypt($this->encryptionSubject(), $this->encryptionContext($key), $this->plainValues[$key]);
        }

        $this->pendingEncryption = [];
    }

    private function encryptionContext(string $key): string
    {
        return $this->getTable().'|'.$key.'|'.$this->getKey();
    }
}
