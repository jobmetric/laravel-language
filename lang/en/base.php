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

    "name" => "Languages",

    "validation" => [
        "locale" => "The :attribute (:locale) has already been taken.",
        "language_exist" => "The language does not exist in locale (:locale).",
        "language_not_found" => "The language not found.",
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

    "exceptions" => [
        "language_data_not_exist" => "The language data does not exist in locale (:locale).",
    ],

];
