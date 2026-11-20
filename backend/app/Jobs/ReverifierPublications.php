<?php

namespace App\Jobs;

use App\Babel\Moderation;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Étape 2 du CDC (« Comment un texte banni masque ce qui est publié ») : relire les pages désignées par
 * ce qui est déjà publié, et marquer celles qui contiennent un texte banni appliqué.
 *
 * En file d'attente (QUEUE_CONNECTION=database, lancer « php artisan queue:work ») : relire des centaines
 * de pages prend du temps, l'administrateur n'attend pas la fin pour voir sa décision enregistrée.
 * ShouldBeUnique : dix décisions de suite ne lancent pas dix relectures complètes en même temps.
 *
 * Le moteur (via Moderation) est injecté dans handle(), jamais dans le constructeur : une tâche est
 * sérialisée pour aller en file, et le moteur refuse de l'être (il porte la clé).
 */
class ReverifierPublications implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(Moderation $moderation): void
    {
        $moderation->toutReverifier();
    }
}
