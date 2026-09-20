<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role as RoleEnum;
use App\Models\ProposalSubmission;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'position',
        'active_status',
        'id_role',
        'id_division',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // --- Role helpers for Opsi B (1 prefix, 5 folders) ---
    public function hasRole(RoleEnum $role): bool
    {
        return $this->id_role === $role->value;
    }

    public function hasAnyRole(array $roles): bool
    {
        $values = array_map(fn (RoleEnum $r) => $r->value, $roles);
        return in_array($this->id_role, $values, true);
    }

    public function roleEnum(): ?RoleEnum
    {
        return RoleEnum::tryFrom($this->id_role);
    }

    public function roleLabel(): string
    {
        return $this->roleEnum()?->label() ?? $this->id_role;
    }

    // Filament panel access: block inactive users
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->active_status;
    }

    // Compatibility for Filament Login yang expect is_active / username
    public function getIsActiveAttribute(): bool
    {
        return (bool) $this->active_status;
    }

    public function getUsernameAttribute(): ?string
    {
        return $this->email;
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role', 'role_defined_id');
    }

    // Compat: Filament expects roles (plural) collection, DB only has id_role single FK
    // hasMany where role_defined_id = id_role returns 0/1 collection
    public function roles()
    {
        return $this->hasMany(Role::class, 'role_defined_id', 'id_role');
    }

    // Compat: active_role_id column tidak ada di DB legacy (hanya id_role char3)
    public function getActiveRoleIdAttribute(): ?int
    {
        if (array_key_exists('active_role_id', $this->attributes) && $this->attributes['active_role_id'] !== null) {
            return (int) $this->attributes['active_role_id'];
        }
        return $this->role?->getKey() ? $this->role->id : null;
    }

    public function setActiveRoleIdAttribute($value): void
    {
        // ignore write if column tidak ada; simpan ke attributes saja agar tidak error SQL
        $this->attributes['active_role_id'] = $value;
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'id_division');
    }
    /**
     * Get all of the ManagerSubmission for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ManagerSubmission()
    {
        return $this->hasMany(ProposalSubmission::class, 'inspiring_manager','id');
    }
}
