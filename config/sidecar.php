<?php

return [
    /*
     * All of your function classes that you'd like to deploy go here.
     */
    'functions' => [
        \Wnx\SidecarBrowsershot\Functions\BrowsershotFunction::class,
    ],

    /*
     * Your AWS key. See the Sidecar docs for more info.
     * @see https://hammerstone.dev/sidecar/docs/main/configuration
     */
    'aws_key' => env('SIDECAR_ACCESS_KEY_ID'),

    /*
     * Your AWS secret. See the Sidecar docs for more info.
     * @see https://hammerstone.dev/sidecar/docs/main/configuration
     */
    'aws_secret' => env('SIDECAR_SECRET_ACCESS_KEY'),

    /*
     * The AWS region that your Lambda function will be deployed to.
     */
    'aws_region' => env('SIDECAR_REGION', 'eu-central-1'),

    /*
     * The S3 bucket that Sidecar will use to store your Lambda function's
     * deployment package. This bucket must be in the same region as your
     * Lambda function.
     */
    'aws_bucket' => env('SIDECAR_ARTIFACT_BUCKET_NAME'),

    /*
     * The execution role that your Lambda function will use.
     * @see https://hammerstone.dev/sidecar/docs/main/configuration#execution-role
     */
    'execution_role' => env('SIDECAR_EXECUTION_ROLE'),

    /*
     * The base name for your Lambda functions. By default, this is your
     * application name followed by the environment.
     */
    'name' => env('SIDECAR_APP_NAME', env('APP_NAME', 'Laravel')),

    /*
     * The environment name. This is used to namespace your Lambda functions.
     */
    'env' => env('SIDECAR_ENV', env('APP_ENV', 'production')),
];
