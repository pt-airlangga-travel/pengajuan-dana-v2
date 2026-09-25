<?php

namespace App\Models;

use App\Models\Bank;
use App\Models\BankTransfer;
use App\Models\ProposalSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccount extends Model
{
    use HasFactory;
    protected $guarded=['id'];
    /**
     * Get the Bank that owns the BankAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
  
    /**
     * Get the ProposalSubmission that owns the BankAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ProposalSubmission()
    {
        return $this->belongsTo(ProposalSubmission::class, 'id_proposal_submission');
    }
    
    /**
     * Get the Bank that owns the BankAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Bank()
    {
        return $this->belongsTo(Bank::class, 'id_bank','bank_defined_id');
    }

    /**
     * Get the BankTransfer associated with the BankAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function BankTransfer(): HasOne
    {
        return $this->hasOne(BankTransfer::class, 'id_bank_account', 'id');
    }

}
