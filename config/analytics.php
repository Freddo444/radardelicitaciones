<?php

$websiteId = (string) env('UMAMI_WEBSITE_ID', '3a71e47e-8466-4078-b759-462a63b46135');

return [

    'umami' => [
        'enabled' => filter_var(env('UMAMI_ENABLED', true), FILTER_VALIDATE_BOOL) && $websiteId !== '',

        'script_url' => env('UMAMI_SCRIPT_URL', 'https://analytics.radardelicitaciones.com/script.js'),

        'website_id' => $websiteId,

        /*
         * Admin panel: set UMAMI_ADMIN_WEBSITE_ID to a second Umami site to keep traffic separate.
         * If unset, the main website_id is used. Set UMAMI_ADMIN_ENABLED=false to disable admin tracking.
         */
        'admin' => [
            'enabled' => filter_var(env('UMAMI_ADMIN_ENABLED', true), FILTER_VALIDATE_BOOL),
            'website_id' => env('UMAMI_ADMIN_WEBSITE_ID'),
        ],

        /** Optional comma-separated hostnames for Umami data-domains (e.g. app.example.com,www.example.com) */
        'domains' => env('UMAMI_DOMAINS'),
    ],

];
