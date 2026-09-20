<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// parent dari Event
class Institution extends Model
{
    use HasFactory;
    protected $primaryKey = 'institution_defined_id';
    protected $guarded = ['id'];
    public $incrementing = false;
    protected $keyType = 'string';
    public function events(){
        return $this->hasMany(Event::class, "id_institution",'institution_defined_id'); 
    }

}
