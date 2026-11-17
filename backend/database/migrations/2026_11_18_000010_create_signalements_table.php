<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SIGNALEMENT (règles 13 à 15) : vise exactement un contenu publié (annotation, collection ou
 * lien court), par une vraie clé étrangère, jamais une adresse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signalements', function (Blueprint $table) {
            $table->id();
            // Règle 13 : si le contenu signalé est supprimé, ses signalements partent avec lui.
            $table->foreignId('annotation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->cascadeOnDelete();
            // Même procédé que pour liens_courts : CHECK dans la définition de la colonne.
            $table->rawColumn('lien_court_id', 'integer constraint signalement_une_seule_cible check ('
                .'(case when annotation_id is null then 0 else 1 end)'
                .' + (case when collection_id is null then 0 else 1 end)'
                .' + (case when lien_court_id is null then 0 else 1 end) = 1)')->nullable();
            // Règle 19 : supprimer un compte garde ses signalements, sans auteur. Nul aussi pour un visiteur.
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motif');
            $table->enum('statut', ['en_attente', 'traite', 'rejete'])->default('en_attente');
            $table->foreignId('traite_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('lien_court_id')->references('id')->on('liens_courts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
