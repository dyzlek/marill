<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** ANNOTATION (règle 9) : au plus 2 000 caractères, publique ou privée au choix de son auteur. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // règle 19
            $table->foreignId('adresse_id')->constrained()->restrictOnDelete();
            // SQLite ne vérifie pas la longueur d'un VARCHAR : la limite de 2 000 est tenue par la
            // validation (FormRequest) ; la colonne la documente et la ferait respecter ailleurs.
            $table->string('contenu', 2000);
            $table->boolean('est_publique'); // pas de valeur par défaut : c'est le choix du membre
            $table->timestamp('masque_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annotations');
    }
};
