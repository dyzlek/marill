<?php

namespace App\Console\Commands;

use App\Models\LienCourt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Règle 11 : un lien court créé sans compte expire. Une fois expiré, il répond 410 et ne mènera plus jamais
 * nulle part : on l'efface (ses signalements partent avec lui, cascade). Lancée chaque nuit (routes/console.php).
 *
 * Les liens des membres n'expirent pas (expire_le nul) : ils ne sont jamais touchés ici.
 */
#[Signature('liens-courts:nettoyer')]
#[Description('Efface les liens courts de visiteurs expirés')]
class NettoyerLiensCourts extends Command
{
    public function handle(): int
    {
        $effaces = LienCourt::query()->whereNotNull('expire_le')->where('expire_le', '<', now())->delete();

        $this->info("$effaces lien(s) court(s) expiré(s) effacé(s).");

        return self::SUCCESS;
    }
}
