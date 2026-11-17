<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADRESSE (règles 1 à 4) : une page ou un livre désigné par quelqu'un. Jamais le texte de la page :
 * le moteur le recalcule. Toutes les tables qui désignent une page pointent ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bibliotheque_id')->constrained('bibliotheques')->restrictOnDelete();
            $table->string('hexagone', 3004); // base 36, jusqu'à 3 004 caractères : pas d'index dessus
            $table->unsignedTinyInteger('mur');
            $table->unsignedTinyInteger('etagere');
            $table->unsignedTinyInteger('volume');
            $table->unsignedSmallInteger('page')->nullable(); // nul = le livre entier (règle 4)
            $table->char('empreinte', 64); // SHA-256 de l'adresse écrite sous sa forme valide
            $table->char('empreinte_livre', 64)->index(); // SHA-256 de l'adresse sans la page (règle 3)
            // Règle 18 : marque l'adresse dont la page contient un texte banni appliqué.
            // Si le texte banni est supprimé, la marque tombe (null), l'adresse reste.
            $table->foreignId('texte_banni_id')->nullable()->constrained('textes_bannis')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            // Règle 2 : une adresse n'est enregistrée qu'une fois par bibliothèque. Un index sur
            // l'hexagone (3 004 caractères) serait refusé : l'unicité passe par l'empreinte.
            $table->unique(['bibliotheque_id', 'empreinte']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adresses');
    }
};
