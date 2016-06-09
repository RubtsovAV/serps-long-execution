<?php

namespace RubtsovAV\Serps\Test\Core\Query;

use RubtsovAV\Serps\Core\Query\Query;
use RubtsovAV\Serps\Core\Query\Condition\Condition;
use RubtsovAV\Serps\Core\Exception\InvalidArgumentException;

/**
 * @covers RubtsovAV\Serps\Core\Query\Query
 */
class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testSearchTerm()
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $this->assertEquals($searchTerm, $query->getSearchTerm());

        // change the search term
        $query->setSearchTerm('bar');
        $this->assertEquals('bar', $query->getSearchTerm());
    }

    /**
     * @dataProvider getWrongSearchTerms
     */
    public function testWrongSearchTerm($wrongSearchTerm)
    {
        $this->expectException(InvalidArgumentException::class);
        $query = new Query($wrongSearchTerm);
    }

    public function getWrongSearchTerms()
    {
        return [
            'empty string' => [''],
            'array' => [[]],
            'object' => [new \stdClass],
        ];
    }

    public function testSearchRegion()
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $searchRegion = [
            'foo' => 'bar',
            'baz' => [
                'param1' => 'value1',
                'param2' => 'value2',
            ]
        ];
        $query->setSearchRegion($searchRegion);
        $this->assertEquals($searchRegion, $query->getSearchRegion());
    }

    public function testPositionLimit()
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $positionLimit = 100;
        $query->setPositionLimit($positionLimit);
        $this->assertEquals($positionLimit, $query->getPositionLimit());

        // check type casting
        $positionLimit = '100';
        $query->setPositionLimit($positionLimit);
        $this->assertInternalType('int', $query->getPositionLimit());
    }

    /**
     * @dataProvider getWrongPositiveInteger
     */
    public function testWrongPositionLimit($wrongPositionLimit)
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $this->expectException(InvalidArgumentException::class);
        $query->setPositionLimit($wrongPositionLimit);
    }

    public function testMaxNumberItems()
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $maxNumberItems = 1;
        $query->setMaxNumberItems($maxNumberItems);
        $this->assertEquals($maxNumberItems, $query->getMaxNumberItems());

        // check type casting
        $maxNumberItems = '1';
        $query->setMaxNumberItems($maxNumberItems);
        $this->assertInternalType('int', $query->getMaxNumberItems());
    }

    /**
     * @dataProvider getWrongPositiveInteger
     */
    public function testWrongMaxNumberItems($wrongMaxNumberItems)
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $this->expectException(InvalidArgumentException::class);
        $query->setMaxNumberItems($wrongMaxNumberItems);
    }

    public function getWrongPositiveInteger()
    {
        return [
            'zero' => [0],
            'negative number' => [-1],
            'string' => ['test123'],
            'array' => [[]],
            'object' => [new \stdClass],
        ];
    }

    public function testConditionItems()
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $condition = $this->getMockBuilder(Condition::class)
            ->getMockForAbstractClass();
            
        $query->setConditionItems($condition);
        $this->assertEquals($condition, $query->getConditionItems());
    }
}
