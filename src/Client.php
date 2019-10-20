<?php


namespace Jiannei\Http;

use Jiannei\Http\Exceptions\ConnectionException;
use Jiannei\Http\Request\Request;
use Jiannei\Http\Response\Response;
use Jiannei\Http\TransferStats\TransferStats;

class Client
{
    private $configs;
    private $beforeSendingCallbacks;
    private $bodyFormat;
    private $options;
    private $cookies;

    public static $request;
    public static $transferStats;
    public static $response;

    private function __construct(array $configs)
    {
        $this->configs = $configs;
        $this->beforeSendingCallbacks = collect(function ($request, $options) {
            $this->cookies = $options['cookies'];
        });
        $this->bodyFormat = 'json';
        $this->options = [
            'http_errors' => false,
        ];
    }

    public static function create()
    {
        return new static(...func_get_args());
    }

    public function withConfig(array $configs)
    {
        return tap($this, function ($request) use ($configs) {
            return $this->configs = array_merge_recursive($this->configs, $configs);
        });
    }

    public function withOptions($options)
    {
        return tap($this, function ($request) use ($options) {
            return $this->options = array_merge_recursive($this->options, $options);
        });
    }

    public function withoutRedirecting()
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'allow_redirects' => false,
            ]);
        });
    }

    public function withoutVerifying()
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'verify' => false,
            ]);
        });
    }

    public function asJson()
    {
        return $this->bodyFormat('json')->contentType('application/json');
    }

    public function asFormParams()
    {
        return $this->bodyFormat('form_params')->contentType('application/x-www-form-urlencoded');
    }

    public function asMultipart()
    {
        return $this->bodyFormat('multipart');
    }

    public function bodyFormat($format)
    {
        return tap($this, function ($request) use ($format) {
            $this->bodyFormat = $format;
        });
    }

    public function contentType($contentType)
    {
        return $this->withHeaders(['Content-Type' => $contentType]);
    }

    public function accept($header)
    {
        return $this->withHeaders(['Accept' => $header]);
    }

    public function withHeaders($headers)
    {
        return tap($this, function ($request) use ($headers) {
            return $this->options = array_merge_recursive($this->options, [
                'headers' => $headers,
            ]);
        });
    }

    public function withBasicAuth($username, $password)
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password],
            ]);
        });
    }

    public function withDigestAuth($username, $password)
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password, 'digest'],
            ]);
        });
    }

    public function withCookies($cookies)
    {
        return tap($this, function ($request) use ($cookies) {
            return $this->options = array_merge_recursive($this->options, [
                'cookies' => $cookies,
            ]);
        });
    }

    public function timeout($seconds)
    {
        return tap($this, function () use ($seconds) {
            $this->options['timeout'] = $seconds;
        });
    }

    public function beforeSending($callback)
    {
        return tap($this, function () use ($callback) {
            $this->beforeSendingCallbacks[] = $callback;
        });
    }

    public function get($url, $queryParams = [])
    {
        return $this->send('GET', $url, [
            'query' => $queryParams,
        ]);
    }

    public function post($url, $params = [])
    {
        return $this->send('POST', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    public function patch($url, $params = [])
    {
        return $this->send('PATCH', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    public function put($url, $params = [])
    {
        return $this->send('PUT', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    public function delete($url, $params = [])
    {
        return $this->send('DELETE', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    public function send($method, $url, $options)
    {
        try {
            $originalOptions = [
                'query'    => $this->parseQueryParams($url),
                'on_stats' => function (\GuzzleHttp\TransferStats $transferStats) {
                    $this->transferStats(new TransferStats($transferStats));
                },
            ];

            $response = $this->buildClient()->request($method, $url, $this->mergeOptions($originalOptions, $options));
            return $this->response(new Response($response));
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }

    private function buildClient()
    {
        return new \GuzzleHttp\Client(array_merge_recursive([
            'handler' => $this->buildHandlerStack(),
            'cookies' => true,
        ], $this->configs));
    }

    private function buildHandlerStack()
    {
        return tap(\GuzzleHttp\HandlerStack::create(), function ($stack) {
            $stack->push($this->buildBeforeSendingHandler());
        });
    }

    private function buildBeforeSendingHandler()
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                return $handler($this->runBeforeSendingCallbacks($request, $options), $options);
            };
        };
    }

    private function runBeforeSendingCallbacks($request, $options)
    {
        return tap($request, function ($request) use ($options) {
            $this->beforeSendingCallbacks->each->__invoke($this->request(new Request($request)), $options);
        });
    }

    public static function request(Request $request)
    {
        return self::$request = $request;
    }

    public static function transferStats(TransferStats $transferStats)
    {
        return self::$transferStats = $transferStats;
    }

    public static function response(Response $response)
    {
        return self::$response = $response;
    }

    private function mergeOptions(...$options)
    {
        return array_merge_recursive($this->options, ...$options);
    }

    private function parseQueryParams($url)
    {
        return tap([], function (&$query) use ($url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
        });
    }
}


