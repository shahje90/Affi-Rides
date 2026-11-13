<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rider_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cnic_number', 20)->nullable();
            $table->string('cnic_front_image')->nullable();
            $table->string('cnic_back_image')->nullable();
            $table->string('bike_number', 30)->nullable();
            $table->string('bike_model')->nullable();
            $table->string('bike_document_image')->nullable();
            $table->string('kyc_status', 20)->default('pending')->index();
            $table->text('kyc_note')->nullable();
            $table->boolean('is_online')->default(false);
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->timestamp('last_location_updated_at')->nullable();
            $table->decimal('wallet_balance', 10, 2)->default(0);
            $table->index(['is_online', 'kyc_status']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_profiles');
    }
};
