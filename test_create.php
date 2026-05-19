<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::transaction(function () {
    $log = \App\Models\Log::create([
        'log_id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => 1,
        'log_title' => 'Test Log',
        'logged_at' => now()
    ]);

    // let's insert a selected_medication
    $smed = \App\Models\SelectedMedication::create([
        'user_id' => 1,
        'medication_name' => 'Aspirin'
    ]);

    $recordMedication = \App\Models\RecordMedication::create([
        'log_id' => $log->log_id,
        'user_id' => 1,
        'notes' => 'Test'
    ]);

    dump("After create, recordMedication ID is: " . $recordMedication->medication_id);

    \App\Models\SelectedMedication::whereIn('selected_med_id', [$smed->selected_med_id])
        ->update([
            'medication_id' => $recordMedication->medication_id
        ]);

    $log->load('recordMedication.selectedMedications');
    dump("Accessor:", $log->recordMedication->medications);
    dump("Real Relation:", $log->recordMedication->selectedMedications->toArray());
});