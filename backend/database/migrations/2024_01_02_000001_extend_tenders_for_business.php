<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            // Business-posted tenders: null means admin-posted
            $table->foreignId('posted_by_user_id')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
            // government, private, business
            $table->string('source')->default('admin')->after('type'); // admin, business
            $table->unsignedInteger('views_count')->default(0)->after('status');
            $table->unsignedInteger('applications_count')->default(0)->after('views_count');
            $table->boolean('is_published')->default(true)->after('applications_count');
        });

        Schema::table('tender_applications', function (Blueprint $table) {
            // Extended statuses for tenderee workflow
            // pending, in_progress, submitted, won, lost (existing)
            // + shortlisted, awarded, rejected (new tenderee statuses)
            $table->text('tenderee_notes')->nullable()->after('admin_notes');
            $table->timestamp('awarded_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropForeign(['posted_by_user_id']);
            $table->dropColumn(['posted_by_user_id', 'source', 'views_count', 'applications_count', 'is_published']);
        });

        Schema::table('tender_applications', function (Blueprint $table) {
            $table->dropColumn(['tenderee_notes', 'awarded_at']);
        });
    }
};
