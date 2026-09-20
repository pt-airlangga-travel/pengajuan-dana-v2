<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;
    protected $primaryKey = 'division_defined_id';
    protected $guarded = ['id'];
    public $incrementing = false;
    protected $keyType = 'string';
    public function user()
    {
        return $this->hasMany(User::class, 'id_division');
    }

}
