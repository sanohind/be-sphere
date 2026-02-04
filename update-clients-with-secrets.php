<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Update SCOPE client (ID: 1, client_id: 'scope-client')
$scopeSecret = env('SCOPE_APPLICATION_CLIENT_SECRET', 'RpFsx4BNQXpgILGvpY7rbwY1QvZLtwk4RQvuhmIL');
DB::table('oauth_clients')
    ->where('id', 1)
    ->update([
        'client_secret' => $scopeSecret,
        'is_confidential' => true, // Make it confidential since it has a secret
    ]);
echo "Updated SCOPE client (ID: 1, client_id: scope-client)\n";
echo "  Secret: {$scopeSecret}\n";

// Update AMS client (ID: 2, client_id: 'ams-client')
$amsSecret = env('AMS_ARRIVAL_MANAGEMENT_SYSTEM_CLIENT_SECRET', 'ltJeOVUz7vbVm3KDwXrN1HMRtMoZGrofr7W5Bc2t');
DB::table('oauth_clients')
    ->where('id', 2)
    ->update([
        'client_secret' => $amsSecret,
        'is_confidential' => true, // Make it confidential since it has a secret
    ]);
echo "Updated AMS client (ID: 2, client_id: ams-client)\n";
echo "  Secret: {$amsSecret}\n";

echo "\nDone! Clients are now configured with secrets.\n";
