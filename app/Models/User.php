<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Backend\Account;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Role;
use App\Models\Backend\Salary;
use App\Models\Backend\Upload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'hub_id',
        'image_id',
        'facebook_id',
        'google_id',
        'user_type',
        'phone_e164',
        'mobile',
        'address',

    ];

    /**
     * Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('User')
            ->logOnly(['name', 'email'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}");
    }

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
        'permissions' => 'array',
        'notification_prefs' => 'array',
    ];

    // Get single row in Hub table.
    public function hub()
    {
        return $this->belongsTo(Hub::class, 'hub_id', 'id');
    }

    // Get single row in Upload table.
    public function upload()
    {
        return $this->belongsTo(Upload::class, 'image_id', 'id');
    }

    // Get single row in Department table.
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    // Get single row in Designation table.
    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    // Get all row. Descending order using scope.
    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }

    public function getImageAttribute()
    {
        if (! empty($this->upload->original['original']) && file_exists(public_path($this->upload->original['original']))) {
            return static_asset($this->upload->original['original']);
        }

        return static_asset('images/default/user.png');
    }

    public function getMyStatusAttribute()
    {
        if ($this->status == Status::ACTIVE) {
            $status = '<span class="badge badge-pill badge-success">'.trans('status.'.$this->status).'</span>';
        } else {
            $status = '<span class="badge badge-pill badge-danger">'.trans('status.'.$this->status).'</span>';
        }

        return $status;
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'id', 'user_id');
    }

    // Encrypt phone at rest without breaking existing plain values
    public function getPhoneE164Attribute($value)
    {
        if (is_null($value)) {
            return null;
        }
        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            return $value; // fallback for legacy plain text
        }
    }

    public function setPhoneE164Attribute($value)
    {
        $this->attributes['phone_e164'] = is_null($value) ? null : encrypt($value);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function deliveryman()
    {
        return $this->belongsTo(DeliveryMan::class, 'id', 'user_id');
    }

    public function salary()
    {
        return $this->hasMany(Salary::class, 'user_id', 'id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'user_id', 'id');
    }

    /**
     * Shipments created for this user when acting as a Customer.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'customer_id');
    }

    /**
     * Check if user has a given role by slug or name.
     */
    public function hasRole(string|array $roles): bool
    {
        $this->loadMissing('role');
        $current = strtolower($this->role->slug ?? $this->role->name ?? '');

        if (is_array($roles)) {
            $needle = array_map(fn ($r) => strtolower($r), $roles);

            return in_array($current, $needle, true);
        }

        return $current === strtolower($roles);
    }

    /**
     * Determine if the user has a given permission directly or via role.
     */
    public function hasPermission(string|array $permissions): bool
    {
        if ($this->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        $this->loadMissing('role');

        $permissions = (array) $permissions;
        $ownPermissions = is_array($this->permissions) ? $this->permissions : [];
        $rolePermissions = [];

        if ($this->role && is_array($this->role->permissions)) {
            $rolePermissions = $this->role->permissions;
        }

        foreach ($permissions as $permission) {
            if (in_array($permission, $ownPermissions, true) || in_array($permission, $rolePermissions, true)) {
                return true;
            }
        }

        return false;
    }
}
