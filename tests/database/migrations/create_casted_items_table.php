<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('casted_items', function (Blueprint $table) {
            $table->id();

            $table->dateTime('date_at')->nullable();
            // full datetime storage

            $table->dateTime('datetime_at')->nullable();
            // stored as 'Y-m-d H:i:s' even for date-only
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('casted_items');
    }
};
