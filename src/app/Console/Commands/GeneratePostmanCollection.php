<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Command;
use ReflectionMethod;
use stdClass;

#[Signature('postman:generate')]
#[Description('Automatically generates a Postman collection based on routes and FormRequests')]
class GeneratePostmanCollection extends Command
{
    public function handle()
    {
        $routes = app('router')->getRoutes();

        $collection = [
            'info' => [
                'name' => env('APP_NAME', 'Laravel') . ' (Auto-Generated)',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'item' => [],
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => 'http://localhost',
                    'type' => 'string'
                ],
                [
                    'key' => 'token',
                    'value' => '',
                    'type' => 'string'
                ]
            ]
        ];

        $folders = [];

        foreach ($routes as $route) {
            $uri = $route->uri();
            if (strpos($uri, 'api/') !== 0)
                continue;

            $methods = array_diff($route->methods(), ['HEAD']);
            if (empty($methods))
                continue;
            $method = reset($methods);

            $testUri = preg_replace('/\{[a-zA-Z0-9_]+\}/', '1', $uri);

            $parts = explode('/', $uri);
            $folderName = isset($parts[1]) ? ucfirst($parts[1]) : 'Other';

            if (!isset($folders[$folderName])) {
                $folders[$folderName] = [
                    'name' => $folderName,
                    'item' => []
                ];
            }

            $request = [
                'method' => $method,
                'header' => [
                    [
                        'key' => 'Accept',
                        'value' => 'application/json',
                        'type' => 'text'
                    ]
                ],
                'url' => [
                    'raw' => '{{base_url}}/' . $testUri,
                    'host' => ['{{base_url}}'],
                    'path' => explode('/', $testUri)
                ]
            ];

            $middlewares = $route->gatherMiddleware();
            $needsAuth = false;
            foreach ($middlewares as $mw) {
                if (strpos($mw, 'auth') !== false || strpos($mw, 'role:') !== false) {
                    $needsAuth = true;
                    break;
                }
            }

            if ($needsAuth) {
                $request['auth'] = [
                    'type' => 'bearer',
                    'bearer' => [
                        [
                            'key' => 'token',
                            'value' => '{{token}}',
                            'type' => 'string'
                        ]
                    ]
                ];
            }

            $bodyData = new stdClass();
            $description = '';
            $rules = [];
            $controllerCode = '';
            $action = $route->getAction();

            if (isset($action['controller']) && strpos($action['controller'], '@') !== false) {
                list($controller, $methodName) = explode('@', $action['controller']);
                try {
                    $reflector = new ReflectionMethod($controller, $methodName);
                    
                    // Extract controller method source code
                    $filename = $reflector->getFileName();
                    $startLine = $reflector->getStartLine() - 1;
                    $endLine = $reflector->getEndLine();
                    $length = $endLine - $startLine;
                    $source = file($filename);
                    $controllerCode = implode("", array_slice($source, $startLine, $length));

                    foreach ($reflector->getParameters() as $parameter) {
                        $type = $parameter->getType();
                        if ($type && !$type->isBuiltin()) {
                            $class = $type->getName();
                            if (is_subclass_of($class, \Illuminate\Foundation\Http\FormRequest::class)) {
                                $formRequest = new $class();
                                if (method_exists($formRequest, 'rules')) {
                                    $rules = $formRequest->rules();
                                    $bodyData = $this->generateFakeDataFromRules($rules);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Exception in reflection: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
                }
            }

            if (env('GEMINI_API_KEY')) {
                $aiResult = $this->generateDocsViaAI($method, $testUri, $rules, $bodyData, $controllerCode);
                if (is_array($aiResult)) {
                    if (!empty($aiResult['request_body'])) {
                        $bodyData = $aiResult['request_body'];
                    }
                }
            }

            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $request['body'] = [
                    'mode' => 'raw',
                    'raw' => empty((array) $bodyData) ? "{\n\n}" : json_encode($bodyData, JSON_PRETTY_PRINT),
                    'options' => [
                        'raw' => [
                            'language' => 'json'
                        ]
                    ]
                ];
            }

            $this->info("Generated post body for $method $testUri");

            $folders[$folderName]['item'][] = [
                'name' => "[$method] $testUri",
                'request' => $request,
                'response' => []
            ];

            if (env('GEMINI_API_KEY')) {
                sleep(4); // Avoid Gemini free tier rate limit of 15 requests/min
            }
        }

        foreach ($folders as $folder) {
            $collection['item'][] = $folder;
        }

        $outputPath = base_path('postman_collection_generated.json');
        file_put_contents($outputPath, json_encode($collection, JSON_PRETTY_PRINT));
        $this->info("Postman collection successfully generated at: {$outputPath}");
    }

    private function generateFakeDataFromRules($rules)
    {
        $faker = \Faker\Factory::create();
        $data = [];
        $nestedData = [];

        foreach ($rules as $field => $ruleSet) {
            if (is_string($ruleSet)) {
                $ruleSet = explode('|', $ruleSet);
            }

            $value = $faker->word;

            $isEmail = false;
            $isNumeric = false;
            $isBoolean = false;
            $isArray = false;

            foreach ($ruleSet as $rule) {
                if ($rule === 'email')
                    $isEmail = true;
                if ($rule === 'numeric' || $rule === 'integer')
                    $isNumeric = true;
                if ($rule === 'boolean')
                    $isBoolean = true;
                if ($rule === 'array')
                    $isArray = true;
            }

            if ($isEmail) {
                $value = 'john.doe@example.com';
            } elseif ($isNumeric) {
                $value = 1;
            } elseif ($isBoolean) {
                $value = true;
            } elseif ($isArray) {
                $value = [];
            }

            // Hardcode common predictable values
            if (strpos($field, 'password') !== false) {
                $value = 'Password123!';
            } elseif ($field === 'name') {
                $value = 'John Doe';
            } elseif ($field === 'role') {
                $value = 'customer';
            } elseif ($field === 'shop_name') {
                $value = 'Johns Shop';
            } elseif ($field === 'quantity') {
                $value = 2;
            } elseif ($field === 'price') {
                $value = 99.99;
            } elseif (strpos($field, 'quantity') !== false || strpos($field, 'stock') !== false) {
                $value = $faker->numberBetween(1, 100);
            }

            // Check for 'in:' rules
            foreach ($ruleSet as $rule) {
                if (is_string($rule) && strpos($rule, 'in:') === 0) {
                    $options = explode(',', substr($rule, 3));
                    $value = $options[array_rand($options)]; // pick random option
                }
            }

            if (strpos($field, '.*.') !== false || strpos($field, '.') !== false) {
                // Nested arrays (e.g. items.*.product_id)
                $nestedData[$field] = $value;
            } else {
                $data[$field] = $value;
            }
        }

        // Attempt to build nested structure
        foreach ($nestedData as $field => $val) {
            $parts = explode('.', $field);
            if (count($parts) == 3 && $parts[1] === '*') {
                // e.g. items.*.product_id
                $parent = $parts[0];
                $child = $parts[2];
                if (!isset($data[$parent]) || !is_array($data[$parent])) {
                    $data[$parent] = [[]];
                }
                if (empty($data[$parent])) {
                    $data[$parent][] = [];
                }
                $data[$parent][0][$child] = $val;
            }
        }

        return $data;
    }

    private function generateDocsViaAI($method, $uri, $rules, $bodyData, $controllerCode = '')
    {
        $context = "";
        $readmePath = base_path('../OVERVIEW.md');
        if (!file_exists($readmePath)) {
            $readmePath = base_path('README.md');
        }
        if (file_exists($readmePath)) {
            $context = substr(file_get_contents($readmePath), 0, 3000);
        }

        $rulesJson = json_encode($rules, JSON_PRETTY_PRINT);
        
        $prompt = "You are an API documentation expert. Generate realistic Postman documentation for the following endpoint.
        Context about the app:
        {$context}

        Endpoint: {$method} /{$uri}
        Validation Rules (for request body):
        {$rulesJson}
        
        Controller Logic (to determine the exact response format):
        ```php
        {$controllerCode}
        ```

        Return ONLY valid JSON with exactly these three keys:
        {
            \"overview\": \"A brief 1-2 sentence overview of what this endpoint specifically does.\",
            \"request_body\": {\"realistic_key\": \"realistic_value\"},
            \"response_body\": {\"message\": \"Success\", \"data\": {\"realistic_key\": \"realistic_value\"}}
        }
        
        CRITICAL: The `response_body` MUST reflect the EXACT JSON structure and keys returned by the Controller Logic provided above! Analyze the code and make the response data highly realistic.
        If there are no validation rules and it's a GET request, request_body should be empty {}. Do NOT include Markdown formatting in the response, just the JSON.";

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => env('GEMINI_API_KEY'),
                'Content-Type' => 'application/json'
            ])->timeout(30)->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent', [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    $text = $result['candidates'][0]['content']['parts'][0]['text'];
                    $data = json_decode($text, true);
                    return is_array($data) ? $data : false;
                }
            } else {
                $this->error("Gemini API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());
        }

        return false;
    }


}
