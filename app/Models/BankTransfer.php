<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransfer extends Model
{
    use HasFactory;
    protected $primaryKey =  'bank_transfer_defined_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['id'];

    public function BankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'id_bank_account', 'id');
    }

    public function BankAsal(): BelongsTo
    {
        return $this->belongsTo(BankAsal::class, 'id_bank_asal');
    }

    public function eagleTreasurer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eagle_treasurer');
    }
}
