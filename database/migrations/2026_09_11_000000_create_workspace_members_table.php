<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('workspace_members')) {
            Schema::create('workspace_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role', 20)->default('member'); // owner | admin | member
                $table->timestamps();

                $table->unique(['workspace_id', 'user_id']);
            });
        }

        // Backfill: existing workspace owners become members with the owner role.
        DB::table('workspaces')->orderBy('id')->chunk(100, function ($workspaces) {
            foreach ($workspaces as $workspace) {
                if (! $workspace->user_id) {
                    continue;
                }

                DB::table('workspace_members')->insertOrIgnore([
                    'workspace_id' => $workspace->id,
                    'user_id'      => $workspace->user_id,
                    'role'         => 'owner',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_members');
    }
};
