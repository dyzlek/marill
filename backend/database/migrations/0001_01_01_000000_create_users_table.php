<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Entité UTILISATEUR du MLD (docs/correspondance-mld.md) : `pseudo` remplace le `name`
            // du starter kit, `password` et created_at / updated_at gardent leurs noms Laravel.
            $table->id();
            $table->string('pseudo', 50)->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // Valeurs écrites en dur : une migration décrit la base à un instant donné,
            // elle ne doit pas changer si l'enum PHP App\Enums\Role évolue plus tard.
            $table->enum('role', ['membre', 'admin'])->default('membre');
            $table->timestamp('suspendu_le')->nullable();
            $table->timestamp('derniere_connexion_le')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
