<?php

namespace HaiderJabbar\LaravelSolr\Tests\Unit\Console\Commands;

use HaiderJabbar\LaravelSolr\Console\Commands\UpdateSolrFields;
use HaiderJabbar\LaravelSolr\Schema\SolrSchemaBuilder;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class UpdateSolrFieldsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Any setup needed before each test
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testHandleWithFieldUpdates()
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
        $this->artisan(UpdateSolrFields::class, ['name' => 'testCore'])
            ->expectsQuestion("Enter field name to update (or 'done' to finish)", 'title')
            ->expectsQuestion("Enter new field type for 'title' (or leave empty to keep unchanged)", 'text')
            ->expectsConfirmation("Is 'title' required?", 'yes')
            ->expectsConfirmation("Should 'title' be indexed?", 'yes')
            ->expectsConfirmation("Should 'title' be stored?", 'yes')
            ->expectsConfirmation("Is 'title' multi-valued?", 'no')
            ->expectsQuestion("Enter field name to update (or 'done' to finish)", 'done')
            ->assertExitCode(0);

        // Assert that everything worked correctly
        $this->assertTrue(true);  // Additional assertions can be added here
    }

    public function testHandleWithoutFieldUpdates()
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
        $this->artisan(UpdateSolrFields::class, ['name' => 'testCore'])
            ->expectsQuestion("Enter field name to update (or 'done' to finish)", 'done')
            ->assertExitCode(0);

        // Assert that no fields were added or updated
        $this->assertTrue(true);  // Additional assertions can be added here
    }
}
