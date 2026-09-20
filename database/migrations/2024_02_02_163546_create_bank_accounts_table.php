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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('bank_account_revised')->default(0);
            $table->string('bank_account_sub_total');
            $table->string('bank_account_owner', 150);
            $table->string('bank_account_number', 20);
            $table->boolean('status_revised')->default(false);
            $table->text('alasan_revised')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
