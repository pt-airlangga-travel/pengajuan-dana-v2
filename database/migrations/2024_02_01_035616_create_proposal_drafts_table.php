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
        Schema::create('proposal_drafts', function (Blueprint $table) {
            $table->id();
            $table->char('proposal_draft_defined_id', 17)->nullable(false)->unique();
            $table->char('proposal_draft_index', 5)->nullable(false);
            // $table->string('proposal_draft_total_price')->nullable(false);
            $table->string('proposal_draft_note_admin')->default('not set');
            $table->string('proposal_draft_note_member')->default('not set');
            $table->tinyInteger('proposal_draft_status')->default(2)->comment('0 tolak, 1 terima, 2 menunggu, 3 di ajukan');
            $table->date('proposal_draft_deadline_payment');
            $table->string('file_attached_name');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_drafts');
    }
};
