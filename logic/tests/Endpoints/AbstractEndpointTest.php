<?php
use \TwinePM\Endpoints\AbstractEndpoint;
use PHPUnit\Framework\TestCase;

class AbstractEndpointTest extends TestCase {
    public function testGetClientErrorCode() {
        $stub = $this->getMockForAbstractClass(
            "\TwinePM\Endpoints\AbstractEndpoint");

        $this->assertEquals($stub->getClientErrorCode(null), "NoCodeProvided");
        $this->assertEquals($stub->getClientErrorCode("FooBarBaz"), "FooBarBaz");
    }

    public function testConvertServerErrorToClientError() {
        
    }
}