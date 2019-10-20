<?php

/*
 * This file is part of the jiannei/http.
 *
 * (c) jiannei<longjian.huang@aliyun.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
