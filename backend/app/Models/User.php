<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * UTILISATEUR du MLD (docs/correspondance-mld.md) : table users du starter kit, `pseudo` à la place de `name`.
 *
 * @property int $id
 * @property string $pseudo
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Role $role
 * @property Carbon|null $suspendu_le
 * @property Carbon|null $derniere_connexion_le
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
// role et suspendu_le hors de #[Fillable] : un formulaire ne doit jamais pouvoir faire d'un membre un admin.
#[Fillable(['pseudo', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'suspendu_le' => 'datetime',
            'derniere_connexion_le' => 'datetime',
        ];
    }

    /**
     * La seule définition de l'admin, réutilisée par l'API (Gates, Policies) et par Filament (canAccessPanel).
     */
    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->pseudo, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /*
     * Ce que le membre enregistre. Supprimer le compte efface tout cela (règle 19) : les clés
     * étrangères sont en cascadeOnDelete dans les migrations, la base s'en charge.
     */

    /** @return HasMany<MarquePage, $this> */
    public function marquePages(): HasMany
    {
        return $this->hasMany(MarquePage::class);
    }

    /** @return HasMany<Collection, $this> */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    /** @return HasMany<Annotation, $this> */
    public function annotations(): HasMany
    {
        return $this->hasMany(Annotation::class);
    }

    /**
     * Les pages sur lesquelles le membre a voté, par la table pivot votes (clé composée, règle 10).
     * Voter : $user->pagesVotees()->attach($adresse->id, ['valeur' => 1]).
     *
     * created_at n'est pas dans withPivot : s'il y était, Laravel daterait la ligne lui-même avec les
     * colonnes de User (created_at ET updated_at), et votes n'a pas d'updated_at. La base le remplit.
     *
     * @return BelongsToMany<Adresse, $this, Vote, 'vote'>
     */
    public function pagesVotees(): BelongsToMany
    {
        return $this->belongsToMany(Adresse::class, 'votes')
            ->using(Vote::class)
            ->as('vote')
            ->withPivot('valeur');
    }

    /** @return HasMany<LienCourt, $this> */
    public function liensCourts(): HasMany
    {
        return $this->hasMany(LienCourt::class);
    }

    /** @return HasMany<Recherche, $this> */
    public function recherches(): HasMany
    {
        return $this->hasMany(Recherche::class);
    }

    /**
     * Ses signalements restent si le compte est supprimé, sans auteur (règle 19).
     *
     * @return HasMany<Signalement, $this>
     */
    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class, 'auteur_id');
    }
}
