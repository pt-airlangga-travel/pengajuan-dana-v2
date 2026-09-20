<?php

namespace App\Models;

use App\Models\ProposalSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Need extends Model
{
    use HasFactory;
    protected $primaryKey =  'need_defined_id';
    protected $guarded = ['id'];
    public $incrementing = false;
    protected $keyType = 'string';
    public function ProposalSubmission()
    {
        return $this->belongsToMany(ProposalSubmission::class, 'need_submissions', 'id_need', 'id_proposal_submission');
    }
}
