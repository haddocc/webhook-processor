<?php
declare(strict_types=1);

namespace Tests;

use App\DeployHQWebhookProcessor;
use App\Exceptions\InvalidSourceException;
use App\Path;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class DeployHQWebhookProcessorTest extends TestCase
{
    private string $_testReceiveInputStreamPath = 'tests/app/DeployHQWebhookProcessorTestReceiveInputStream';

    public function setUp(): void
    {
        if (!defined('APPROOT')) {
            define('APPROOT', dirname(__DIR__, 2));
        }
    }

    /**
     * @throws ReflectionException
     */
    public function testReceive(): void
    {
        $stub = $this->getMockBuilder(DeployHQWebhookProcessor::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['getInputStream'])
                     ->getMock();
        $path = new Path($this->_testReceiveInputStreamPath);
        $stub->method('getInputStream')
             ->willReturnCallback(fn() => file_get_contents($path->__toString()));

        $populatedStub = $stub->receive();

        $this->assertInstanceOf(get_class($stub), $populatedStub);

        $reflector    = new ReflectionClass($populatedStub);
        $property     = $reflector->getProperty('_requestData');
        $_requestData = $property->getValue($populatedStub);
        $this->assertArrayHasKey('payload', $_requestData);
        $this->assertArrayHasKey('signature', $_requestData);
    }

    /**
     * @throws InvalidSourceException
     */
    public function testRespond(): void
    {
        $stub = $this->getMockBuilder(DeployHQWebhookProcessor::class)
                     ->setConstructorArgs(['https://remote.webhook.url'])
                     ->onlyMethods(['getInputStream'])
                     ->getMock();
        $path = new Path($this->_testReceiveInputStreamPath);
        $stub->method('getInputStream')
             ->willReturnCallback(fn() => file_get_contents($path->__toString()));
        $result = $stub->receive()->respond();
        $this->assertTrue(is_string($result) || is_bool($result));
    }
}
