<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'list_id')) {
                // already exists
            }
            if (!Schema::hasColumn('tasks', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('project_id');
            }
            if (!Schema::hasColumn('tasks', 'priority')) {
                $table->string('priority', 20)->nullable()->after('due_date'); // low, medium, high, critical
            }
            if (!Schema::hasColumn('tasks', 'is_critical')) {
                $table->tinyInteger('is_critical')->default(0)->after('priority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumnIfExists(['parent_id', 'priority', 'is_critical']);
        });
    }
};
