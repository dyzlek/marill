<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VOTE (règle 10) : table pivot entre users et adresses, un vote par membre et par page.
 * Clé primaire composée (user_id, adresse_id), comme au MLD : c'est elle qui interdit le second vote.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // règle 19
            $table->foreignId('adresse_id')->constrained()->restrictOnDelete();
            // +1 ou −1. Laravel n'a pas de ->check() : la contrainte CHECK s'écrit à la main dans la
            // définition de la colonne (rawColumn). C'est ce que Laravel fait lui-même pour enum() sous SQLite.
            $table->rawColumn('valeur', 'integer constraint vote_plus_ou_moins_un check (valeur in (-1, 1))');
            // Une table pivot n'est pas datée par Eloquent comme un modèle : la base date le vote.
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_id', 'adresse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
