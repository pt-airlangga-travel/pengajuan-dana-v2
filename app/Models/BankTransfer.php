<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransfer extends Model
{
    use HasFactory;
    protected $primaryKey =  'bank_transfer_defined_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['id'];

}
