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
        Schema::create(config('language.tables.language'), function (Blueprint $table) {
            $table->id();

            $table->string('name');
            /**
             * The name field is used to store the language name.
             */

            $table->string('flag')->nullable();
            /**
             * The flag field is used to store the language flag.
             */

            $table->string('locale');
            /**
             * The locale field is used to store the language locale.
             */

            $table->string('direction');
            /**
             * The direction field is used to store the language direction.
             */

            $table->boolean('status')->default(true);

            $table->timestamps();
        });

        cache()->forget('language');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(config('language.tables.language'));

        cache()->forget('language');
    }
};
