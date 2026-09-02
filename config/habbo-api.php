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
    | HTTP request timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | How long a single request may wait for a hotel to respond before the
    | client throws a ConnectionException. This is not a rate limit — it does
    | not control how often you may call the API.
    |
    */

    'request_timeout' => (int) env('HABBO_API_REQUEST_TIMEOUT', 15),

];
