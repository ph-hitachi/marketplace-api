<?php

namespace Tests\Unit;

use Tests\TestCase;

class GeneratePostmanCollectionTest extends TestCase
{
    /**
     * Test the generated postman collection structure and contents.
     */
    public function test_postman_collection_generation_integrity(): void
    {
        $collectionPath = base_path('docs/api/postman_collection.json');
        
        $this->assertFileExists($collectionPath, "Generated postman collection must exist.");
        
        $json = json_decode(file_get_contents($collectionPath), true);
        
        $this->assertIsArray($json);
        $this->assertArrayHasKey('info', $json);
        $this->assertArrayHasKey('item', $json);
        $this->assertArrayHasKey('variable', $json);

        // Assert base url variable
        $variables = $json['variable'];
        $baseUrlVar = collect($variables)->firstWhere('key', 'base_url');
        $this->assertNotNull($baseUrlVar);
        $this->assertEquals('http://localhost/api', $baseUrlVar['value']);

        // Check for generated routes/folders structure
        $items = $json['item'];
        $this->assertNotEmpty($items);

        // Retrieve specific request items and check constraint resolution
        $customerFolder = collect($items)->firstWhere('name', 'Customer');
        $this->assertNotNull($customerFolder, "Customer folder should exist in generated collection.");

        // Traverse down to orders creation
        $ordersFolder = collect($customerFolder['item'])->firstWhere('name', 'Orders');
        $this->assertNotNull($ordersFolder, "Customer/Orders folder should exist.");

        $placeOrderReq = collect($ordersFolder['item'])->firstWhere('name', 'Place order');
        $this->assertNotNull($placeOrderReq, "Place order endpoint should exist.");

        $body = $placeOrderReq['request']['body']['raw'] ?? null;
        $this->assertNotNull($body, "Place order request body should exist.");

        $bodyData = json_decode($body, true);
        $this->assertIsArray($bodyData);
        $this->assertArrayHasKey('payment_method', $bodyData);
        
        // Assert enum value resolved from OpenAPI spec ("wallet" or "cod" are the allowed enum values)
        $this->assertContains($bodyData['payment_method'], ['wallet', 'cod'], "payment_method must resolve to allowed enum options.");

        $this->assertArrayHasKey('items', $bodyData);
        $this->assertIsArray($bodyData['items']);
        $this->assertNotEmpty($bodyData['items']);
        
        $orderItem = $bodyData['items'][0];
        $this->assertArrayHasKey('quantity', $orderItem);
        // Assert minimum bound (quantity >= 1) resolved from schema
        $this->assertGreaterThanOrEqual(1, $orderItem['quantity'], "quantity must be greater than or equal to 1 based on minimum schema constraint.");
    }
}
