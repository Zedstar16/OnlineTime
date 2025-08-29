<?php

namespace Zedstar16\OnlineTime\database\thread\message;

class ThreadMessage
{

    public const TYPE_GC_COLLECT = -1;

    public const TYPE_QUERY_SINGLE = 0;
    public const TYPE_QUERY_ALL = 1;
    public const TYPE_EXEC = 2;

    private int $requestID;

    public function __construct(
        private readonly string $query,
        private readonly int    $queryType
    ){
        $this->requestID = mt_rand(0, mt_getrandmax());
    }

    public function getQuery(): string{
        return $this->query;
    }

    public function getQueryType(): int{
        return $this->queryType;
    }

    public function getRequestID(): int{
        return $this->requestID;
    }


}