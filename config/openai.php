<?php

return [
    'endpoint' => env('OPENAI_ENDPOINT', 'https://api.openai.com/v1'),
    'key' => env('OPENAI_KEY', ''),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'timeout' => (int) env('OPENAI_TIMEOUT', 30),
];
