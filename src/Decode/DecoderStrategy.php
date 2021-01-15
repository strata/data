<?php
declare(strict_types=1);

namespace Strata\Data\Decode;


use Strata\Data\Data\Item;
use Strata\Data\Exception\DecoderException;

/**
 * Usage
 *
 * Set one decoder:
 * $decoder = new DecoderStrategy(new JsonDecoder());
 *
 * Set multiple decoders:
 * $decoder->add(new FrontMatter());
 * $decoder->add(new MarkdownDecoder());
 *
 * Decode an Item content:
 * $decoder->decode($item);
 *
 * Please note decoders run in order
 *
 * @package Strata\Data\Data
 */
class DecoderStrategy
{
    private array $decoders = [];

    public function __construct(?DecoderInterface $decoder)
    {
        if ($decoder !== null) {
            $this->add($decoder);
        }
    }

    public function add(DecoderInterface $decoder)
    {
        $this->decoders[] = $decoder;
    }

    public function getDecoders(): array
    {
        return $this->decoders;
    }

    public function decode(Item $item)
    {
        foreach ($this->getDecoders() as $decoder) {
            $content = $item->getContent();
            if (!is_string($content)) {
                throw new DecoderException(sprintf('Item content is not a string so cannot be decoded, %s detected', gettype($content)));
            }
            $item->setContent($decoder->decode($item->getContent()));
        }
    }
}