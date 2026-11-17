<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COLLECTION_PAGE (règle 8) : table pivot entre collections et adresses, ordonnée.
 * Clé primaire composée (collection_id, position), comme au MLD : pas d'id. C'est la position qui
 * est unique, pas l'adresse : une même page peut revenir deux fois dans une collection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_pages', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->foreignId('adresse_id')->constrained()->restrictOnDelete();

            $table->primary(['collection_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_pages');
    }
};
