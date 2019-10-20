<?php

/*
 * This file is part of the jiannei/http.
 *
 * (c) jiannei<longjian.huang@aliyun.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jiannei\Http\Response;

class Response
{
    private $response;

    public function __construct(\GuzzleHttp\Psr7\Response $response)
    {
        $this->response = $response;
    }

    public function body()
    {
        return (string) $this->response->getBody();
    }

    public function json()
    {
        return json_decode($this->response->getBody(), true);
    }

    public function header($header)
    {
        return $this->response->getHeaderLine($header);
    }

    public function headers()
    {
        return $this->response->getHeaders();
    }

    public function status()
    {
        return $this->response->getStatusCode();
    }

    public function isSuccess()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function isRedirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    public function isClientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    public function isServerError()
    {
        return $this->status() >= 500;
    }

    public function __toString()
    {
        return $this->body();
    }

    public function __call($method, $args)
    {
        return $this->response->{$method}(...$args);
    }
}
