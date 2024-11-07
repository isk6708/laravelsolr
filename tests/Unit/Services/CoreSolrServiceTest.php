<?php

namespace HaiderJabbar\LaravelSolr\Tests\Unit\Services;

use Exception;
use HaiderJabbar\LaravelSolr\Services\CoreSolrService;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\ConsoleOutput;

class CoreSolrServiceTest extends TestCase
{
    protected $solrService;
    protected $mockConsoleOutput;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock ConsoleOutput to avoid actual console output during the test
        $this->mockConsoleOutput = Mockery::mock(ConsoleOutput::class);

        // Inject the mocked ConsoleOutput into the service
        $this->solrService = new CoreSolrService();
        $this->solrService->output = $this->mockConsoleOutput; // Set the mocked output
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateCoreIfNotExists_CoreAlreadyExists()
    {
        // Simulate core already exists
        $this->solrService = $this->getMockBuilder(CoreSolrService::class)
            ->onlyMethods(['checkCoreExists', 'createCore'])
            ->getMock();

        $this->solrService->method('checkCoreExists')->willReturn(true);

        $result = $this->solrService->createCoreIfNotExists('testCore');
        $this->assertTrue($result);
    }

    public function testCreateCoreIfNotExists_CoreDoesNotExist_CreatesCore()
    {
        $this->solrService = $this->getMockBuilder(CoreSolrService::class)
            ->onlyMethods(['checkCoreExists', 'createCore'])
            ->getMock();

        $this->solrService->method('checkCoreExists')->willReturn(false);
        $this->solrService->method('createCore')->willReturn(true);

        $result = $this->solrService->createCoreIfNotExists('testCore');
        $this->assertTrue($result);
    }

    public function testCreateCore_SuccessfullyCreatesCore()
    {
        // Simulate core does not exist, and we can successfully create it
        $this->solrService = $this->getMockBuilder(CoreSolrService::class)
            ->onlyMethods(['checkCoreExists', 'makeRequest'])
            ->getMock();

        $this->solrService->method('checkCoreExists')->willReturn(false);
        $this->solrService->method('makeRequest')->willReturn(json_encode(['responseHeader' => ['status' => 0]]));

        $result = $this->solrService->createCore('testCore');
        $this->assertTrue($result);
    }

    public function testCheckCoreExists_ReturnsTrueIfCoreExists()
    {
        $this->solrService = $this->getMockBuilder(CoreSolrService::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $this->solrService->method('makeRequest')
            ->willReturn(json_encode(['status' => ['testCore' => []]]));

        $result = $this->solrService->checkCoreExists('testCore');

        $this->assertTrue($result);
    }

    public function testCheckCoreExists_ReturnsFalseIfCoreDoesNotExist()
    {
        $this->solrService = $this->getMockBuilder(CoreSolrService::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $this->solrService->method('makeRequest')
            ->willReturn(json_encode(['status' => []]));

        $result = $this->solrService->checkCoreExists('testCore');
        $this->assertFalse($result);
    }

    public function testDeleteCore_SuccessfullyDeletesCore()
    {
        $this->solrService = $this->getMockBuilder(CoreSolrService::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $this->solrService->method('makeRequest')
            ->willReturn(json_encode(['responseHeader' => ['status' => 0]]));

        $result = $this->solrService->deleteCore('testCore');
        $this->assertIsArray($result);
        $this->assertEquals(['responseHeader' => ['status' => 0]], $result);
    }

    public function testDeleteCore_ThrowsExceptionOnFailure()
    {
        $this->solrService = $this->getMockBuilder(CoreSolrService::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $this->solrService->method('makeRequest')
            ->willReturn('');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Empty response received while deleting core 'testCore'.");

        $this->solrService->deleteCore('testCore');
    }

    public function testUpdateCore_SuccessfullyUpdatesCore()
    {
        $this->solrService = $this->getMockBuilder(CoreSolrService::class)
            ->onlyMethods(['checkCoreExists', 'makeRequest'])
            ->getMock();

        $this->solrService->method('checkCoreExists')->willReturn(true);
        $this->solrService->method('makeRequest')
            ->willReturn(json_encode(['responseHeader' => ['status' => 0]]));

        $result = $this->solrService->updateCore('oldCore', 'newCore');
        $this->assertTrue($result);
    }

    public function testUpdateCore_ThrowsExceptionIfOldCoreDoesNotExist()
    {
        $this->solrService = $this->getMockBuilder(CoreSolrService::class)
            ->onlyMethods(['checkCoreExists'])
            ->getMock();

        $this->solrService->method('checkCoreExists')->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Core 'oldCore' does not exist in Solr.");

        $this->solrService->updateCore('oldCore', 'newCore');
    }
}
