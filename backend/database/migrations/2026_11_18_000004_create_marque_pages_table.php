<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** MARQUE_PAGE (règle 7) : privé, une seule fois la même page par membre. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marque_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // règle 19
            $table->foreignId('adresse_id')->constrained()->restrictOnDelete();
            $table->string('titre', 120)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'adresse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marque_pages');
    }
};
