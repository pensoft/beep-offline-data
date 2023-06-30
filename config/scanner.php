<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scanner settings
    |--------------------------------------------------------------------------
    |
    | These are the default scanner settings. These values are used for each
    | scan request
    |
    */

    'token_secret' => env('SCANNER_TOKEN_SECRET'),

    'base_directory' => env('SCANNER_BASE_DIRECTORY', 'app/public/scanner/scans'),

    'mode_debug' => env('SCANNER_MODE_DEBUG', true),

    'log_mode' => env('SCANNER_LOG_MODE', 'debug'),

    'area_adjustment' => env('SCANNER_AREA_ADJUSTMENT_PERCENTAGE', 90),

    'checkbox_tolerance' => env('SCANNER_CHECKBOX_TOLERANCE', 30),

    'svg' => [
        'width'  => env('SVG_PAGE_WIDTH_MM', 210),
        'height' => env('SVG_PAGE_HEIGHT_MM', 297),
    ],

    'ocr_engine' => env('SCANNER_DEFAULT_OCR_ENGINE', 'tesseract'),
];
