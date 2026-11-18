<?php

use App\Models\Reglage;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as ProfilGithub;

/**
 * Connexion avec GitHub (issue #4). On ne parle jamais à GitHub dans les tests : Socialite::fake()
 * remplace le profil renvoyé au retour.
 */
function profilGithub(string $id = '4242', ?string $email = 'ada@example.com', string $pseudo = 'ada'): ProfilGithub
{
    return (new ProfilGithub)->map(['id' => $id, 'nickname' => $pseudo, 'name' => 'Ada', 'email' => $email]);
}

beforeEach(function () {
    config(['app.frontend_url' => 'http://localhost:4200']);
});

it('envoie le navigateur chez GitHub', function () {
    Socialite::fake('github');

    $this->get('/auth/github/redirect')->assertRedirect();
});

it('crée un membre au premier retour et ouvre sa session', function () {
    Socialite::fake('github', profilGithub());

    $this->get('/auth/github/callback')->assertRedirect('http://localhost:4200');

    $user = User::query()->where('github_id', '4242')->sole();
    expect($user->pseudo)->toBe('ada')
        ->and($user->email)->toBe('ada@example.com')
        ->and($user->password)->toBeNull()
        ->and($user->derniere_connexion_le)->not->toBeNull();
    $this->assertAuthenticatedAs($user, 'web');
});

it('retrouve le membre par son identifiant GitHub, même si son email a changé', function () {
    $user = User::factory()->create();
    $user->forceFill(['github_id' => '4242'])->save();
    Socialite::fake('github', profilGithub(email: 'nouvel-email@example.com'));

    $this->get('/auth/github/callback')->assertRedirect('http://localhost:4200');

    $this->assertAuthenticatedAs($user, 'web');
    expect(User::count())->toBe(1);
});

it('ne relie pas un compte existant par son email', function () {
    User::factory()->create(['email' => 'ada@example.com']);
    Socialite::fake('github', profilGithub());

    $this->get('/auth/github/callback')
        ->assertRedirect('http://localhost:4200/connexion?erreur=ERR_OAUTH_EMAIL_TAKEN');

    $this->assertGuest('web');
    expect(User::query()->whereNotNull('github_id')->exists())->toBeFalse();
});

it('refuse un profil GitHub sans email', function () {
    Socialite::fake('github', profilGithub(email: null));

    $this->get('/auth/github/callback')
        ->assertRedirect('http://localhost:4200/connexion?erreur=ERR_OAUTH_NO_EMAIL');
    $this->assertGuest('web');
});

it('refuse un nouveau compte quand les inscriptions sont fermées', function () {
    Reglage::query()->create(['cle' => Reglage::INSCRIPTIONS_OUVERTES, 'valeur' => '0']);
    Socialite::fake('github', profilGithub());

    $this->get('/auth/github/callback')
        ->assertRedirect('http://localhost:4200/connexion?erreur=ERR_REGISTRATION_CLOSED');
    expect(User::count())->toBe(0);
});

it('refuse un compte suspendu', function () {
    $user = User::factory()->create();
    $user->forceFill(['github_id' => '4242', 'suspendu_le' => now()])->save();
    Socialite::fake('github', profilGithub());

    $this->get('/auth/github/callback')
        ->assertRedirect('http://localhost:4200/connexion?erreur=ERR_ACCOUNT_SUSPENDED');
    $this->assertGuest('web');
});

it('prend un autre pseudo si celui de GitHub est déjà pris', function () {
    User::factory()->create(['pseudo' => 'ada']);
    Socialite::fake('github', profilGithub());

    $this->get('/auth/github/callback');

    expect(User::query()->where('github_id', '4242')->sole()->pseudo)->toStartWith('ada-');
});

it('refuse un retour dont le state ne correspond pas', function () {
    $faux = Mockery::mock(GithubProvider::class);
    $faux->shouldReceive('user')->andThrow(new InvalidStateException);
    Socialite::shouldReceive('driver')->with('github')->andReturn($faux);

    $this->get('/auth/github/callback')
        ->assertRedirect('http://localhost:4200/connexion?erreur=ERR_OAUTH_STATE');
    $this->assertGuest('web');
});

it("ne laisse pas un membre GitHub se connecter par mot de passe (il n'en a pas)", function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'password' => null]);
    $user->forceFill(['github_id' => '4242'])->save();

    $this->postJson('/login', ['email' => 'ada@example.com', 'password' => 'nimporte'])
        ->assertUnprocessable();
    $this->assertGuest('web');
});
