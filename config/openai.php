<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | Your OpenAI API Key. This will be used to authenticate with the OpenAI API.
    | You can find your API key in your OpenAI dashboard.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Organization
    |--------------------------------------------------------------------------
    |
    | Your OpenAI Organization ID. This will be used to authenticate with the OpenAI API.
    | You can find your Organization ID in your OpenAI dashboard.
    |
    */

    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Default OpenAI Client Options
    |--------------------------------------------------------------------------
    |
    | The default OpenAI client options to use for API requests. These options
    | will be passed to the constructor of the client instance.
    |
    */

    'request_options' => [
        'timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),
        'http_errors' => env('OPENAI_REQUEST_HTTP_ERRORS', true),
    ],

];