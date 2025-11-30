<?php

namespace Tests\Feature\Branch;

use App\Models\Backend\Branch;
use App\Models\Backend\BranchWorker;
use App\Models\BranchAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkforceAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function createWorker(Branch $branch): BranchWorker
    {
        $user = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);

        return BranchWorker::create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 1,
            'role' => 'courier',
        ]);
    }

    public function test_schedule_and_check_in_out(): void
    {
        $branch = Branch::factory()->create();
        $worker = $this->createWorker($branch);
        $actor = User::factory()->create([
            'primary_branch_id' => $branch->id,
            'permissions' => ['branch_manage'],
        ]);

        $this->actingAs($actor)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.workforce.schedule'), [
                'worker_id' => $worker->id,
                'shift_date' => now()->toDateString(),
                'start_at' => now()->subMinutes(5)->toDateTimeString(),
                'end_at' => now()->addHours(1)->toDateTimeString(),
            ])
            ->assertRedirect();

        $attendance = BranchAttendance::first();
        $this->assertNotNull($attendance);
        $this->assertEquals('SCHEDULED', $attendance->status);

        $this->actingAs($actor)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.workforce.checkin'), [
                'attendance_id' => $attendance->id,
            ])
            ->assertRedirect();

        $attendance->refresh();
        $this->assertEquals('LATE', $attendance->status);
        $this->assertNotNull($attendance->check_in_at);

        $this->actingAs($actor)
            ->withSession(['current_branch_id' => $branch->id])
            ->post(route('branch.workforce.checkout'), [
                'attendance_id' => $attendance->id,
            ])
            ->assertRedirect();

        $attendance->refresh();
        $this->assertEquals('COMPLETED', $attendance->status);
        $this->assertNotNull($attendance->check_out_at);
    }

    public function test_branch_scope_enforced_on_attendance(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $worker = $this->createWorker($branchA);
        $attendance = BranchAttendance::create([
            'branch_id' => $branchA->id,
            'worker_id' => $worker->id,
            'shift_date' => now()->toDateString(),
            'status' => 'SCHEDULED',
        ]);

        $actorB = User::factory()->create([
            'primary_branch_id' => $branchB->id,
            'permissions' => ['branch_manage'],
        ]);

        $this->actingAs($actorB)
            ->withSession(['current_branch_id' => $branchB->id])
            ->post(route('branch.workforce.checkin'), [
                'attendance_id' => $attendance->id,
            ])
            ->assertStatus(403);
    }
}
