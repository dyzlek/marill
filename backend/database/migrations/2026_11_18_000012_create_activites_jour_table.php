<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** ACTIVITE_JOUR (règle 21, A11) : des compteurs par jour, pas une ligne par page lue. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activites_jour', function (Blueprint $table) {
            $table->date('jour')->primary();
            $table->unsignedInteger('pages_lues')->default(0);
            $table->unsignedInteger('recherches')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activites_jour');
    }
};
