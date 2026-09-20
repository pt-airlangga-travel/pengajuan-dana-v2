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
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->char('event_defined_id', 12)->nullable(false)->unique();
            $table->string('event_name', 100)->nullable(false);
            $table->string('event_short_name', 12)->nullable(false);
            $table->string('event_name_slug', 12)->nullable(false);
            $table->string('event_date', 20)->nullable(false);
            $table->string('event_month', 20)->nullable(false);
            $table->string('event_year', 20)->nullable(false);
            $table->date('event_started_at')->nullable(false);
            $table->date('event_finished_at')->nullable(false);
            $table->boolean('event_availability')->nullable(false)->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
