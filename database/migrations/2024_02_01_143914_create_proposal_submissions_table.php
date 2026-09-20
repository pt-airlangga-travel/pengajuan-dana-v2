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
        Schema::create('proposal_submissions', function (Blueprint $table) {
            $table->id();
            $table->char('proposal_submission_defined_id', 17)->unique();
            $table->char('proposal_submission_index', 5);
            $table->string('proposal_submission_event_identity');
            $table->string('proposal_submission_booking_code')->default('not set');
            $table->string('proposal_submission_note_manager')->default('not set');
            $table->tinyInteger('proposal_submission_status')->default(2)->comment('0 tolak, 1 selesai , 2 menunggu, 3 proses tf, 4 kembalikan ke user');
            $table->date('checked_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_submissions');
    }
};
