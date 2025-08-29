<?php

namespace Zedstar16\OnlineTime\database\thread\message;

readonly class ThreadResponse
{

    public function __construct(
        private int             $requestID,
        private array|bool|null $response
    ){
    }

    public function getRequestID(): int{
        return $this->requestID;
    }

    public function getResponse(): array|bool|null{
        return $this->response;
    }

}