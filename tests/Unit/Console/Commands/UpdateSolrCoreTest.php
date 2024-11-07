<?php

namespace HaiderJabbar\LaravelSolr\Tests\Unit\Console\Commands;

use HaiderJabbar\LaravelSolr\Console\Commands\UpdateSolrCore;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSolrCoreTest extends TestCase
{


    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new UpdateSolrCore();
    }

    public function testUpdateSolrCoreCommand()
    {
        // Mock the File facade to simulate file creation
        File::shouldReceive('put')
            ->once()
            ->andReturn(true);

        // Execute the command
        $coreName = 'test_core22w';
        $newCoreName = 'test_core22w2';
        $result = Artisan::call('solr:update-core', ['oldName' => $coreName,'newName' => $newCoreName]);

        // Assert that the command was successful
        $this->assertEquals(0, $result);
    }

    public function testMigrationStubContent()
    {
        $oldCoreName = 'test_core22w';
        $newCoreName = 'test_core22w2';
        $stub = $this->command->getMigrationStub($oldCoreName, $newCoreName);

        $this->assertStringContainsString('use Illuminate\\Database\\Migrations\\Migration;', $stub);
        $this->assertStringContainsString('use HaiderJabbar\\LaravelSolr\\Services\\CoreSolrService;', $stub);
        $this->assertStringContainsString('public function up()', $stub);
        $this->assertStringContainsString('public function down()', $stub);
        $this->assertStringContainsString("\$this->coreSolrService->updateCore('{$oldCoreName}', '{$newCoreName}');", $stub);
        $this->assertStringContainsString("\$this->coreSolrService->updateCore('{$newCoreName}', '{$oldCoreName}');", $stub);
    }
}
