<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Decode\Rss;
use Strata\Data\Http\Response\MockResponseFromFile;
use Symfony\Component\HttpClient\MockHttpClient;

final class RssTest extends TestCase
{
    public function testRss()
    {
        $responses = [
            new MockResponseFromFile(__DIR__ . '/rss/example.rss'),
        ];
        $client = new MockHttpClient($responses);
        $response = $client->request('GET', 'http://example.com/');

        $decoder = new Rss();
        $feed = $decoder->decode($response);

        $this->assertInstanceOf('Laminas\Feed\Reader\Feed\FeedInterface', $feed);
        $this->assertEquals('News feed generator', $feed->getTitle());

        /** @var \Laminas\Feed\Reader\Entry\EntryInterface $item */
        $x = 0;
        foreach ($feed as $item) {
            $x++;
            switch ($x) {
                case 1:
                    $this->assertSame('Article 1', $item->getTitle());
                    $this->assertStringContainsString('Test description 1', $item->getDescription());
                    $this->assertSame('2021-03-29', $item->getDateCreated()->format('Y-m-d'));
                    break;
                case 2:
                    $this->assertSame('Article 2', $item->getTitle());
                    break;
                case 3:
                    $this->assertSame('Digital support', $item->getTitle());
                    break;
            }
        }
    }

}
