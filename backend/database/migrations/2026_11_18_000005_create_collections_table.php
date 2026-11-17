<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** COLLECTION (M4, M5) : privée par défaut, masquée en datant masque_le (règle 14). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // règle 19
            $table->string('nom', 120);
            $table->text('description')->nullable();
            $table->boolean('est_publique')->default(false);
            $table->timestamp('masque_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
