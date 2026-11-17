<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BIBLIOTHEQUE (règles 5 et 6) : notre bibliothèque est une ligne comme les autres (est_locale),
 * une bibliothèque a un identifiant stable et une URL qui change. Aucune date de création au MLD.
 * Ni liste blanche ni liste noire : le réseau est ouvert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bibliotheques', function (Blueprint $table) {
            $table->id();
            $table->string('identifiant', 40)->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('url_actuelle');
            $table->boolean('est_locale')->default(false);
            $table->enum('etat', ['en_ligne', 'hors_ligne', 'inconnu'])->default('inconnu');
            $table->unsignedInteger('echecs_consecutifs')->default(0);
            $table->timestamp('dernier_contact_le')->nullable();
            $table->timestamp('vue_annuaire_le')->nullable();
            $table->char('empreinte_cle', 64)->nullable(); // seulement l'empreinte publiée (B6), jamais la clé
            $table->timestamp('cle_changee_le')->nullable();
        });

        // Règle 5 : une seule ligne est_locale à vrai. Index unique « partiel » : il ne porte que sur
        // les lignes où est_locale est vrai, donc deux lignes vraies se heurtent, les fausses jamais.
        // Le schema builder de Laravel ne sait pas écrire le WHERE d'un index : SQL à la main
        // (accepté par SQLite et PostgreSQL ; MySQL n'a pas d'index partiel).
        DB::statement('CREATE UNIQUE INDEX bibliotheques_une_seule_locale ON bibliotheques (est_locale) WHERE est_locale');
    }

    public function down(): void
    {
        Schema::dropIfExists('bibliotheques');
    }
};
