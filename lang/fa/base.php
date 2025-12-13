<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during Language for
    | various messages that we need to display to the user.
    |
    */

    "calendar_type" => [
        "gregorian" => "میلادی",
        "jalali" => "شمسی",
        "hijri" => "قمری",
        "hebrew" => "عبری",
        "buddhist" => "بودایی",
        "coptic" => "قبطی",
        "ethiopian" => "اتیوپیایی",
        "chinese" => "چینی",
    ],

    "direction" => [
        "ltr" => "چپ به راست",
        "rtl" => "راست به چپ",
    ],

    "weekdays" => [
        "0" => "شنبه",
        "1" => "یکشنبه",
        "2" => "دوشنبه",
        "3" => "سه‌شنبه",
        "4" => "چهارشنبه",
        "5" => "پنج‌شنبه",
        "6" => "جمعه",
    ],

    "exceptions" => [
        "language_data_not_exist" => "داده های زبان در (:locale) وجود ندارد.",
    ],

    "validation" => [
        "locale" => ":attribute (:locale) از قبل وجود دارد.",
        "language_exist" => "زبان :locale از قبل وجود دارد.",
        "language_not_found" => "زبان پیدا نشد.",
        "future_date" => ":attribute باید در آینده باشد.",
    ],

    "messages" => [
        "created" => "زبان با موفقیت ایجاد شد.",
        "updated" => "زبان با موفقیت به روز شد.",
        "deleted" => "زبان با موفقیت حذف شد.",
        "deleted_items" => "{1} یک زبان با موفقیت حذف شد.|[2,*] :count مورد زبان با موفقیت حذف شدند.",
        "status" => [
            "enable" => "{1} یک زبان فعال شد.|[2,*] :count مورد زبان فعال شدند.",
            "disable" => "{1} یک زبان غیرفعال شد.|[2,*] :count مورد زبان غیرفعال شدند.",
        ],
    ],

    "fields" => [
        "name" => "نام زبان",
        "flag" => "پرچم",
        "locale" => "محلی سازی",
        "direction" => "جهت",
        "calendar" => "تقویم",
        "first_day_of_week" => "اولین روز هفته",
        "status" => "وضعیت",
    ],

    "entity_names" => [
        "language" => "زبان",
    ],

    'events' => [
        'language_stored' => [
            'title' => 'ذخیره زبان',
            'description' => 'هنگامی که یک زبان ذخیره می‌شود، این رویداد فعال می‌شود.',
        ],

        'language_updated' => [
            'title' => 'به‌روزرسانی زبان',
            'description' => 'هنگامی که یک زبان به‌روزرسانی می‌شود، این رویداد فعال می‌شود.',
        ],

        'language_deleted' => [
            'title' => 'حذف زبان',
            'description' => 'هنگامی که یک زبان حذف می‌شود، این رویداد فعال می‌شود.',
        ],

        'language_deleting' => [
            'title' => 'در حال حذف زبان',
            'description' => 'هنگامی که یک زبان در حال حذف است، این رویداد فعال می‌شود.',
        ],

        'locale_set' => [
            'title' => 'تنظیم محلی‌سازی',
            'description' => 'هنگامی که محلی‌سازی تنظیم می‌شود، این رویداد فعال می‌شود.',
        ],
    ],

];
