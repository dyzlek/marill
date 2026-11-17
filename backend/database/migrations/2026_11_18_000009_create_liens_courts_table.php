<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LIEN_COURT (règle 11) : exactement une cible, une adresse (page ou livre) ou une collection.
 * Créé sans compte, il n'a pas d'auteur et il expire obligatoirement. Un passage ne va qu'avec une adresse.
 *
 * Laravel n'a pas de ->check() : les trois contraintes CHECK s'écrivent dans la définition d'une
 * colonne (rawColumn) ; SQLite accepte qu'une telle contrainte lise les autres colonnes de la ligne.
 * Elles portent un nom (constraint …) pour que l'erreur dise quelle règle est violée.
 * Le modèle LienCourt vérifie les mêmes règles avant d'écrire, avec un message en français.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liens_courts', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 8)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // nul = visiteur ; règle 19
            $table->rawColumn('collection_id', 'integer constraint lien_court_une_seule_cible check ('
                .'(case when adresse_id is null then 0 else 1 end)'
                .' + (case when collection_id is null then 0 else 1 end) = 1)')->nullable();
            $table->foreignId('adresse_id')->nullable()->constrained()->restrictOnDelete();
            $table->rawColumn('passage', 'varchar(3200) constraint lien_court_passage_vers_une_adresse check ('
                .'passage is null or adresse_id is not null)')->nullable();
            $table->unsignedInteger('visites')->default(0);
            $table->rawColumn('expire_le', 'datetime constraint lien_court_visiteur_expire check ('
                .'user_id is not null or expire_le is not null)')->nullable();
            $table->timestamp('masque_le')->nullable();
            $table->timestamp('created_at')->nullable();

            // La colonne collection_id est écrite à la main : sa clé étrangère se déclare à part.
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liens_courts');
    }
};
