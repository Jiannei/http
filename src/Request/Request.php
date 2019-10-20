<?php


namespace Jiannei\Http\Request;


class Request
{
    private $request;

    public function __construct(\GuzzleHttp\Psr7\Request $request)
    {
        $this->request = $request;
    }

    public function url()
    {
        return (string) $this->request->getUri();
    }

    public function method()
    {
        return $this->request->getMethod();
    }

    public function body()
    {
        return (string) $this->request->getBody();
    }

    public function headers()
    {
        return collect($this->request->getHeaders())->mapWithKeys(function ($values, $header) {
            return [$header => $values[0]];
        })->all();
    }

    public function __call($method, $args)
    {
        return $this->request->{$method}(...$args);
    }
}
