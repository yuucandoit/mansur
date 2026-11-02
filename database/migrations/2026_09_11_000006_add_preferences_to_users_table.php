<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'task_notifications')) {
                $table->boolean('task_notifications')->default(true)->after('is_active');
            }
            if (! Schema::hasColumn('users', 'compact_cards')) {
                $table->boolean('compact_cards')->default(false)->after('task_notifications');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists(['task_notifications', 'compact_cards']);
        });
    }
};
