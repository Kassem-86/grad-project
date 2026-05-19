<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$log = \App\Models\Log::with('recordMedication.selectedMedications')
    ->where('log_id', '1897383b-cf4c-41e9-8e28-2949d1dda75d')
    ->first();

if ($log->recordMedication) {
    dump("selectedMedications:", $log->recordMedication->selectedMedications->toArray());
    dump("medications:", $log->recordMedication->medications);
}
