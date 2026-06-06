<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['http', 'tcp', 'ping']);
            $table->string('target');
            $table->integer('port')->nullable();
            $table->integer('interval')->default(60);
            $table->integer('timeout')->default(10);
            $table->boolean('active')->default(true);
            $table->enum('status', ['up', 'down', 'unknown'])->default('unknown');
            $table->integer('latency')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('discord_webhook')->nullable();
            $table->timestamps();
        });

        Schema::create('monitor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['up', 'down']);
            $table->integer('latency')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_logs');
        Schema::dropIfExists('monitors');
    }
};
