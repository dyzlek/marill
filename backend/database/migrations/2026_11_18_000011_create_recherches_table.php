<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RECHERCHE (règle 12, M8) : seulement celles des membres. L'adresse trouvée n'est pas stockée :
 * la même recherche la redonne. Les visiteurs ne laissent qu'un compteur (activites_jour).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recherches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // règle 19
            $table->string('texte', 3200); // normalisé
            $table->enum('mode', ['exact', 'bruit']);
            $table->enum('portee', ['locale', 'reseau']);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recherches');
    }
};
