<?php

namespace HaiderJabbar\LaravelSolr\Tests\Unit\Console\Commands;

use HaiderJabbar\LaravelSolr\Console\Commands\CreateSolrCore;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Composer\InstalledVersions;
use Illuminate\Foundation\Console\AboutCommand;
class CreateSolrCoreTest extends TestCase
{
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new CreateSolrCore();
    }

    public function testCreateSolrCoreCommand()
    {
        // Mock the File facade to simulate file creation
        File::shouldReceive('put')
            ->once()
            ->andReturn(true);

        // Execute the command
        $coreName = 'test_core22w';
        $result = Artisan::call('solr:create-core', ['name' => $coreName]);

        // Assert that the command was successful
        $this->assertEquals(0, $result);
    }

    public function testMigrationStubContent()
    {
        $coreName = 'test_core22w';
        $migrationContent = $this->command->getMigrationStub($coreName);
        $this->assertStringContainsString("createCore('{$coreName}')", $migrationContent);
        $this->assertStringContainsString("deleteCore('{$coreName}')", $migrationContent);
    }
}
