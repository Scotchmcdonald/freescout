<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('conversations')) {
            return;
        }

        Schema::table('conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('conversations', 'waiting_on_user_id')) {
                $table->unsignedBigInteger('waiting_on_user_id')->nullable()->after('user_id');
                $table->foreign('waiting_on_user_id')->references('id')->on('users')->nullOnDelete();
                $table->index('waiting_on_user_id');
            }

            if (! Schema::hasColumn('conversations', 'waiting_reason')) {
                $table->string('waiting_reason', 80)->nullable()->after('waiting_on_user_id');
            }

            if (! Schema::hasColumn('conversations', 'next_follow_up')) {
                $table->dateTime('next_follow_up')->nullable()->after('follow_up_date');
                $table->index('next_follow_up');
            }

            if (! Schema::hasColumn('conversations', 'last_contact_at')) {
                $table->dateTime('last_contact_at')->nullable()->after('last_reply_at');
                $table->index('last_contact_at');
            }
        });
    }

    public function down(): void
    {
        // Forward-only migration to avoid destructive schema rollbacks.
    }
};
