<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Connexion avec GitHub (issue #4).
 *
 * github_id : l'identifiant stable du compte GitHub. On rapproche par lui, jamais par l'email,
 * qui peut changer de propriétaire. unique() : un compte GitHub ne se relie qu'à un seul membre.
 *
 * password devient nullable : un membre venu par GitHub n'a pas de mot de passe. Il ne peut alors
 * se connecter que par GitHub (VerifierIdentifiants refuse un mot de passe absent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('github_id')->nullable()->unique()->after('email');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['github_id']);
            $table->dropColumn('github_id');
            $table->string('password')->nullable(false)->change();
        });
    }
};
