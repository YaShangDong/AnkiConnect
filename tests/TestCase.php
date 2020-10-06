<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect\Tests;

use PHPUnit\Framework\TestCase as PHPUnit_Framework_TestCase;
use YaSD\AnkiConnect\Anki;
use YaSD\AnkiConnect\Client;

class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getMockedClient(string $responseJson, string $requestJsonToTest = null): Client
    {
        $client = $this->getMockBuilder(Client::class)
            ->onlyMethods(['request'])
            ->getMock();

        if ($requestJsonToTest) {
            $client->expects($this->once())
                ->method('request')
                ->with($requestJsonToTest)->willReturn($responseJson);
        } else {
            $client->expects($this->once())
                ->method('request')
                ->willReturn($responseJson);
        }

        /** @var Client $client */
        return $client;
    }

    public function getMockedAnki(Client $client): Anki
    {
        $anki = $this->getMockBuilder(Anki::class)
            ->onlyMethods(['checkApiVersion'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Anki $anki */
        $anki->__construct($client);
        return $anki;
    }
}
