<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ExportApiDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Scramble OpenAPI docs and group schemas by type (Models vs Requests)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Exporting raw OpenAPI JSON via Scramble...');
        $exitCode = Artisan::call('scramble:export', [
            '--path' => 'docs/api/openapi.json'
        ]);

        if ($exitCode !== 0) {
            $this->error('Failed to export OpenAPI JSON.');
            return $exitCode;
        }

        $path = base_path('docs/api/openapi.json');
        if (!file_exists($path)) {
            $this->error('openapi.json not found at ' . $path);
            return 1;
        }

        $this->info('Grouping schemas by type...');
        $json = file_get_contents($path);
        $data = json_decode($json, true);

        if (!isset($data['components']['schemas'])) {
            $this->warn('No schemas found to group.');
            return 0;
        }

        $schemas = $data['components']['schemas'];
        $newSchemas = [];
        $replacements = [];

        foreach ($schemas as $key => $schema) {
            if (str_ends_with($key, 'Request')) {
                $newKey = 'Requests.' . $key;
            } elseif (str_ends_with($key, 'Resource') || str_ends_with($key, 'Collection')) {
                $newKey = 'Resources.' . $key;
            } else {
                // Default everything else to Models
                $newKey = 'Models.' . $key;
            }
            
            $newSchemas[$newKey] = $schema;
            $replacements['"#/components/schemas/'.$key.'"'] = '"#/components/schemas/'.$newKey.'"';
        }

        // Sort them alphabetically by the new key
        ksort($newSchemas);
        $data['components']['schemas'] = $newSchemas;

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Replace all $refs in the whole JSON document
        $json = strtr($json, $replacements);

        file_put_contents($path, $json);

        $this->info('Successfully grouped schemas and saved to ' . $path);
        
        return 0;
    }
}
