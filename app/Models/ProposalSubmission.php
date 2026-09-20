<?php

namespace App\Models;

use App\Models\Need;
use App\Models\User;
use App\Models\BankAccount;
use App\Models\ProposalDraft;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProposalSubmission extends Model
{
    use HasFactory;
    protected $primaryKey =  'proposal_submission_defined_id';
    protected $guarded=['id'];
    public $incrementing = false;
    protected $keyType = 'string';
    // protected $fillable=[
    //     'proposal_submission_defined_id',
    //     'proposal_submission_index',
    //     'proposal_submission_event_identity',
    //     'proposal_submission_booking_code',
    //     'proposal_submission_note_manager',
    //     'proposal_submission_status',
    //     'id_proposal_draft',
    //     'organizer_admin',
    //     'inspiring_manager',
    // ];
/**
 * Get the ProposalDraft that owns the ProposalSubmission
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function ProposalDraft() 
    {
        return $this->belongsTo(ProposalDraft::class, 'id_proposal_draft', 'proposal_draft_defined_id');
    }
    /**
     * The Needs that belong to the ProposalSubmission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function Needs()
    {
        return $this->belongsToMany(Need::class, 'need_submissions', 'id_proposal_submission','id_need', 'proposal_submission_defined_id', 'need_defined_id');
    }
    /**
     * Get all of the BankAccounts for the ProposalSubmission
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function BankAccounts()
    {
        return $this->hasMany(BankAccount::class, 'id_proposal_submission', 'proposal_submission_defined_id');
    }
    /**
     * Get the Manager that owns the ProposalSubmission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Manager()
    {
        return $this->belongsTo(User::class, 'inspiring_manager');
    }
}
