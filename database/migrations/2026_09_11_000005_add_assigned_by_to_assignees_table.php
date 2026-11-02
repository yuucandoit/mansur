<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assignees', 'assigned_by')) {
            Schema::table('assignees', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_by')->nullable()->after('user_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assignees', 'assigned_by')) {
            Schema::table('assignees', function (Blueprint $table) {
                $table->dropColumn('assigned_by');
            });
        }
    }
};
