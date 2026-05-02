<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'URL=' . (env('SUPABASE_URL') ?? '(empty)') . PHP_EOL;
echo 'KEY=' . (env('SUPABASE_SERVICE_ROLE_KEY') ? 'present' : 'missing') . PHP_EOL;
