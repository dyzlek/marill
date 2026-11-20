<?php

/*
 * Issue #17 (M9, M10, règle 19) : modifier son pseudo, son email ou son mot de passe ; supprimer son compte.
 */

use App\Models\Annotation;
use App\Models\Bibliotheque;
use App\Models\MarquePage;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Bibliotheque::factory()->locale()->create();
    $this->membre = User::factory()->create(['pseudo' => 'lectrice', 'email' => 'lectrice@example.com']);
});

test('401 sans être connecté', function () {
    $this->patchJson('/api/user', ['pseudo' => 'x'])->assertUnauthorized();
    $this->deleteJson('/api/user')->assertUnauthorized();
});

describe('M9 : modifier son compte', function () {
    test('changer de pseudo sans toucher à l\'email', function () {
        $this->actingAs($this->membre, 'sanctum')
            ->patchJson('/api/user', ['pseudo' => 'liseuse'])
            ->assertOk()
            ->assertJsonPath('data.pseudo', 'liseuse')
            ->assertJsonPath('data.email', 'lectrice@example.com');
    });

    test('pseudo ou email déjà pris : 422 ; garder les siens est permis', function () {
        User::factory()->create(['pseudo' => 'pris', 'email' => 'pris@example.com']);

        $this->actingAs($this->membre, 'sanctum')->patchJson('/api/user', ['pseudo' => 'pris'])->assertUnprocessable();
        $this->actingAs($this->membre, 'sanctum')->patchJson('/api/user', ['email' => 'pris@example.com'])->assertUnprocessable();
        $this->actingAs($this->membre, 'sanctum')->patchJson('/api/user', ['pseudo' => 'lectrice'])->assertOk();
    });

    test('on ne devient pas admin par ce formulaire', function () {
        $this->actingAs($this->membre, 'sanctum')->patchJson('/api/user', ['role' => 'admin'])->assertOk();

        expect($this->membre->fresh()?->isAdmin())->toBeFalse();
    });

    test('changer de mot de passe demande l\'ancien et coupe les jetons', function () {
        $this->membre->createToken('bruno');

        $this->actingAs($this->membre, 'sanctum')
            ->putJson('/api/user/password', ['current_password' => 'faux', 'password' => 'nouveau-mdp', 'password_confirmation' => 'nouveau-mdp'])
            ->assertUnprocessable();

        $this->actingAs($this->membre, 'sanctum')
            ->putJson('/api/user/password', ['current_password' => 'password', 'password' => 'nouveau-mdp', 'password_confirmation' => 'nouveau-mdp'])
            ->assertNoContent();

        expect(Hash::check('nouveau-mdp', (string) $this->membre->fresh()?->password))->toBeTrue()
            ->and($this->membre->tokens()->count())->toBe(0);
    });

    test('un compte GitHub sans mot de passe en choisit un sans ancien', function () {
        $github = User::factory()->create(['password' => null]);

        $this->actingAs($github, 'sanctum')
            ->putJson('/api/user/password', ['password' => 'nouveau-mdp', 'password_confirmation' => 'nouveau-mdp'])
            ->assertNoContent();
    });
});

describe('M10 : supprimer son compte', function () {
    test('mot de passe requis, puis tout est effacé sauf les signalements, sans auteur (règle 19)', function () {
        MarquePage::factory()->for($this->membre)->create();
        Annotation::factory()->for($this->membre)->create();
        $signalement = Signalement::factory()->for(Annotation::factory()->publique())->create();
        $signalement->auteur()->associate($this->membre)->save();

        $this->actingAs($this->membre, 'sanctum')->deleteJson('/api/user', ['current_password' => 'faux'])->assertUnprocessable();
        $this->actingAs($this->membre, 'sanctum')->deleteJson('/api/user', ['current_password' => 'password'])->assertNoContent();

        expect(User::query()->find($this->membre->id))->toBeNull()
            ->and(MarquePage::query()->count())->toBe(0)
            ->and(Annotation::query()->where('user_id', $this->membre->id)->count())->toBe(0)
            ->and($signalement->fresh()?->auteur_id)->toBeNull();
    });

    test('un compte GitHub confirme en retapant son pseudo', function () {
        $github = User::factory()->create(['pseudo' => 'octo', 'password' => null]);

        $this->actingAs($github, 'sanctum')->deleteJson('/api/user', ['pseudo' => 'autre'])->assertUnprocessable();
        $this->actingAs($github, 'sanctum')->deleteJson('/api/user', ['pseudo' => 'octo'])->assertNoContent();
    });
});
