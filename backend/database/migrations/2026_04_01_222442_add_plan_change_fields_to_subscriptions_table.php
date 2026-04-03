<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Track the previous subscription this one replaced (upgrade/downgrade chain)
            $table->foreignId('previous_subscription_id')
                  ->nullable()
                  ->after('status')
                  ->constrained('subscriptions')
                  ->nullOnDelete();

            // Credit carried over from the previous subscription (prorated unused amount)
            $table->decimal('proration_credit', 10, 2)->default(0)->after('previous_subscription_id');

            // Scheduled downgrade: store the plan to switch to at end of current period
            $table->foreignId('scheduled_plan_id')
                  ->nullable()
                  ->after('proration_credit')
                  ->constrained('plans')
                  ->nullOnDelete();

            $table->string('scheduled_billing_cycle')->nullable()->after('scheduled_plan_id');
        });

        // Add tier column to plans for ordering (Basic=1, Pro=2, Business=3)
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('tier')->default(1)->after('name');
        });

        // Set tier values for existing plans
        DB::table('plans')->where('name', 'Basic')->update(['tier' => 1]);
        DB::table('plans')->where('name', 'Pro')->update(['tier' => 2]);
        DB::table('plans')->where('name', 'Business')->update(['tier' => 3]);
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['previous_subscription_id']);
            $table->dropColumn(['previous_subscription_id', 'proration_credit', 'scheduled_billing_cycle']);
            $table->dropForeign(['scheduled_plan_id']);
            $table->dropColumn('scheduled_plan_id');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('tier');
        });
    }
};
