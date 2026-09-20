<?php

namespace App\Models;

use App\Models\Event;
use App\Models\Vendor;
use App\Models\ProposalSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProposalDraft extends Model
{
    use HasFactory;
    protected $primaryKey =  'proposal_draft_defined_id';
    protected $guarded=['id'];
    public $incrementing = false;
    protected $keyType = 'string';

    //     public static function countRev($id) 
    // {
    //     $index = ProposalDraft::whereRaw("SUBSTRING(proposal_draft_defined_id,1,15)=".substr($id,0,15))
    //     ->select('proposal_draft_index')
    //     ->orderBy('proposal_draft_index','desc')
    //     ->first();  
    //     return substr($index->proposal_draft_index,3,2);
    // }

    /**
     * Get the creativeMember that owns the ProposalDraft
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creativeMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creative_member', 'id');
    }
    /**
     * Get the Event that owns the ProposalDraft
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Event()
    {
        return $this->belongsTo(Event::class, 'id_event', 'event_defined_id');
    }
    /**
     * Get all of the Vendor for the ProposalDraft
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Vendors()
    {
        return $this->hasMany(Vendor::class, 'id_proposal_draft', 'proposal_draft_defined_id');
    }
    /**
     * Get the ProposalSubmission associated with the ProposalDraft
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ProposalSubmission()
    {
        return $this->hasOne(ProposalSubmission::class, 'id_proposal_draft', 'proposal_draft_defined_id');
    }

    // For Filament RelationManager: history revisi submission per draft
    public function ProposalSubmissions()
    {
        return $this->hasMany(ProposalSubmission::class, 'id_proposal_draft', 'proposal_draft_defined_id');
    }
    
}
