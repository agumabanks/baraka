<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Department;
use App\Models\Backend\Designation;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Role;
use App\Models\Backend\Salary;
use App\Models\Backend\Upload;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Builder;
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

    public const CLIENT_TYPES = [UserType::MERCHANT];

    public const TEAM_MEMBER_TYPES = [UserType::DELIVERYMAN, UserType::INCHARGE, UserType::HUB];

    public const SYSTEM_ADMIN_TYPES = [UserType::ADMIN];

    private const USER_TYPE_LABELS = [
        UserType::ADMIN => 'admin',
        UserType::MERCHANT => 'client',
        UserType::DELIVERYMAN => 'deliveryman',
        UserType::INCHARGE => 'incharge',
        UserType::HUB => 'hub',
    ];

    private const USER_TYPE_ALIAS_MAP = [
        'admin' => UserType::ADMIN,
        'system_admin' => UserType::ADMIN,
        'merchant' => UserType::MERCHANT,
        'client' => UserType::MERCHANT,
        'customer' => UserType::MERCHANT,
        'deliveryman' => UserType::DELIVERYMAN,
        'delivery_man' => UserType::DELIVERYMAN,
        'courier' => UserType::DELIVERYMAN,
        'driver' => UserType::DELIVERYMAN,
        'incharge' => UserType::INCHARGE,
        'in_charge' => UserType::INCHARGE,
        'hub' => UserType::HUB,
    ];

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
        'preferred_language',
        'primary_branch_id',

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
        'primary_branch_id' => 'integer',
    ];

    public const SUPPORTED_LANGUAGES = ['en', 'fr', 'sw'];

    public function primaryBranch()
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }

    public function getUserTypeLabelAttribute(): ?string
    {
        $type = self::normalizeUserType($this->attributes['user_type'] ?? null);

        return $type !== null ? (self::USER_TYPE_LABELS[$type] ?? null) : null;
    }

    public function getIsClientAttribute(): bool
    {
        return $this->isClient();
    }

    public function isClient(): bool
    {
        $type = self::normalizeUserType($this->attributes['user_type'] ?? null);

        return $type !== null && in_array($type, self::CLIENT_TYPES, true);
    }

    public static function isClientType(int|string|null $value): bool
    {
        $type = self::normalizeUserType($value);

        return $type !== null && in_array($type, self::CLIENT_TYPES, true);
    }

    public static function labelForUserType(int|string|null $value): ?string
    {
        $type = self::normalizeUserType($value);

        return $type !== null ? (self::USER_TYPE_LABELS[$type] ?? null) : null;
    }

    public static function normalizeUserType(int|string|null $value): ?int
    {
        if (is_null($value)) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $key = strtolower(trim((string) $value));

        return self::USER_TYPE_ALIAS_MAP[$key] ?? null;
    }

    public function scopeClients(Builder $query): Builder
    {
        return $query->whereIn('user_type', self::CLIENT_TYPES);
    }

    public function scopeTeamMembers(Builder $query): Builder
    {
        return $query->whereIn('user_type', self::TEAM_MEMBER_TYPES);
    }

    public function scopeSystemAdmins(Builder $query): Builder
    {
        return $query->whereIn('user_type', self::SYSTEM_ADMIN_TYPES);
    }

    public function scopeInternalUsers(Builder $query): Builder
    {
        return $query->whereIn('user_type', array_merge(self::TEAM_MEMBER_TYPES, self::SYSTEM_ADMIN_TYPES));
    }

    public function setUserTypeAttribute($value): void
    {
        $normalized = self::normalizeUserType($value);

        if ($normalized === null) {
            if (is_null($value)) {
                $this->attributes['user_type'] = null;
            } elseif (is_numeric($value)) {
                $this->attributes['user_type'] = (int) $value;
            } else {
                $this->attributes['user_type'] = null;
            }

            return;
        }

        $this->attributes['user_type'] = $normalized;
    }

    public function setPreferredLanguageAttribute(?string $value): void
    {
        $language = $value ? strtolower(trim($value)) : null;

        if ($language && in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            $this->attributes['preferred_language'] = $language;

            return;
        }

        $this->attributes['preferred_language'] = 'en';
    }

    public function getPreferredLanguageAttribute(?string $value): string
    {
        $resolved = $value ? strtolower($value) : null;

        if ($resolved && in_array($resolved, self::SUPPORTED_LANGUAGES, true)) {
            return $resolved;
        }

        return 'en';
    }

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

    public function branchWorker()
    {
        return $this->hasOne(BranchWorker::class, 'user_id');
    }

    public function branchWorkers(): HasMany
    {
        return $this->hasMany(BranchWorker::class, 'user_id');
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class, 'user_id');
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
        if (isset($this->user_type) && (int) $this->user_type === UserType::ADMIN) {
            return true;
        }

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

        if (isset($this->user_type) && (int) $this->user_type === UserType::ADMIN) {
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
