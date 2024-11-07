<?php

namespace HaiderJabbar\LaravelSolr\Tests\Unit\Console\Commands;

use HaiderJabbar\LaravelSolr\Console\Commands\DeleteSolrFields;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class DeleteSolrFieldsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testHandleWithFieldsToDelete()
    {
        // Mock the File facade to prevent actual file creation
        File::shouldReceive('put')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('string'));

        // Mock the FieldsSolrService that will be used in the migration stub
        $fieldsSolrServiceMock = Mockery::mock('overload:HaiderJabbar\LaravelSolr\Services\FieldsSolrService');
        $fieldsSolrServiceMock->shouldReceive('deleteFieldsFromCore')
            ->once()
            ->with('testCore', ['title', 'author']);

        // Simulate running the command with mocked interaction
        $this->artisan(DeleteSolrFields::class, ['coreName' => 'testCore'])
            ->expectsQuestion("Enter field name to delete (or 'done' to finish)", 'title')
            ->expectsQuestion("Enter field name to delete (or 'done' to finish)", 'author')
            ->expectsQuestion("Enter field name to delete (or 'done' to finish)", 'done')
            ->assertExitCode(0);

        // Assert that the correct operations were invoked
        $this->assertTrue(true); // Additional assertions can be added if necessary
    }

    public function testHandleWithoutFieldsToDelete()
    {
        // Mock the File facade
        File::shouldReceive('put')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('string'));

        // Mock the FieldsSolrService but expect no calls since no fields are being deleted
        $fieldsSolrServiceMock = Mockery::mock('overload:HaiderJabbar\LaravelSolr\Services\FieldsSolrService');
        $fieldsSolrServiceMock->shouldReceive('deleteFieldsFromCore')
            ->never();

        // Simulate running the command with no fields entered
        $this->artisan(DeleteSolrFields::class, ['coreName' => 'testCore'])
            ->expectsQuestion("Enter field name to delete (or 'done' to finish)", 'done')
            ->assertExitCode(0);

        // Assert that no fields were deleted
        $this->assertTrue(true); // Additional assertions can be added if necessary
    }
}
