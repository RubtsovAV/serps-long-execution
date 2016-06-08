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
        $this->setExpectedException(InvalidArgumentException::class);
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

    public function testMaxPosition()
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $maxPosition = 100;
        $query->setMaxPosition($maxPosition);
        $this->assertEquals($maxPosition, $query->getMaxPosition());

        // check type casting
        $maxPosition = '100';
        $query->setMaxPosition($maxPosition);
        $this->assertInternalType('int', $query->getMaxPosition());
    }

    /**
     * @dataProvider getWrongMaxPositions
     */
    public function testWrongMaxPosition($wrongMaxPosition)
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $this->setExpectedException(InvalidArgumentException::class);
        $query->setMaxPosition($wrongMaxPosition);
    }

    public function getWrongMaxPositions()
    {
        return [
            'zero' => [0],
            'negative number' => [-1],
            'string' => ['test123'],
            'array' => [[]],
            'object' => [new \stdClass],
        ];
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
     * @dataProvider getWrongMaxPositions
     */
    public function testWrongMaxNumberItems($wrongMaxNumberItems)
    {
        $searchTerm = 'foo';
        $query = new Query($searchTerm);

        $this->setExpectedException(InvalidArgumentException::class);
        $query->setMaxNumberItems($wrongMaxNumberItems);
    }

    public function getWrongMaxNumberItems()
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
