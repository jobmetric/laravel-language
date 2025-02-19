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

    "name" => "زبان ها",

    "validation" => [
        "locale" => ":attribute (:locale) از قبل وجود دارد.",
        "language_exist" => "زبان :locale از قبل وجود دارد.",
        "language_not_found" => "زبان پیدا نشد.",
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

    "exceptions" => [
        "language_data_not_exist" => "داده های زبان در (:locale) وجود ندارد.",
    ],

    "list" => [
        "filters" => [
            "name" => [
                "title" => "نام",
                "placeholder" => "نام را وارد کنید",
            ],
        ],
        "columns" => [
            "flag" => "پرچم",
            "locale" => "کلید زبان",
            "direction" => "جهت",
            "calendar" => "نوع تقویم",
        ],
    ],

    "form" => [
        "create" => [
            "title" => "ایجاد زبان",
        ],
        "edit" => [
            "title" => "ویرایش زبان",
        ],
        "fields" => [
            "name" => [
                "title" => "نام",
                "placeholder" => "نام زبان را وارد کنید.",
            ],
            "locale" => [
                "title" => "کلید زبان",
                "placeholder" => "کلید زبان را وارد کنید",
            ],
            "direction" => [
                "title" => "جهت",
                "rtl" => "راست به چپ (rtl)",
                "ltr" => "چپ به راست (ltr)",
            ],
            "calendar" => [
                "title" => "تقویم",
                "placeholder" => "تقویم را انتخاب کنید",
            ],
            "flag" => [
                "title" => "پرچم",
                "placeholder" => "پرچم را انتخاب کنید",
            ],
        ],
    ],

    "calendar_type" => [
        "jalali" => "شمسی",
        "gregorian" => "میلادی",
    ],

];
