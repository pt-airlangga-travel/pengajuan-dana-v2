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
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->char('institution_defined_id',3)->nullable(false)->unique();
            $table->string('institution_name');
            $table->string('institution_name_slug');
            $table->string('institution_contact')->default("not set");
            $table->string('institution_email')->default("not set");
            $table->boolean('institution_active_status')->nullable(false)->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
