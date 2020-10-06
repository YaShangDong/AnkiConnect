<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect\Exception;

use YaSD\AnkiConnect\Exception;

class ApiErrorException extends \RuntimeException implements Exception
{
    protected array $request;
    protected \stdClass $response;

    public function __construct(array $request, \stdClass $response, ?\Throwable $previous = null)
    {
        parent::__construct("API_Error: {$request['action']}: {$response->error}", 0, $previous);
        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    public function getResponse(): \stdClass
    {
        return $this->response;
    }
}
