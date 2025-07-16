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
        Schema::create('request_for_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('npk');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->date('input_date');
            $table->text('description');
            $table->enum('status', ['wait_for_review', 'in_progress','accepted','finish','rejected'])->default('wait_for_review');
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
