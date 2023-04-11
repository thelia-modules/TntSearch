<?php
namespace TntSearch\Tokenizer;

use TeamTNT\TNTSearch\Support\AbstractTokenizer;
use TeamTNT\TNTSearch\Support\TokenizerInterface;

/**
 * @method getPattern()
 */
class Tokenizer extends AbstractTokenizer implements TokenizerInterface
{
    static protected $pattern = '/[^\p{L}\p{N}\p{Pc}\p{Pd}@]+/u';

    public function tokenize($text, $stopwords = []): array
    {
        $text  = mb_strtolower(strip_tags($text));
        $split = preg_split($this->getPattern(), $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_diff($split, $stopwords);
    }
}