<?php

namespace App\Models;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bank extends Model
{
    use HasFactory;
    protected $primaryKey = 'bank_defined_id';
    protected $guarded = ['id'];
    public $incrementing = false;
    protected $keyType = 'string';
    /**
     * Get all of the BankAccounts for the Bank
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function BankAccounts():HasMany
    {
        return $this->hasMany(BankAccount::class, 'id_bank', 'bank_defined_id');
    }
  
    
}
