<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::create('request_for_services', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->string('npk');
        //     $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
        //     $table->text('description');
        //     $table->enum('status', ['wait_for_review', 'in_progress', 'accepted', 'finish', 'rejected'])->default('wait_for_review');
        //     $table->text('impact')->nullable();
        //     $table->string('attachment')->nullable();
        //     $table->timestamps();
        // });
        Schema::create('request_for_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('npk');
            $table->foreignId('priority_id')->constrained('priorities')->restrictOnDelete();
            $table->text('description');
            $table->foreignId('status_id')->constrained('statuses')->restrictOnDelete();
            $table->text('impact')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_for_services');
    }
};
