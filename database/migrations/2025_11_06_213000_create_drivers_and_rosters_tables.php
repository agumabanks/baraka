<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('drivers')) {
            Schema::create('drivers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('code', 50)->nullable()->unique();
                $table->string('name');
                $table->string('phone', 30)->nullable();
                $table->string('email')->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->string('employment_status', 20)->default('ACTIVE');
                $table->string('license_number')->nullable();
                $table->date('license_expiry')->nullable();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->json('documents')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('onboarded_at')->nullable();
                $table->timestamp('offboarded_at')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['branch_id', 'status']);
                $table->index('employment_status');
            });
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('description')->constrained('branches')->nullOnDelete();
            }
            if (! Schema::hasColumn('vehicles', 'type')) {
                $table->string('type', 30)->nullable()->after('branch_id');
            }
            if (! Schema::hasColumn('vehicles', 'capacity_kg')) {
                $table->decimal('capacity_kg', 8, 2)->nullable()->after('type');
            }
            if (! Schema::hasColumn('vehicles', 'capacity_volume')) {
                $table->decimal('capacity_volume', 8, 2)->nullable()->after('capacity_kg');
            }
            if (! Schema::hasColumn('vehicles', 'ownership')) {
                $table->string('ownership', 30)->default('COMPANY')->after('capacity_volume');
            }
            if (! Schema::hasColumn('vehicles', 'status')) {
                $table->string('status', 20)->default('ACTIVE')->after('ownership');
            }
        });

        if (! Schema::hasTable('driver_rosters')) {
            Schema::create('driver_rosters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('shift_type', 40)->nullable();
                $table->timestamp('start_time');
                $table->timestamp('end_time');
                $table->string('status', 20)->default('SCHEDULED');
                $table->integer('planned_hours')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['driver_id', 'status']);
                $table->index('start_time');
            });
        }

        if (! Schema::hasTable('driver_time_logs')) {
            Schema::create('driver_time_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
                $table->foreignId('roster_id')->nullable()->constrained('driver_rosters')->nullOnDelete();
                $table->string('log_type', 30); // CHECK_IN, CHECK_OUT, BREAK_START, BREAK_END
                $table->timestamp('logged_at');
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('source', 30)->default('manual');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['driver_id', 'log_type']);
                $table->index('logged_at');
            });
        }

        // Link shipments to drivers for operational assignment
        if (Schema::hasTable('shipments') && ! Schema::hasColumn('shipments', 'assigned_driver_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->foreignId('assigned_driver_id')->nullable()->after('assigned_worker_id')->constrained('drivers')->nullOnDelete();
                $table->timestamp('driver_assigned_at')->nullable()->after('assigned_driver_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'assigned_driver_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropForeign(['assigned_driver_id']);
            });
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropColumn(['assigned_driver_id', 'driver_assigned_at']);
            });
        }

        Schema::dropIfExists('driver_time_logs');
        Schema::dropIfExists('driver_rosters');
        Schema::dropIfExists('drivers');

        Schema::table('vehicles', function (Blueprint $table) {
            foreach (['status', 'ownership', 'capacity_volume', 'capacity_kg', 'type', 'branch_id'] as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    if ($column === 'branch_id') {
                        $table->dropForeign(['branch_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
