<?php

use App\Enums\Status;
use App\Enums\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('mobile')->nullable();
            $table->string('nid_number')->nullable();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedTinyInteger('user_type')->default(UserType::ADMIN)->comment(UserType::ADMIN.'='.trans('userType.'.UserType::ADMIN).', '.UserType::MERCHANT.'='.trans('userType.'.UserType::MERCHANT).', '.UserType::DELIVERYMAN.'='.trans('userType.'.UserType::DELIVERYMAN).', '.UserType::INCHARGE.'='.trans('userType.'.UserType::INCHARGE))->nullable();
            $table->foreignId('image_id')->nullable()->constrained('uploads')->onUpdate('cascade')->onDelete('cascade');
            $table->string('joining_date')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('role_id')->nullable()->constrained('roles')->onUpdate('cascade')->onDelete('cascade');
            $table->text('permissions')->nullable();
            $table->integer('otp')->nullable();
            $table->decimal('salary', 16, 2)->default(0);
            $table->string('device_token')->nullable();
            $table->string('web_token')->nullable();
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE)->comment(Status::ACTIVE.'='.trans('status.'.Status::ACTIVE).', '.Status::INACTIVE.'='.trans('status.'.Status::INACTIVE));
            $table->unsignedTinyInteger('verification_status')->default(Status::ACTIVE)->comment(Status::ACTIVE.'='.trans('status.'.Status::ACTIVE).', '.Status::INACTIVE.'='.trans('status.'.Status::INACTIVE));
            $table->string('google_id')->unique()->nullable();
            $table->string('facebook_id')->unique()->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('designation_id');
            $table->index('department_id');
            $table->index('hub_id');
            $table->index('role_id');
            $table->index('user_type');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
