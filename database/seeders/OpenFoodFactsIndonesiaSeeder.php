<?php

namespace Database\Seeders;

use App\Modules\Products\Services\ExternalBarcodeSyncService;
use Illuminate\Database\Seeder;

class OpenFoodFactsIndonesiaSeeder extends Seeder
{
    public function run(): void
    {
        $syncService = app(ExternalBarcodeSyncService::class);
        $result = $syncService->sync([
            ExternalBarcodeSyncService::SOURCE_OPENFOODFACTS,
        ]);

        if ($this->command) {
            $this->command->line(sprintf(
                'OpenFoodFacts sync done. processed=%d inserted=%d updated=%d invalid=%d',
                $result['processed'],
                $result['inserted'],
                $result['updated'],
                $result['invalid'],
            ));
        }
    }
}
