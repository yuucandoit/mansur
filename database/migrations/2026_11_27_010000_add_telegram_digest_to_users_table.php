<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'telegram_digest_enabled')) {
                $table->boolean('telegram_digest_enabled')->default(false)->after('telegram_user_id');
            }
            if (! Schema::hasColumn('users', 'telegram_digest_morning')) {
                $table->string('telegram_digest_morning', 5)->default('08:00')->after('telegram_digest_enabled');
            }
            if (! Schema::hasColumn('users', 'telegram_digest_evening')) {
                $table->string('telegram_digest_evening', 5)->default('17:00')->after('telegram_digest_morning');
            }
            if (! Schema::hasColumn('users', 'telegram_digest_last_sent_slot')) {
                $table->string('telegram_digest_last_sent_slot', 32)->nullable()->after('telegram_digest_evening');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_values(array_filter([
                Schema::hasColumn('users', 'telegram_digest_enabled') ? 'telegram_digest_enabled' : null,
                Schema::hasColumn('users', 'telegram_digest_morning') ? 'telegram_digest_morning' : null,
                Schema::hasColumn('users', 'telegram_digest_evening') ? 'telegram_digest_evening' : null,
                Schema::hasColumn('users', 'telegram_digest_last_sent_slot') ? 'telegram_digest_last_sent_slot' : null,
            ]));

            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
