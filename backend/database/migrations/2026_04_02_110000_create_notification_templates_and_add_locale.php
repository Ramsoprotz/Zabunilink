<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type');        // e.g. welcome, password_reset, new_tender
            $table->string('channel');     // email or sms
            $table->string('locale', 5);   // en or sw
            $table->string('subject')->nullable(); // email subject only
            $table->text('body');
            $table->timestamps();

            $table->unique(['type', 'channel', 'locale']);
        });

        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->string('locale', 5)->default('en')->after('push_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');

        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
