<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_databases', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id');
            $table->string('db_name');
            $table->string('db_user');
            $table->text('db_password');
            $table->timestamps();
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_databases');
    }
};
