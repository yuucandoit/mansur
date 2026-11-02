<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_members')) {
            Schema::create('project_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role', 20)->default('member'); // manager | member
                $table->timestamps();

                $table->unique(['project_id', 'user_id']);
            });
        }

        // Backfill: project owners become project members (and workspace members).
        DB::table('projects')->orderBy('id')->chunk(100, function ($projects) {
            foreach ($projects as $project) {
                if (! $project->user_id) {
                    continue;
                }

                DB::table('project_members')->insertOrIgnore([
                    'project_id' => $project->id,
                    'user_id'    => $project->user_id,
                    'role'       => 'manager',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($project->workspace_id) {
                    DB::table('workspace_members')->insertOrIgnore([
                        'workspace_id' => $project->workspace_id,
                        'user_id'      => $project->user_id,
                        'role'         => 'member',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
