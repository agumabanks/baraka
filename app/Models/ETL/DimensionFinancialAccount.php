<?php

namespace App\Models\ETL;

use Illuminate\Database\Eloquent\Model;

class DimensionFinancialAccount extends Model
{
    protected $table = 'dim_financial_accounts';
    public $timestamps = false;
    protected $primaryKey = 'account_key';
    public $incrementing = false;
    protected $keyType = 'bigint';

    protected $fillable = [
        'account_key',
        'account_code',
        'account_name',
        'account_type',
        'parent_account_key',
        'is_active',
        'effective_date',
        'expiration_date',
        'account_category',
        'account_subcategory',
        'cost_center_code',
        'business_unit',
        'currency_code',
        'is_system_account'
    ];

    protected $casts = [
        'account_key' => 'bigint',
        'parent_account_key' => 'bigint',
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'expiration_date' => 'date',
        'is_system_account' => 'boolean'
    ];

    public function parentAccount()
    {
        return $this->belongsTo(DimensionFinancialAccount::class, 'parent_account_key', 'account_key');
    }

    public function childAccounts()
    {
        return $this->hasMany(DimensionFinancialAccount::class, 'parent_account_key', 'account_key');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('account_category', $category);
    }
}