<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * UTILISATEUR.derniere_connexion_le (A1 : la liste des comptes la montre à l'administrateur).
 *
 * Laravel lance l'événement Login à chaque connexion par session, quelle que soit la porte
 * (formulaire Blade, JSON d'Angular, double authentification). Un écouteur s'y branche sans toucher
 * aux contrôleurs de Fortify. Laravel le trouve tout seul : classe dans app/Listeners, méthode handle()
 * typée avec l'événement.
 */
class NoterDerniereConnexion
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            // forceFill : la colonne n'est pas dans #[Fillable]. withoutTimestamps : updated_at ne bouge pas,
            // se connecter n'est pas modifier son profil. saveQuietly : pas d'événements de modèle pour une date.
            User::withoutTimestamps(fn () => $user->forceFill(['derniere_connexion_le' => now()])->saveQuietly());
        }
    }
}
