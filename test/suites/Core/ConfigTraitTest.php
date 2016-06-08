<?php

namespace RubtsovAV\Serps\Test\Core;

use RubtsovAV\Serps\Core\ConfigTrait;
use RubtsovAV\Serps\Core\Logger;
use RubtsovAV\Serps\Core\Exception\InvalidArgumentException;

/**
 * @covers RubtsovAV\Serps\Core\ConfigTrait
 */
class ConfigTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $config = [
            'param1' => 'value1',
            'param2' => [
                'subparam1' => 'subvalue1',
                'subparam2' => 'subvalue2',
            ],
        ];

        $configMock = $this->getMockBuilder(ConfigTrait::class)
            ->setMethods(null)
            ->setConstructorArgs([$config])
            ->getMockForTrait();

        $this->assertEquals($config, $configMock->getConfig());
    }

    public function testLogger()
    {
        $logger = Logger::getInstance();

        $config = [
            'logger' => $logger,
        ];

        $configMock = $this->getMockBuilder(ConfigTrait::class)
            ->setMethods(null)
            ->setConstructorArgs([$config])
            ->getMockForTrait();

        $this->assertEquals($logger, $configMock->getLogger());
    }

    public function testInit()
    {
        $config = [
            'param1' => 'value1',
            'param2' => [
                'subparam1' => 'subvalue1',
                'subparam2' => 'subvalue2',
            ],
        ];

        $configMock = $this->getMockBuilder(ConfigTrait::class)
            ->setMethods(['init'])
            ->getMockForTrait();

        $configMock->expects($this->once())->method('init');

        $configMock->initConfig($config);
    }
}
