<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class GenerateOverviewDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'overview:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a high-level project overview (README.md) using Gemini AI';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating project overview via AI...');

        $basePath = base_path();
        
        // Backup existing OVERVIEW.md if it exists
        $overviewPath = $basePath . '/../OVERVIEW.md';
        if (File::exists($overviewPath)) {
            $backupPath = $basePath . '/../OVERVIEW.md.bak';
            File::copy($overviewPath, $backupPath);
            $this->info("Backed up existing OVERVIEW.md to OVERVIEW.md.bak");
        }

        // Create a prompt that explains the goal
        $prompt = "You are a senior technical writer documenting a Laravel E-commerce backend API project.
Please generate a comprehensive `README.md` overview that documents the project flow, roles, and endpoints.

The application has the following roles:
- Customer: Can browse products, manage addresses, place orders.
- Seller: Can manage their seller profile, manage their products, manage their orders.
- Admin: Can manage all users and oversee all orders globally.

The authentication is token-based using Laravel Sanctum.
Please structure the documentation beautifully with markdown. Include sections like:
- Project Overview
- System Architecture & Roles
- API Endpoints summary
- Authentication flow

Keep the output strictly in Markdown format.";

        $this->info('Calling Gemini API (gemini-flash-latest)...');

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => env('GEMINI_API_KEY'),
                'Content-Type' => 'application/json'
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent', [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature' => 0.4
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    $markdown = $result['candidates'][0]['content']['parts'][0]['text'];
                    
                    // The user requested it to be generated as README.md in root src
                    $readmePath = $basePath . '/README.md';
                    File::put($readmePath, $markdown);
                    
                    $this->info("Successfully generated README.md at: " . $readmePath);
                } else {
                    $this->error("Failed to parse AI response structure.");
                }
            } else {
                $this->error("Gemini API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
        }
    }
}
