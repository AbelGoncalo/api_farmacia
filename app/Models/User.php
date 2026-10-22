<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

#[Fillable(['nome', 'email', 'telefone', 'nif', 'palavra_passe'])]
#[Hidden(['palavra_passe', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    public function getAuthPasswordName(): string
    {
        return 'palavra_passe';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }

    public function getJWTIdentifier(): int|string
    {
        return $this->getKey();
    }

    /** @return array<string, mixed> */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verificado_em' => 'datetime',
            'palavra_passe' => 'hashed',
        ];
    }
}
