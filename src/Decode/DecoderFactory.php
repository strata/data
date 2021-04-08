<?php

declare(strict_types=1);

namespace Strata\Data\Decode;

use Symfony\Contracts\HttpClient\ResponseInterface;

class DecoderFactory
{
    /**
     * Array of file extensions to decoders
     * @var array|string[]
     */
    private static array $extensionLookup = [
        'json' => 'Json',
        'md' => 'Markdown',
        'mkd' => 'Markdown',
        'markdown' => 'Markdown',
        'rss' => 'Rss',
        'atom' => 'Rss',
    ];

    /**
     * Array of mime-types to decoders
     * @var array|string[]
     */
    private static array $mimetypeLookup = [
        'application/json' => 'Json',
        'text/markdown' => 'Markdown',
        'text/x-markdown' => 'Markdown',
        'application/rss+xml' => 'Rss',
        'text/rss' => 'Rss',
        'application/atom+xml' => 'Rss',
    ];

    /**
     * Create an appropriate decoder based on filename
     *
     * @param string $filename
     * @return DecoderInterface|null
     */
    public static function fromFilename(string $filename): ?DecoderInterface
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $extension = strtolower($extension);
        if (array_key_exists($extension, self::$extensionLookup)) {
            $class = __NAMESPACE__ . '\\' . self::$extensionLookup[$extension];
            return new $class();
        }
        return null;
    }

    /**
     * Create an appropriate decoder based on response
     *
     * @param ResponseInterface $response
     * @return DecoderInterface|null
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public static function fromResponse(ResponseInterface $response): ?DecoderInterface
    {
        $headers = $response->getHeaders();
        if (!isset($headers['content-type'])) {
            return null;
        }
        $contentType = $headers['content-type'][0];
        $contentType = strtolower($contentType);
        if (array_key_exists($contentType, self::$mimetypeLookup)) {
            $class = __NAMESPACE__ . '\\' . self::$mimetypeLookup[$contentType];
            return new $class();
        }
        return null;
    }

}