<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TEXTE_BANNI (règles 16 à 18) : le texte en clair, normalisé, pour pouvoir le chercher ;
 * l'empreinte (SHA-256 du texte) n'est là que pour refuser les doublons.
 * Créée avant ADRESSE, qui pointe vers elle (texte_banni_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('textes_bannis', function (Blueprint $table) {
            $table->id();
            $table->string('texte', 3200);
            $table->char('empreinte', 64)->unique();
            $table->text('motif')->nullable();
            // Bibliothèque qui l'a banni en premier. restrict : on ne supprime pas une bibliothèque
            // tant que des textes bannis la citent.
            $table->foreignId('origine_id')->constrained('bibliotheques')->restrictOnDelete();
            $table->enum('statut', ['propose', 'applique', 'rejete']);
            $table->timestamp('decide_le')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('textes_bannis');
    }
};
