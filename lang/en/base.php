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
        "gregorian" => "Gregorian",
        "jalali" => "Jalali",
        "hijri" => "Hijri",
        "hebrew" => "Hebrew",
        "buddhist" => "Buddhist",
        "coptic" => "Coptic",
        "ethiopian" => "Ethiopian",
        "chinese" => "Chinese",
    ],

    "direction" => [
        "ltr" => "Left to Right",
        "rtl" => "Right to Left",
    ],

    "weekdays" => [
        "0" => "Saturday",
        "1" => "Sunday",
        "2" => "Monday",
        "3" => "Tuesday",
        "4" => "Wednesday",
        "5" => "Thursday",
        "6" => "Friday",
    ],

    "exceptions" => [
        "language_data_not_exist" => "The language data does not exist in locale (:locale).",
    ],

    "name" => "Languages",

    "validation" => [
        "locale" => "The :attribute (:locale) has already been taken.",
        "language_exist" => "The language does not exist in locale (:locale).",
        "language_not_found" => "The language not found.",
        "future_date" => "The :attribute must be in the future.",
    ],

    "messages" => [
        "created" => "The language was created successfully.",
        "updated" => "The language was updated successfully.",
        "deleted" => "The language was deleted successfully.",
        "deleted_items" => "{1} One language was deleted.|[2,*] :count languages were deleted.",
        "status" => [
            "enable" => "{1} One language was enabled.|[2,*] :count languages were enabled.",
            "disable" => "{1} One language was disabled.|[2,*] :count languages were disabled.",
        ],
    ],

    "list" => [
        "filters" => [
            "name" => [
                "title" => "Name",
                "placeholder" => "Enter name",
            ],
        ],
        "columns" => [
            "flag" => "Flag",
            "locale" => "Locale",
            "direction" => "Direction",
            "calendar" => "Calendar Type",
        ],
    ],

    "form" => [
        "create" => [
            "title" => "Create Language",
        ],
        "edit" => [
            "title" => "Edit Language",
        ],
        "fields" => [
            "name" => [
                "title" => "Name",
                "placeholder" => "Enter name",
            ],
            "locale" => [
                "title" => "Locale",
                "placeholder" => "Enter locale",
            ],
            "direction" => [
                "title" => "Direction",
                "rtl" => "Right to Left (rtl)",
                "ltr" => "Left to Right (ltr)",
            ],
            "calendar" => [
                "title" => "Calendar",
                "placeholder" => "Select calendar",
            ],
            "flag" => [
                "title" => "Flag",
                "placeholder" => "Select flag",
            ],
        ],
    ],

];
