<?php

namespace OctoSqueeze\Client;

use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;

class Tentacles
{
    private ?OctoSqueeze $octo = null;

    protected ?array $source;

    protected ?array $bundle;

    public function __construct($source, $octo)
    {
        $this->source = $source;
        $this->octo = $octo;

        $this->fetchCompressions();
    }

    public function toFile($dir): null|array
    {
        $bundle = $this->getBundle();

        if (count($bundle))
        {
            $fs = new Filesystem();

            // TODO .env
            // if (env('OCTOSQUEEZE_DEV')) {
            // ! only for dev TLS verification
            $contextOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ];
            // }

            $completed = [];

            foreach ($bundle as $file)
            {
                foreach ($file['compressions'] as $compression)
                {
                    $image = file_get_contents($compression['link'], false, stream_context_create($contextOptions));
                    $filename = $file['name'] . '.' . $compression['format'];

                    if ($image)
                    {
                        $path = $dir .'/'. $filename;
                        $fs->dumpFile($path, $image);

                        $completed[] = $path;
                    }
                }
            }

            return $completed;
        }
    }

    public function toBuffer(): array
    {
        return [];
    }

    /**
     * Sets the bundle.
     */
    public function setBundle(array $bundle): self
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Gets the bundle.
     */
    public function getBundle(): array
    {
        return $this->bundle;
    }
    protected function fetchCompressions()
    {
        $this->octo->initializeHttpClient();

        $client = $this->octo->getHttpClient();

        try {
            $response = $client->request('POST', $this->octo->endpoint('api/compressor/take'), [
                'form_params' => [
                    'items' => $this->source,
                ]
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        // } catch (\GuzzleHttp\Exception\ClientException $e) {
        // // } catch (\GuzzleHttp\Exception\ServerException $e) {
            $exception = json_decode($e->getResponse()->getBody()->getContents(), true);
        }

        if (isset($exception))
        {
            // throw new Exception('Something went wrong, bad response');
            dd($exception);
        }
        else
        {
            if ($response->getStatusCode() === 200)
            {
                $result = json_decode($response->getBody()->getContents(), true);

                $this->setBundle($result['images']);

                return $this;
            }
            else
            {
                throw new Exception('Something went wrong, bad response');
            }
        }
    }
}
