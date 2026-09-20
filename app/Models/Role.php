<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $primaryKey = 'role_defined_id';
    protected $guarded = ['id'];
    public $incrementing = false;
    protected $keyType = 'string';

    // Compat: Filament expects is_active, DB has role_active_status
    protected $appends = ['is_active'];

    public function getIsActiveAttribute(): bool
    {
        return (bool) ($this->attributes['role_active_status'] ?? false);
    }

    public function setIsActiveAttribute(bool $value): void
    {
        $this->attributes['role_active_status'] = $value;
    }
}
