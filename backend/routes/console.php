<?php

use App\Jobs\ReverifierPublications;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Étape 2 de la modération (#35) : relire chaque nuit les pages désignées par ce qui est publié, pour
// rattraper un texte banni appliqué pendant que la file était arrêtée, et plus tard les pages des pairs.
// En production : une tâche cron « php artisan schedule:run » chaque minute (doc Laravel, Task Scheduling).
Schedule::job(new ReverifierPublications)->daily();

// Liens courts de visiteurs expirés : on les efface chaque nuit (règle 11, ils ne mènent plus nulle part).
Schedule::command('liens-courts:nettoyer')->daily();
