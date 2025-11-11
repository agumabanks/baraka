<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait OptimisticLocking
{
    protected static function bootOptimisticLocking(): void
    {
        static::updating(function (Model $model) {
            $model->increment('version');
        });
    }

    public static function bootOptimisticLockingObserver(): void
    {
        static::updating(function (Model $model) {
            if (!$model->getConnection()->getQueryGrammar()->supportsLocking()) {
                return;
            }

            $model->getConnection()->statement(
                $model->getConnection()->getQueryBuilder()
                    ->from($model->getTable())
                    ->where('id', $model->id)
                    ->where('version', $model->getOriginal('version') ?? 0)
                    ->limit(1)
                    ->toSql()
            );
        });
    }

    public function checkVersion($expectedVersion): bool
    {
        return $this->version === $expectedVersion;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'version' => $this->version,
            '_lock_token' => $this->generateLockToken(),
        ]);
    }

    private function generateLockToken(): string
    {
        return hash('sha256', $this->id . ':' . $this->version . ':' . config('app.key'));
    }
}
