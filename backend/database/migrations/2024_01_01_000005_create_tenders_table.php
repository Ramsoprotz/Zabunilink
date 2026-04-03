<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('reference_number')->nullable();
            $table->text('description');
            $table->string('organization');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('type')->default('government'); // government, private
            $table->decimal('value', 15, 2)->nullable();
            $table->date('deadline');
            $table->date('published_date')->nullable();
            $table->string('status')->default('open'); // open, closed, awarded
            $table->text('requirements')->nullable();
            $table->text('documents_url')->nullable();
            $table->string('contact_info')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders');
    }
};
