<?php

namespace RubtsovAV\Serps\Test\Core\Query;

use RubtsovAV\Serps\Core\Query\Query;
use RubtsovAV\Serps\Core\Query\Result;
use RubtsovAV\Serps\Core\ItemPosition;
use RubtsovAV\Serps\Core\Query\Condition\AnyConditions;
use RubtsovAV\Serps\Core\Query\Condition\DomainCondition;

/**
 * @covers RubtsovAV\Serps\Core\Query\Result
 */
class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testSetQuery()
    {
        $searchTerm = 'test';
        $query = new Query($searchTerm);

        $result = new Result();
        $result->setQuery($query);

        $this->assertEquals($query, $result->getQuery());
    }

    public function testGetItems()
    {
        $searchTerm = 'test';
        $query = new Query($searchTerm);

        $result = new Result();
        $result->setQuery($query);

        $items = $this->getItems();
        foreach ($items as $item) {
            $result->addItem($item);
        }

        $this->assertEquals($items, $result->getItems());
        $this->assertEquals(count($items), $result->countItems());
    }

    public function testMaxNumberItems()
    {
        $searchTerm = 'test';
        $maxNumberItems = 2;
        
        $query = new Query($searchTerm);
        $query->setMaxNumberItems($maxNumberItems);

        $result = new Result();
        $result->setQuery($query);

        $items = $this->getItems();
        foreach ($items as $item) {
            $result->addItem($item);
        }

        $this->assertEquals($maxNumberItems, $result->countItems());
    }

    public function testMaxPositionItems()
    {
        $searchTerm = 'test';
        $maxPosition = 2;

        $query = new Query($searchTerm);
        $query->setMaxPosition($maxPosition);

        $result = new Result();
        $result->setQuery($query);

        $expectedCount = 0;
        $items = $this->getItems();
        foreach ($items as $item) {
            $result->addItem($item);
            if ($item->position <= $maxPosition) {
                $expectedCount++;
            }
        }

        $this->assertEquals($expectedCount, $result->countItems());
    }

    /**
     * @covers RubtsovAV\Serps\Core\Query\Condition\DomainCondition
     */
    public function testConditions()
    {
        $searchTerm = 'test';
        $wantedDomain = 'domain';
        $condition = new DomainCondition($wantedDomain);        
        
        $query = new Query($searchTerm);
        $query->setConditionItems($condition);

        $result = new Result();
        $result->setQuery($query);

        $expectedCount = 0;
        $items = $this->getItems();
        foreach ($items as $item) {
            $result->addItem($item);

            $itemHost = parse_url($item->url, PHP_URL_HOST);
            if (strcasecmp($itemHost, $wantedDomain) === 0) {
                $expectedCount++;
            }
        }

        $this->assertEquals($expectedCount, $result->countItems());
    }

    /**
     * @covers RubtsovAV\Serps\Core\Query\Condition\AnyConditions
     */
    public function testAnyConditions()
    {
        $searchTerm = 'test';
        $wantedDomains = ['domain', 'sub.domain'];

        $condition = new AnyConditions([
            new DomainCondition($wantedDomains[0]),        
            new DomainCondition($wantedDomains[1]),  
        ]);      
        
        $query = new Query($searchTerm);
        $query->setConditionItems($condition);

        $result = new Result();
        $result->setQuery($query);

        $expectedCount = 0;
        $items = $this->getItems();
        foreach ($items as $item) {
            $result->addItem($item);

            $itemHost = parse_url($item->url, PHP_URL_HOST);
            foreach ($wantedDomains as $wantedDomain) {
                if (strcasecmp($itemHost, $wantedDomain) === 0) {
                    $expectedCount++;
                    continue 2;
                }
            }
        }

        $this->assertEquals($expectedCount, $result->countItems());
    }

    private function getItems()
    {
        return [
            new ItemPosition([
                'url' => 'http://domain/path?query=value#fragment',
                'title' => 'Title',
                'position' => 1,
            ]),
            new ItemPosition([
                'url' => 'http://sub.domain/path?query=value#fragment',
                'title' => 'Title 2',
                'position' => 2,
            ]),
            new ItemPosition([
                'url' => 'https://domain/path?query=value#fragment',
                'title' => 'Title 3',
                'position' => 3,
            ]),
            new ItemPosition([
                'url' => 'https://domain2/path?query=value#fragment',
                'title' => 'Title 4',
                'position' => 4,
            ])
        ];
    }
}
