<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REGLAGE (règle 22) : paires clé/valeur (inscriptions ouvertes, quota de l'API publique).
 * Jamais la clé du moteur ni le jeton réseau : ils restent dans la configuration du serveur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglages', function (Blueprint $table) {
            $table->string('cle', 60)->primary();
            $table->text('valeur');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglages');
    }
};
