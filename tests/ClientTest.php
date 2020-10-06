<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use YaSD\AnkiConnect\Client;
use YaSD\AnkiConnect\Exception\ApiErrorException;
use YaSD\AnkiConnect\Exception\UnexpectedResultException;

class ClientTest extends TestCase
{
    public function testSetFactoryAndRequest()
    {
        $streamFactory = new Psr17Factory;
        $requestFactory = new Psr17Factory;

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getBody')->willReturn('{"result":6,"error":null}');
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn($response);

        $client = new Client();
        $client->setStreamFactory($streamFactory)->setRequestFactory($requestFactory)->setHttpClient($httpClient);

        $version = $client->api('version');
        $this->assertSame(6, $version);
    }

    public function testApiExceptionWhenApiError()
    {
        $client = $this->getMockedClient('{"result":"6","error":"something error"}');

        try {
            $client->api('whatever');
            $this->fail();
        } catch (ApiErrorException $e) {
            $this->assertIsArray($e->getRequest());
            $this->assertInstanceOf(\stdClass::class, $e->getResponse());
        }
    }

    public function testApiExceptionWhenValidatorFail()
    {
        $client = $this->getMockedClient('{"result":"6","error":null}');

        try {
            $client->api('whatever', null, 'is_int');
            $this->fail();
        } catch (UnexpectedResultException $e) {
            $this->assertIsArray($e->getRequest());
            $this->assertInstanceOf(\stdClass::class, $e->getResponse());
        }
    }
}
