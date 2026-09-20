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
        //users
        Schema::table('users', function (Blueprint $table) {
            $table->char('id_role', 3);
            $table->char('id_division',3);

            $table->foreign('id_role')->references('role_defined_id')->on('roles');
            $table->foreign('id_division')->references('division_defined_id')->on('divisions');
        });

        //events
        Schema::table('events', function (Blueprint $table) {
            $table->char('id_institution', 3);

            $table->foreign('id_institution')->references('institution_defined_id')->on('institutions');
        });

        //proposal draft
        Schema::table('proposal_drafts', function (Blueprint $table) {
            $table->char('id_event',12);
            $table->unsignedBigInteger('creative_member');
            $table->unsignedBigInteger('organizer_admin')->nullable();
   
            $table->foreign('id_event')->references('event_defined_id')->on('events');
            $table->foreign('creative_member')->references('id')->on('users');
            $table->foreign('organizer_admin')->references('id')->on('users');
        });

        Schema::table('proposal_submissions', function (Blueprint $table) {
            $table->char('id_proposal_draft',17);
            $table->unsignedBigInteger('organizer_admin');
            $table->unsignedBigInteger('inspiring_manager')->nullable();

   
            $table->foreign('id_proposal_draft')->references('proposal_draft_defined_id')->on('proposal_drafts');
            $table->foreign('inspiring_manager')->references('id')->on('users');
            $table->foreign('organizer_admin')->references('id')->on('users');
        });
        Schema::table('vendors', function (Blueprint $table) {
            $table->char('id_proposal_draft',17);
            $table->foreign('id_proposal_draft')->references('proposal_draft_defined_id')->on('proposal_drafts');
        });
        Schema::table('need_submissions', function (Blueprint $table) {
            $table->char('id_need',3);
            $table->char('id_proposal_submission',17);

            $table->foreign('id_proposal_submission')->references('proposal_submission_defined_id')->on('proposal_submissions');
            $table->foreign('id_need')->references('need_defined_id')->on('needs');

        });
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->char('id_bank', 3);
            $table->char('id_proposal_submission', 17);

            $table->foreign('id_bank')->references('bank_defined_id')->on('banks');
            $table->foreign('id_proposal_submission')->references('proposal_submission_defined_id')->on('proposal_submissions');
        });
        Schema::table('bank_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('id_bank_account');
            $table->unsignedBigInteger('id_bank_asal')->nullable();
            $table->unsignedBigInteger('eagle_treasurer');
            
            $table->foreign('eagle_treasurer')->references('id')->on('users');
            $table->foreign('id_bank_account')->references('id')->on('bank_accounts');
            // $table->foreign('id_bank_asal')->references('bank_defined_id')->on('banks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //users
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_role']); 
            $table->dropColumn('id_role'); 

            $table->dropForeign(['id_division']); 
            $table->dropColumn('id_division'); 
        });

        //events
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_institution']); 
            $table->dropColumn('id_institution'); 
        });

        //proposal draft
        Schema::table('proposal_drafts', function (Blueprint $table) {
            $table->dropForeign(['id_event']); 
            $table->dropColumn('id_event'); 

            $table->dropForeign(['id_division']); 
            $table->dropColumn('id_division'); 

            $table->dropForeign(['id_user']); 
            $table->dropColumn('id_user'); 
        });

    }
};
