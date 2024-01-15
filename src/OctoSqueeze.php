<?php

namespace OctoSqueeze\Client;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Filesystem\Filesystem;

final class OctoSqueeze
{
    /**
     * The API key for the requests.
     */
    private ?string $apiKey = null;

    /**
     * The endpoint URI for the requests.
     */
    private ?string $endpointUri = 'https://api.octosqueeze.com';

    /**
     * The HTTP client for the requests.
     */
    private ?Client $httpClient = null;

    /**
     * The HTTP client config.
     */
    private ?array $httpClientConfig = [];


    /**
     * Options for the image compression.
     */
    protected ?array $options = [
        'formats' => ['avif', 'jpeg'],
        'hash_check' => false,
        // --
        'image_id_check' => true,
        'quality' => 1, // 1 : smallest size, 2 : best quality
    ];

    public function __construct($apiKey)
    {
        $this->setApiKey($apiKey);

        $this->setHttpClientConfig([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getApiKey(),
                // 'http_errors' => true,
            ]
        ]);
    }

    public static function client($apiKey = null)
    {
        return new self($apiKey);
    }

    /**
     * Sets the API key for the requests.
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = trim($apiKey);

        return $this;
    }

    /**
     * Gets the API key.
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Sets the options.
     */
    public function setOptions(array $options, $rewrite = false): self
    {
        $this->options = $rewrite ? $options : array_merge($this->options, $options);

        return $this;
    }

    /**
     * Gets the options.
     */
    public function getOptions($key = null)
    {
        return $key ? (isset($this->options[$key]) ? $this->options[$key] : null) : $this->options;
    }

    /**
     * Sets the Endpoint URI.
     */
    public function setEndpointUri(string $uri): self
    {
        $this->endpointUri = trim($uri);

        $this->initializeHttpClient(true);

        return $this;
    }

    /**
     * Gets the Endpoint URI.
     */
    public function getEndpointUri(): string
    {
        return $this->endpointUri;
    }

    /**
     * Sets the Http Client Config.
     */
    public function setHttpClientConfig(array $config, $owerwrite = false): self
    {
        $this->httpClientConfig = $owerwrite ? $config : array_merge($this->getHttpClientConfig(), $config);

        $this->initializeHttpClient(true);

        return $this;
    }

    /**
     * Gets the Http Client Config.
     */
    public function getHttpClientConfig(): array
    {
        return $this->httpClientConfig;
    }


    /**
     * Initialize/Set the Http Client.
     */
    public function initializeHttpClient($reinitialize = false): self
    {
        if (!$this->httpClient || $reinitialize)
        {
            $this->httpClient = new Client($this->getHttpClientConfig());
        }

        return $this;
    }

    /**
     * Gets the Http Client.
     */
    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    public function squeezeUrl(string|array $urls)//: array
    {
        // $fs = new Filesystem;
        // $fs->appendToFile('test.txt', '1');

        $this->initializeHttpClient();

        $client = $this->getHttpClient();

        if (is_string($urls))
        {
            $urls = [$urls];
        }

        // prepare array format
        foreach ($urls as $key => $url)
        {
            if (is_string($url))
            {
                $urls[$key] = [
                    'url' => $url,
                ];
            }
        }

        try {
            $response = $client->request('POST', $this->endpoint('api/compressor/urls'), [
                'form_params' => [
                    'items' => $urls,
                    'options' => $this->getOptions(),
                ]
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        // } catch (\GuzzleHttp\Exception\ClientException $e) {
        // // } catch (\GuzzleHttp\Exception\ServerException $e) {
            $exception = json_decode($e->getResponse()->getBody()->getContents(), true);
        }

        if (isset($exception))
        {
            // dd($exception);
            return $exception;
            // return ['state' => false, 'error' => $exception['message']];
        }
        else
        {
            if (isset($response) && $response->getStatusCode() === 200)
            {
                $content = json_decode($response->getBody()->getContents(), true);

                return $content;
            }
            else
            {
                throw new Exception('Something went wrong, bad response');
            }
        }
    }

    public function squeezeFile(string $url): array
    {
        $this->initializeHttpClient();

        // ..
    }

    public function squeezeBuffer(string $url): array
    {
        $this->initializeHttpClient();

        // ..
    }

    public function squeezeFolder(string $path): array
    {
        $this->initializeHttpClient();

        // ..
    }

    public function take(string|array $bundle): Tentacles
    {
        $tentacles = new Tentacles($bundle, $this);

        return $tentacles;
    }

    public function endpoint(string $url): string
    {
        $base = $this->getEndpointUri();

        // remove slash at the end if presented
        $base = substr($base, -1) == '/' ? substr($base, 0, -1) : $base;
        // remove slash at the start if presented
        $url = substr($url, 1) == '/' ? substr($url, 1) : $url;

        $finalUrl = $base . '/' . $url;

        return $finalUrl;
    }
}
