<?php

namespace Tests\Unit;

use HaiderJabbar\LaravelSolr\Services\FieldsSolrService;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tests\TestCase;

// This imports Laravel's TestCase
use Illuminate\Support\Facades\Http;

class FieldsSolrServiceTest extends TestCase
{
    protected $service;
    protected $mockOutput;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockOutput = $this->createMock(ConsoleOutput::class);
        $this->mockOutput->method('writeln')->willReturn(null);

        $this->service = new FieldsSolrService($this->mockOutput);
    }

    public function testAddFieldsToCore()
    {
        // Prepare the core name and fields to be added
        $coreName = 'test_core';
        $fields = [
            ['name' => 'field1', 'type' => 'string'],
            ['name' => 'field2', 'type' => 'string'],
        ];

        // Fake the HTTP requests
        Http::fake([
            // Fake the call to createCoreIfNotExists
            'solr-server-url/solr/admin/cores?action=STATUS&core=' . $coreName => Http::response([
                'status' => 'ok', // Assuming this is the response if core exists
            ], 200),

            // Fake modifyField (add-field action)
            'solr-server-url/solr/' . $coreName . '/schema' => Http::response([
                'responseHeader' => ['status' => 0],
            ], 200),
        ]);

        // Call your method
        $result = $this->service->addFieldsToCore($coreName, $fields);

        // Assert that the fields were added successfully
        $this->assertTrue($result);


    }

    public function testAddFieldsToCoreWithError()
    {
        $coreName = 'testCore';
        $fields = [
            ['name' => 'field1', 'type' => 'string'],
            ['name' => 'field2', 'type' => 'string'],
        ];

        // Fake a success on the first call, and an error on the second call
        Http::fake([
            '*' => Http::sequence()
                ->push(['responseHeader' => ['status' => 0]], 200) // First request success
                ->push(['error' => ['msg' => 'Field already exists']], 400) // Second request failure
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Migration halted: Field already exists in the Solr core.');

        $this->service->addFieldsToCore($coreName, $fields);
    }

    public function testUpdateFieldsInCore()
    {
        $coreName = 'testCore';
        $fields = [
            ['name' => 'field1', 'type' => 'string'],
            ['name' => 'field2', 'type' => 'string'],
        ];

        Http::fake([
            '*' => Http::response(['responseHeader' => ['status' => 0]], 200)
        ]);

        $this->service->updateFieldsInCore($coreName, $fields);

        // Assert that no exception was thrown
        $this->assertTrue(true);
    }

    public function testDeleteFieldsFromCore()
    {
        $coreName = 'testCore';
        $fieldNames = ['field1', 'field2'];

        Http::fake([
            '*' => Http::response(['responseHeader' => ['status' => 0]], 200)
        ]);

        $this->service->deleteFieldsFromCore($coreName, $fieldNames);

        // Assert that no exception was thrown
        $this->assertTrue(true);
    }

    public function testDeleteFieldsFromCoreAllFail()
    {
        $coreName = 'testCore';
        $fieldNames = ['field1', 'field2'];

        Http::fake([
            '*' => Http::response(['error' => ['msg' => 'Field does not exist']], 400)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed to delete any fields from core 'testCore'.");

        $this->service->deleteFieldsFromCore($coreName, $fieldNames);
    }

    public function testGetCoreFields()
    {
        $coreName = 'testCore';
        $expectedFields = [
            ['name' => 'field122www', 'type' => 'string'],
//            ['name' => 'field2', 'type' => 'int'],
        ];

        Http::fake([
            '*' => Http::response(['fields' => $expectedFields], 200)
        ]);

        $result = $this->service->getCoreFields($coreName);
        $this->assertEquals($expectedFields, $result);
    }

    public function testGetCoreFieldsWithError()
    {
        $coreName = 'testCore';

        Http::fake([
            '*' => Http::response([], 200) // Simulate a server error
        ]);

        $result = $this->service->getCoreFields($coreName);

        $this->assertEquals([], $result);
    }
}
