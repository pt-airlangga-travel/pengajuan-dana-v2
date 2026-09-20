<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    use HasFactory;
   protected $guarded = ['id'];
   /**
    * Get the ProposalDraft that owns the Vendor
    *
    * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    */
   public function ProposalDraft(): BelongsTo
   {
       return $this->belongsTo(ProposalDraft::class, 'id_proposal_draft');
   }
}
