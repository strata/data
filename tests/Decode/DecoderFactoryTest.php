<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Decode\DecoderFactory;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class DecoderFactoryTest extends TestCase
{

    public function testFilenameFactory()
    {
        $this->assertInstanceOf('Strata\Data\Decode\Json', DecoderFactory::fromFilename('example.json'));
        $this->assertInstanceOf('Strata\Data\Decode\Markdown', DecoderFactory::fromFilename('example.md'));
        $this->assertInstanceOf('Strata\Data\Decode\Markdown', DecoderFactory::fromFilename('example.mkd'));
        $this->assertInstanceOf('Strata\Data\Decode\Markdown', DecoderFactory::fromFilename('example.markdown'));
        $this->assertInstanceOf('Strata\Data\Decode\Rss', DecoderFactory::fromFilename('example.rss'));
        $this->assertInstanceOf('Strata\Data\Decode\Rss', DecoderFactory::fromFilename('example.atom'));
        $this->assertNull(DecoderFactory::fromFilename('example.html'));

        $this->assertInstanceOf('Strata\Data\Decode\Rss', DecoderFactory::fromFilename('https://example.com/feed.rss'));
        $this->assertNull(DecoderFactory::fromFilename('https://example.com/feed'));
    }

    /**
     * @dataProvider responseDataProvider
     */
    public function testResponseFactory($contentType, $expectedClass)
    {
        $mockResponse = new MockResponse('', ['response_headers' => ['Content-type' => $contentType]]);
        $client = new MockHttpClient([$mockResponse]);
        $response = $client->request('GET', 'http://example.com/');
        $this->assertInstanceOf($expectedClass, DecoderFactory::fromResponse($response));
    }

    public function responseDataProvider()
    {
        return [
            'Json' => [
                'application/json',
                'Strata\Data\Decode\Json'
            ],
            'Markdown1' => [
               'text/markdown',
                'Strata\Data\Decode\Markdown'
            ],
            'Markdown2' => [
                'text/x-markdown',
                'Strata\Data\Decode\Markdown'
            ],
            'Rss1' => [
                'application/rss+xml',
                'Strata\Data\Decode\Rss'
            ],
            'Rss2' => [
                'text/rss',
                'Strata\Data\Decode\Rss'
            ],
            'Atom' => [
                'application/atom+xml',
                'Strata\Data\Decode\Rss'
            ],
        ];
    }

}
