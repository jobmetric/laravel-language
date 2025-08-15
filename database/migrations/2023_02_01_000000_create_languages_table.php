<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JobMetric\Language\Enums\CalendarTypeEnum;

/**
 * languages table migration.
 *
 * This migration creates a normalized registry for application languages used by
 * internationalization (i18n), localization (L10n), formatting preferences, and UI
 * behavior such as text direction or calendar system. The table name is resolved
 * via the `language.tables.language` configuration key to keep it customizable per
 * environment or installation.
 *
 * @see CalendarTypeEnum for canonical calendar values supported by the package.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Creates the languages table with semantic columns for i18n/L10n configuration.
     * No data is seeded here; seeding should be handled via dedicated seeders to keep
     * environments reproducible and testable.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(config('language.tables.language'), function (Blueprint $table) {
            $table->id();

            $table->string('name');
            /**
             * Human‑readable display name of the language.
             *
             * Examples: "Persian", "English (United Kingdom)".
             * This value is not guaranteed to be unique; use `locale` for identity.
             */

            $table->string('flag')->nullable();
            /**
             * Optional visual representation of the language flag.
             *
             * Can be:
             * - A file path or slug within your media storage, or
             * - A flag emoji (e.g., "🇮🇷"), or
             * - Any identifier understood by your presentation layer.
             * Nullable to support headless or icon‑less deployments.
             */

            $table->string('locale');
            /**
             * Locale identifier following BCP‑47 style tags.
             *
             * Examples: "fa-IR", "en-GB", "ar-SA".
             * Consider adding a UNIQUE index if each locale must be globally distinct.
             */

            $table->enum('direction', [
                'ltr',
                'rtl'
            ])->default('ltr')->index();
            /**
             * Text direction for UI rendering and layout.
             *
             * Allowed values:
             * - ltr: Left‑to‑Right scripts (e.g., Latin).
             * - rtl: Right‑to‑Left scripts (e.g., Arabic, Persian, Hebrew).
             * Indexed for efficient filtering.
             */

            $table->string('calendar')->default(CalendarTypeEnum::GREGORIAN());
            /**
             * Calendar system identifier used for date presentation and parsing.
             *
             * Common values include:
             * - "gregorian"  -> first day of week 0 (Sunday)
             * - "jalali"     -> first day of week 6 (Saturday)
             * - "hijri"      -> first day of week 6 (Saturday)
             * - "hebrew"     -> first day of week 0 (Sunday)
             * - "buddhist"   -> first day of week 0 (Sunday)
             * - "coptic"     -> first day of week 0 (Sunday)
             * - "ethiopian"  -> first day of week 0 (Sunday)
             * - "chinese"    -> first day of week 0 (Sunday)
             *
             * This column is a string to preserve forward compatibility with
             * additional calendar systems. For canonical constants, see:
             * @see CalendarTypeEnum
             */

            $table->unsignedTinyInteger('first_day_of_week')->default(1);
            /**
             * First day of the week used by the application calendar UI.
             *
             * Range: 0..6
             * Mapping in this schema: 0=Sunday, 1=Monday, ..., 6=Saturday.
             * Defaults to 0 (Saturday). Adjust in seed/config to match locale norms.
             */

            $table->boolean('status')->default(true)->index();
            /**
             * Activation toggle for availability across the system.
             *
             * true  => language is active/available
             * false => language is inactive/hidden
             * Indexing can be added if you frequently filter by status.
             */

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the languages table. This operation is destructive; ensure any dependent
     * data or foreign‑key constraints are handled in prior migrations or guarded via
     * application logic.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(config('language.tables.language'));
    }
};
