<?php
namespace TntSearch\Tokenizer;

use TeamTNT\TNTSearch\Support\AbstractTokenizer;
use TeamTNT\TNTSearch\Support\TokenizerInterface;

class Tokenizer extends AbstractTokenizer implements TokenizerInterface
{
    static protected $pattern = '/[^\p{L}\p{N}@]+/u';

    public function tokenize($text, $stopwords = []): array
    {
        $text = mb_strtolower(strip_tags($text));
        $text = str_replace(['_', '-', "'", "'"], ' ', $text);
        $split = preg_split($this->getPattern(), $text, -1, PREG_SPLIT_NO_EMPTY);

        $split = array_filter($split, function ($token) {
            return mb_strlen($token) > 2;
        });

        return array_diff($split, $stopwords);
    }
}
