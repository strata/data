<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Data\Wordpress;

final class WordpressTest extends TestCase
{

    public function testGetOne()
    {
        $api = new Wordpress("https://simonrjones.net/wp-json/wp/v2/");
        $response = $api->getOne(1);
        $data = $response->toArray();

        $this->assertTrue($api->isSuccess($response));
        $this->assertEquals('hello-world', $data['slug']);

    }

}
