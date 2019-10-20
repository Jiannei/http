<?php

namespace Jiannei\Http\TransferStats;

class TransferStats
{
    private $transferStats;

    public function __construct(\GuzzleHttp\TransferStats $transferStats)
    {
        $this->transferStats = $transferStats;
    }

    public function __call($method, $args)
    {
        return $this->transferStats->{$method}(...$args);
    }
}