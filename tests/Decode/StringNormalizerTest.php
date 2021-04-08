<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Decode\StringNormalizer;
use Strata\Data\Exception\DecoderException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class Item
{
    public function __toString(): string
    {
        return 'test3';
    }
}

final class StringNormalizerTest extends TestCase
{

    public function testString()
    {
        $this->assertEquals('test1', StringNormalizer::getString('test1'));
    }

    public function testObject()
    {
        $responses = [
            new MockResponse('test2'),
        ];

        $client = new MockHttpClient($responses);
        $response = $client->request('GET', 'https://example.com/test');

        $this->assertEquals('test2', StringNormalizer::getString($response));
        $this->assertEquals('test3', StringNormalizer::getString(new Item()));
    }

    public function testInvalid()
    {
        $this->expectException(DecoderException::class);
        StringNormalizer::getString([1,2,3]);
    }
}
