<?php


namespace HaiderJabbar\LaravelSolr\Tests\Unit\Services;

use HaiderJabbar\LaravelSolr\Services\SolrQueryBuilder;
use Illuminate\Support\Facades\Http;

// Import Http facade correctly

use ReflectionClass;

// Import ReflectionClass from global namespace
use Tests\TestCase;

// Ensure you import the correct TestCase
use Mockery;

class SolrQueryBuilderTest extends TestCase
{
    protected $builder;

    protected function setUp(): void
    {
        $this->builder = new SolrQueryBuilder('testCore');
    }

    public function testWhere()
    {
        $this->builder->where('field', '=', 'value');
        $this->assertEquals(['field:value'], $this->builder->filterQueries);
    }

    public function testOrWhere()
    {
        $this->builder->orWhere('field', '=', 'value');
        $this->assertEquals(['field:value'], $this->builder->filterQueries);
    }

    public function testSort()
    {
        $this->builder->sort('field asc');
        $this->assertEquals('field asc', $this->builder->sort);
    }

    public function testStart()
    {
        $this->builder->start(10);
        $this->assertEquals(10, $this->builder->start);
    }

    public function testRows()
    {
        $this->builder->rows(20);
        $this->assertEquals(20, $this->builder->rows);
    }

    public function testFl()
    {
        $this->builder->fl(['field1', 'field2']);
        $this->assertEquals('field1,field2', $this->builder->fields);
    }

    public function testFacet()
    {
        $this->builder->facet(true);
        $this->assertTrue($this->builder->facet);
    }

    public function testFacetFields()
    {
        $this->builder->facetFields(['field1', 'field2']);
        $this->assertEquals(['field1', 'field2'], $this->builder->facetFields);
    }

    public function testWhereParent()
    {
        $this->builder->whereParent('field', '=', 'value');
        $this->assertEquals(["{!parent which='parent:true'}field:value"], $this->builder->filterQueries);
    }

    public function testWhereChild()
    {
        $this->builder->whereChild('field', '=', 'value');
        $this->assertEquals(["{!child of='parent:true'}field:value"], $this->builder->filterQueries);
    }

    public function testWhereJoin()
    {
        $joinConfig = [
            'from' => 'from_field',
            'to' => 'to_field',
            'fromIndex' => 'other_core',
            'v' => 'field:value'
        ];
        $this->builder->whereJoin('testCore', 'id', 'id', "*:*");
        $expected = "{!join from=from_field to=to_field fromIndex=other_core}field:value";
//        $this->assertEquals([$expected], $this->builder->filterQueries);
    }

    public function testReturnOnlyParent()
    {
        $this->builder->returnOnlyParent();
        $this->assertEquals('parent', $this->builder->returnMode);
    }

    public function testReturnOnlyChild()
    {
        $this->builder->returnOnlyChild();
        $this->assertEquals('child', $this->builder->returnMode);
    }

    public function testReturnBothParentAndChild()
    {
        $this->builder->returnBothParentAndChild();
        $this->assertEquals('both', $this->builder->returnMode);
    }

    public function testGet()
    {
        // Mock the Http facade
        Http::shouldReceive('get')
            ->once()
            ->andReturn(['response' => ['docs' => []]]);

        $result = $this->builder->get();

        $this->assertIsArray($result);

    }

    public function testFormatCondition()
    {
        $reflectionClass = new ReflectionClass(SolrQueryBuilder::class);
        $method = $reflectionClass->getMethod('formatCondition');
        $method->setAccessible(true);

        $this->assertEquals('value', $method->invoke($this->builder, '=', 'value'));
        $this->assertEquals('[* TO *] -value', $method->invoke($this->builder, '!=', 'value'));
        $this->assertEquals('[* TO value]', $method->invoke($this->builder, '<', 'value'));
        $this->assertEquals('[value TO *]', $method->invoke($this->builder, '>', 'value'));
        $this->assertEquals('[* TO value]', $method->invoke($this->builder, '<=', 'value'));
        $this->assertEquals('[value TO *]', $method->invoke($this->builder, '>=', 'value'));
        $this->assertEquals('*value*', $method->invoke($this->builder, 'like', 'value'));
        $this->assertEquals('value', $method->invoke($this->builder, 'unknown', 'value'));
    }
}
