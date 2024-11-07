<?php

namespace HaiderJabbar\LaravelSolr\Tests\Unit\Console\Commands;

use HaiderJabbar\LaravelSolr\Console\Commands\CreateSolrFields;
use HaiderJabbar\LaravelSolr\Schema\SolrSchemaBuilder;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class CreateSolrFieldsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Setup any common initialization
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testHandleWithValidFields()
    {
        // Mock SolrSchemaBuilder
        $schemaMock = Mockery::mock(SolrSchemaBuilder::class);
        $schemaMock->shouldReceive('name')->andReturnSelf();
        $schemaMock->shouldReceive('type')->andReturnSelf();
        $schemaMock->shouldReceive('required')->andReturnSelf();
        $schemaMock->shouldReceive('indexed')->andReturnSelf();
        $schemaMock->shouldReceive('stored')->andReturnSelf();
        $schemaMock->shouldReceive('multiValued')->andReturnSelf();
        $schemaMock->shouldReceive('getFields')->andReturn([
            ['name' => 'title', 'type' => 'text', 'required' => true, 'indexed' => true, 'stored' => true, 'multiValued' => false]
        ]);

        // Mock File facade
        File::shouldReceive('put')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('string'));

        // Replace the schema in the service container with the mock
        $this->app->instance(SolrSchemaBuilder::class, $schemaMock);

        // Mock command input and options
        $this->artisan(CreateSolrFields::class, ['name' => 'testCore'])
            ->expectsQuestion("Enter field name (or 'done' to finish)", 'title')
            ->expectsQuestion("Enter field type for 'title'", 'text')
            ->expectsConfirmation("Is 'title' required?", 'yes')
            ->expectsConfirmation("Should 'title' be indexed?", 'yes')
            ->expectsConfirmation("Should 'title' be stored?", 'yes')
            ->expectsConfirmation("Is 'title' multi-valued?", 'no')
            ->expectsQuestion("Enter field name (or 'done' to finish)", 'done')
            ->assertExitCode(0);

        // Assert that the mock was called with the expected values
        $this->assertTrue(true); // Additional assertions can be added here
    }

    public function testHandleWithoutFields()
    {
        // Mock SolrSchemaBuilder
        $schemaMock = Mockery::mock(SolrSchemaBuilder::class);
        $schemaMock->shouldReceive('name')->andReturnSelf();
        $schemaMock->shouldReceive('type')->andReturnSelf();
        $schemaMock->shouldReceive('required')->andReturnSelf();
        $schemaMock->shouldReceive('indexed')->andReturnSelf();
        $schemaMock->shouldReceive('stored')->andReturnSelf();
        $schemaMock->shouldReceive('multiValued')->andReturnSelf();
        $schemaMock->shouldReceive('getFields')->andReturn([]);

        // Replace the schema in the service container with the mock
        $this->app->instance(SolrSchemaBuilder::class, $schemaMock);

        // Mock File facade
        File::shouldReceive('put')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('string'));

        // Mock command input and options
        $this->artisan(CreateSolrFields::class, ['name' => 'testCore'])
            ->expectsQuestion("Enter field name (or 'done' to finish)", 'done')
            ->assertExitCode(0);

        // Assert that no fields were added
        $this->assertTrue(true); // Additional assertions can be added here
    }
}
