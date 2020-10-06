<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect\Tests;

use YaSD\AnkiConnect\Config;

class ConfigTest extends TestCase
{
    protected \stdClass $object;
    protected Config $config;

    protected function setUp(): void
    {
        $this->object = \json_decode('{"lapse":{"leechFails":8,"delays":[10],"minInt":1,"leechAction":0,"mult":0},"dyn":false,"autoplay":true,"mod":1502970872,"id":1,"maxTaken":60,"new":{"bury":true,"order":1,"initialFactor":2500,"perDay":20,"delays":[1,10],"separate":true,"ints":[1,4,7]},"name":"Default","rev":{"bury":true,"ivlFct":1,"ease4":1.3,"maxIvl":36500,"perDay":100,"minSpace":1,"fuzz":0.05},"timer":0,"replayq":true,"usn":-1}');
        $this->config = new Config($this->object);
    }

    public function testConstructExceptionWhenConfigNotValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Config(new \stdClass);
    }

    public function testGetConfig()
    {
        $this->assertEquals($this->object, $this->config->getConfig());
    }

    public function testGetId()
    {
        $this->assertSame(1, $this->config->getId());
    }
}
