<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUPPORTED_LANGUAGES = ['en', 'fr', 'sw'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'preferred_language')) {
                $table->string('preferred_language', 5)
                    ->default('en')
                    ->after('web_token');
                $table->index('preferred_language');
            }
        });

        $this->backfillExistingUsers();
        $this->applyCheckConstraint();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropCheckConstraint();

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'preferred_language')) {
                $table->dropIndex(['preferred_language']);
                $table->dropColumn('preferred_language');
            }
        });
    }

    private function backfillExistingUsers(): void
    {
        if (! Schema::hasColumn('users', 'preferred_language')) {
            return;
        }

        DB::table('users')
            ->whereNull('preferred_language')
            ->orWhere('preferred_language', '')
            ->update(['preferred_language' => 'en']);

        DB::table('users')
            ->whereNotIn('preferred_language', self::SUPPORTED_LANGUAGES)
            ->update(['preferred_language' => 'en']);
    }

    private function applyCheckConstraint(): void
    {
        $driver = DB::getDriverName();
        $constraintName = 'users_preferred_language_check';

        if ($this->constraintExists($constraintName)) {
            return;
        }

        $constraint = "preferred_language IN ('".implode("','", self::SUPPORTED_LANGUAGES)."')";

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT {$constraintName} CHECK ({$constraint})");
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT {$constraintName} CHECK ({$constraint})");
        }
    }

    private function dropCheckConstraint(): void
    {
        $driver = DB::getDriverName();
        $constraintName = 'users_preferred_language_check';

        if (! $this->constraintExists($constraintName)) {
            return;
        }

        try {
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE users DROP CHECK {$constraintName}");
            }

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE users DROP CONSTRAINT {$constraintName}");
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function constraintExists(string $constraintName): bool
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $database = $connection->getDatabaseName();

            return DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', $database)
                ->where('TABLE_NAME', 'users')
                ->where('CONSTRAINT_NAME', $constraintName)
                ->exists();
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_constraint as c')
                ->join('pg_class as t', 'c.conrelid', '=', 't.oid')
                ->where('t.relname', 'users')
                ->where('c.conname', $constraintName)
                ->exists();
        }

        return false;
    }
};
