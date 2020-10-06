<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use YaSD\AnkiConnect\Exception\ApiErrorException;
use YaSD\AnkiConnect\Exception\UnexpectedResultException;

class Client
{
    protected string $host;
    protected int $port;
    protected int $version;

    protected ?StreamFactoryInterface $streamFactory = null;
    protected ?RequestFactoryInterface $requestFactory = null;
    protected ?ClientInterface $httpClient = null;

    public function __construct(string $host = '127.0.0.1', int $port = 8765, int $version = 6)
    {
        $this->host = $host;
        $this->port = $port;
        $this->version = $version;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setStreamFactory(StreamFactoryInterface $streamFactory = null): self
    {
        $this->streamFactory = $streamFactory;
        return $this;
    }

    public function setRequestFactory(RequestFactoryInterface $requestFactory = null): self
    {
        $this->requestFactory = $requestFactory;
        return $this;
    }

    public function setHttpClient(ClientInterface $httpClient = null): self
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    protected function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory ?: Psr17FactoryDiscovery::findStreamFactory();
    }

    protected function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory ?: Psr17FactoryDiscovery::findRequestFactory();
    }

    protected function getHttpClient(): ClientInterface
    {
        return $this->httpClient ?: Psr18ClientDiscovery::find();
    }

    /**
     * AnkiConnect API
     *
     * @param string $action
     * @param array|null $params
     * @param callable|null $validator
     *
     * @return mixed AnkiConnect API result
     *
     * @throws ClientExceptionInterface HTTP Client Exception
     * @throws ApiErrorException AnkiConnect API Error
     * @throws UnexpectedResultException Unexpected AnkiConnect API result
     */
    public function api(string $action, array $params = null, callable $validator = null)
    {
        $reqArr = [
            'action' => $action,
            'version' => $this->version,
        ];
        if ($params) {
            $reqArr['params'] = $params;
        }

        $resJson = \json_decode($this->request(\json_encode($reqArr, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)));
        if ($resJson->error) {
            throw new ApiErrorException($reqArr, $resJson);
        }
        $result = $resJson->result;
        if ($validator and !$validator($result)) {
            throw new UnexpectedResultException($reqArr, $resJson);
        }
        return $result;
    }

    protected function request(string $reqJson): string
    {
        $reqBody = $this->getStreamFactory()->createStream($reqJson);
        $request = $this->getRequestFactory()->createRequest('POST', "http://{$this->host}:{$this->port}")->withBody($reqBody);
        $response = $this->getHttpClient()->sendRequest($request);
        return (string) $response->getBody();
    }
}
