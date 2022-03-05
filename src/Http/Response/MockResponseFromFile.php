<?php

declare(strict_types=1);

namespace Strata\Data\Http\Response;

use Symfony\Component\HttpClient\Response\MockResponse;

class MockResponseFromFile extends MockResponse
{
    /**
     * Create mock response from a file
     *
     * Body file is loaded from {$filename}
     *
     * The optional info file is loaded from {$filename}.info.php and must contain the $info variable (array). By
     * default mock responses return a 200 status code, which you can change by setting the $info array.
     *
     * @see ResponseInterface::getInfo() for possible info, e.g. http_code, response_headers
     *
     * Example:
     *
     * $reponse = new MockResponseFromFile('test1.json');
     *
     * test1.json
     * {
     *     "message": "OK"
     * }
     *
     * test1.json.info.php
     * <?php
     * $info = [
     *     'http_code' => 200
     * ];
     *
     * @param string $filename Local file to load mock response from, automatically adds .response.json for body file
     * @throws \Exception
     */
    public function __construct(string $filename)
    {
        $bodyFile = realpath($filename);
        $infoFile = realpath($filename . '.info.php');
        $info = [];

        if (empty($bodyFile)) {
            throw new \Exception(sprintf('Mock HTTP response file does not exist at %s', $bodyFile));
        }
        $body = file_get_contents($bodyFile);
        if (!empty($infoFile)) {
            include $infoFile;
        }

        parent::__construct($body, $info);
    }
}
