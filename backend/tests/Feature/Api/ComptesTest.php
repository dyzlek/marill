<?php

/*
 * Issue #16 (V19, M1) : inscription, connexion et déconnexion par les routes de Fortify en JSON
 * (comme les enverra le front Angular), session Sanctum pour le front, jetons pour les clients extérieurs.
 */

use App\Enums\Role;
use App\Models\Reglage;
use App\Models\User;

/** En-têtes du front Angular : JSON, et l'origine qui le rend « stateful » pour Sanctum (cookie de session). */
function depuisLeFront(): array
{
    return ['Accept' => 'application/json', 'Origin' => 'http://localhost:4200', 'Referer' => 'http://localhost:4200/'];
}

describe('V19 : inscription', function () {
    test('crée un membre avec pseudo, email et mot de passe, et le connecte', function () {
        $this->postJson('/register', [
            'pseudo' => 'borges',
            'email' => 'Borges@Example.com',
            'password' => 'un mot de passe',
            'password_confirmation' => 'un mot de passe',
        ], depuisLeFront())->assertCreated();

        $user = User::query()->sole();
        expect($user->pseudo)->toBe('borges')
            ->and($user->email)->toBe('borges@example.com') // rangé en minuscules : la connexion le retrouve
            ->and($user->role)->toBe(Role::Membre)
            ->and($user->password)->not->toBe('un mot de passe'); // haché
        $this->assertAuthenticatedAs($user);
    });

    test('le pseudo est unique', function () {
        User::factory()->create(['pseudo' => 'borges']);

        $this->postJson('/register', ['pseudo' => 'borges', 'email' => 'autre@example.com',
            'password' => 'password', 'password_confirmation' => 'password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pseudo');
    });

    test('le pseudo fait au plus 50 caractères', function () {
        $this->postJson('/register', ['pseudo' => str_repeat('a', 51), 'email' => 'a@example.com',
            'password' => 'password', 'password_confirmation' => 'password'])
            ->assertJsonValidationErrors('pseudo');
    });

    test('on ne devient pas administrateur en ajoutant role à la requête', function () {
        $this->postJson('/register', ['pseudo' => 'malin', 'email' => 'malin@example.com', 'role' => 'admin',
            'password' => 'password', 'password_confirmation' => 'password'])
            ->assertCreated();

        expect(User::query()->sole()->isAdmin())->toBeFalse();
    });

    test('A8 : inscriptions fermées, 403 au format commun et aucun compte créé', function () {
        Reglage::query()->create(['cle' => Reglage::INSCRIPTIONS_OUVERTES, 'valeur' => '0']);

        $this->postJson('/register', ['pseudo' => 'borges', 'email' => 'b@example.com',
            'password' => 'password', 'password_confirmation' => 'password'])
            ->assertForbidden()
            ->assertExactJson(['erreurs' => [[
                'code' => 'ERR_REGISTRATION_CLOSED',
                'champ' => null,
                'message' => 'Les inscriptions sont fermées sur cette bibliothèque.',
            ]]]);

        expect(User::query()->count())->toBe(0);
        $this->assertGuest();
    });
});

describe('M1 : connexion et déconnexion', function () {
    test('connexion par email et mot de passe, date de dernière connexion notée', function () {
        $user = User::factory()->create(['email' => 'borges@example.com', 'updated_at' => now()->subDay()]);

        $this->postJson('/login', ['email' => 'Borges@example.com', 'password' => 'password'], depuisLeFront())
            ->assertOk();

        $this->assertAuthenticatedAs($user);
        $user->refresh();
        expect($user->derniere_connexion_le)->not->toBeNull()
            ->and($user->updated_at?->isToday())->toBeFalse(); // se connecter ne modifie pas le profil
    });

    test('un mauvais mot de passe est refusé', function () {
        $user = User::factory()->create();

        $this->postJson('/login', ['email' => $user->email, 'password' => 'faux'])
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    });

    test('A2 : un compte suspendu ne se connecte pas', function () {
        $user = User::factory()->create();
        $user->forceFill(['suspendu_le' => now()])->save();

        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertJsonValidationErrors(['email' => 'suspendu']);

        $this->assertGuest();
    });

    test('déconnexion', function () {
        $this->actingAs(User::factory()->create());

        $this->postJson('/logout', [], depuisLeFront())->assertNoContent();

        $this->assertGuest('web');
    });
});

describe('GET /api/user', function () {
    test('401 sans session ni jeton', function () {
        $this->getJson('/api/user')->assertUnauthorized();
    });

    test('le parcours du front : cookie CSRF, connexion, puis qui suis-je', function () {
        $user = User::factory()->create(['pseudo' => 'borges']);

        $this->get('/sanctum/csrf-cookie', depuisLeFront())->assertNoContent()->assertCookie('XSRF-TOKEN');
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password'], depuisLeFront())->assertOk();

        $this->getJson('/api/user', depuisLeFront())
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => $user->id,
                'pseudo' => 'borges',
                'email' => $user->email,
                'role' => 'membre',
            ]]); // ni mot de passe, ni secrets : la Resource choisit les champs
    });
});

describe('jetons des clients extérieurs', function () {
    test('un jeton se crée avec email et mot de passe, et ouvre /api/user', function () {
        $user = User::factory()->create();

        $jeton = $this->postJson('/api/tokens', ['email' => $user->email, 'password' => 'password', 'nom' => 'bruno'])
            ->assertCreated()
            ->json('data.jeton');

        expect($jeton)->toBeString();
        $this->getJson('/api/user', ['Authorization' => "Bearer {$jeton}"])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    });

    test('même refus vague pour un mauvais mot de passe et pour un compte suspendu', function (bool $suspendu) {
        $user = User::factory()->create();
        if ($suspendu) {
            $user->forceFill(['suspendu_le' => now()])->save();
        }

        $this->postJson('/api/tokens', ['email' => $user->email, 'password' => $suspendu ? 'password' : 'faux', 'nom' => 'x'])
            ->assertUnauthorized()
            ->assertExactJson(['erreurs' => [['code' => 'ERR_INVALID_CREDENTIALS', 'champ' => null, 'message' => 'Identifiants incorrects.']]]);

        expect($user->tokens()->count())->toBe(0);
    })->with(['mauvais mot de passe' => false, 'compte suspendu' => true]);

    test('au-delà de 5 essais par minute : 429', function () {
        foreach (range(1, 5) as $essai) {
            $this->postJson('/api/tokens', ['email' => 'x@example.com', 'password' => 'faux', 'nom' => 'x'])->assertUnauthorized();
        }

        $this->postJson('/api/tokens', ['email' => 'x@example.com', 'password' => 'faux', 'nom' => 'x'])
            ->assertTooManyRequests()
            ->assertJsonPath('erreurs.0.code', 'ERR_TOO_MANY_REQUESTS');
    });

    test('un jeton se révoque', function () {
        $user = User::factory()->create();
        $jeton = $user->createToken('bruno')->plainTextToken;

        $this->deleteJson('/api/tokens/courant', [], ['Authorization' => "Bearer {$jeton}"])->assertNoContent();

        expect($user->tokens()->count())->toBe(0);
    });
});
