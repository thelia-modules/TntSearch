<?php

namespace TntSearch\Tokenizer;

use TeamTNT\TNTSearch\Support\AbstractTokenizer;
use TeamTNT\TNTSearch\Support\TokenizerInterface;

/**
 * @method getPattern()
 */
class CustomerTokenizer extends AbstractTokenizer implements TokenizerInterface
{
    static protected $pattern = '/[^\p{L}\p{N}\p{Pc}\p{Pd}@\.]+/u';

    public function tokenize($text, $stopwords = []): array
    {
        $text  = mb_strtolower($text);
        $split = preg_split($this->getPattern(), $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_diff($split, $stopwords);
    }
}