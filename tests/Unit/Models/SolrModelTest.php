<?php

namespace HaiderJabbar\LaravelSolr\Tests\Unit\Models;


use Tests\TestCase;

// This imports Laravel's TestCase
use Illuminate\Support\Facades\Http;
use HaiderJabbar\LaravelSolr\Models\SolrModel;

class SolrModelTest extends TestCase
{
    protected $solrUrl;

    protected function setUp(): void
    {
        parent::setUp();
        // Assuming the SOLR_BASE_URL is set in the .env file
        $this->solrUrl = env('SOLR_BASE_URL', 'http://localhost:8983/solr');

    }

    public function testAddDocumentSuccess()
    {
        $coreName = 'test_core';
        $data = [
            'id' => '1',
            'title' => 'Test Documents'
        ];


        // Fake a successful HTTP response for adding a document
        Http::fake([
            "{$this->solrUrl}/{$coreName}/update?commit=true" => Http::response(null, 200)
        ]);

        $result = SolrModel::addDocument($coreName, $data);
        $this->assertTrue($result);
    }

    public function testUpdateDocumentSuccess()
    {
        $coreName = 'test_core';
        $data = [
            'id' => '1w1',
            'title' => 'Updated Document Title'
        ];


//         Fake a successful HTTP response for updating a document
        Http::fake([
            "{$this->solrUrl}/{$coreName}/update?commit=true" => Http::response(null, 200)
        ]);

        $result = SolrModel::updateDocument($coreName, $data);

        $this->assertTrue($result);
    }

    public function testDeleteDocumentByIdSuccess()
    {
        $coreName = 'test_core';
        $documentId = '1';


        // Fake a successful HTTP response for deleting a document by ID
        Http::fake([
            "{$this->solrUrl}/{$coreName}/update?commit=true" => Http::response(null, 200)
        ]);

        $result = SolrModel::deleteDocumentById($coreName, $documentId);

        $this->assertTrue($result);
    }

    public function testAddChildToParentSuccess()
    {
        $coreName = 'A12';
        $parentId = 'parent2';
        $childData = [
            ['id' => 'child1', 'name' => 'Child Document 1'],
            ['id' => 'child2', 'name' => 'Child Document 2']
        ];


        // Fake a successful HTTP response for adding child documents to a parent
//        Http::fake([
//            "{$this->solrUrl}/{$coreName}/update?commit=true" => Http::response(null, 200)
//        ]);

        $result = SolrModel::addChildToParent($coreName, $parentId, $childData);

        $this->assertTrue($result);
    }

    public function testAddDocumentFailure()
    {
        $coreName = 'test_core';
        $data = [
            'id' => '1',
            'title' => 'Test Document'
        ];


        // Fake a failed HTTP response for adding a document
        Http::fake([
            "{$this->solrUrl}/{$coreName}/update?commit=true" => Http::response(null, 500)
        ]);

        $result = SolrModel::addDocument($coreName, $data);

        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

    }
}
