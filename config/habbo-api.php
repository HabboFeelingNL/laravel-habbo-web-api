<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default hotel domain
    |--------------------------------------------------------------------------
    |
    | The hotel the client talks to when no other hotel is selected with
    | HabboApi::hotel(). Any form is accepted ("habbo.com", "www.habbo.com",
    | "https://www.habbo.com/") and normalised to the "www." public host.
    |
    */

    'domain' => env('HABBO_API_DOMAIN', 'www.habbo.com'),

    /*
    |--------------------------------------------------------------------------
    | Request timeout (seconds)
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('HABBO_API_TIMEOUT', 15),

];
