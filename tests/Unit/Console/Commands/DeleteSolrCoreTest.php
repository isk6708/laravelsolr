<?php

namespace HaiderJabbar\LaravelSolr\Tests\Unit\Console\Commands;

use HaiderJabbar\LaravelSolr\Console\Commands\DeleteSolrCore;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteSolrCoreTest extends TestCase
{

    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new DeleteSolrCore();
    }

    public function testDeleteSolrCoreCommand()
    {
        // Mock the File facade to simulate file creation
        File::shouldReceive('put')
            ->once()
            ->andReturn(true);

        // Execute the command
        $coreName = 'test_core22w';
        $result = Artisan::call('solr:delete-core', ['name' => $coreName]);

        // Assert that the command was successful
        $this->assertEquals(0, $result);
    }

    public function testMigrationStubContent()
    {
        $coreName = 'test_core';
        $stub = $this->command->getMigrationStub($coreName);

        $this->assertStringContainsString('use Illuminate\\Database\\Migrations\\Migration;', $stub);
        $this->assertStringContainsString('use HaiderJabbar\\LaravelSolr\\Services\\CoreSolrService;', $stub);
        $this->assertStringContainsString('public function up()', $stub);
        $this->assertStringContainsString('public function down()', $stub);
        $this->assertStringContainsString("\$this->coreSolrService->deleteCore('{$coreName}');", $stub);
    }

    public function testCommandDescription()
    {
        $this->assertEquals('Delete  Solr core and a migration file', $this->command->getDescription());
    }

    public function testCommandSignature()
    {
        $this->assertEquals('solr:delete-core', $this->command->getName());
    }
}
