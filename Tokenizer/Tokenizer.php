<?php
namespace TntSearch\Tokenizer;


use TeamTNT\TNTSearch\Support\AbstractTokenizer;
use TeamTNT\TNTSearch\Support\TokenizerInterface;

class Tokenizer extends AbstractTokenizer implements TokenizerInterface
{
    use SynonymResolverTrait;

    static protected $pattern = '/[^\p{L}\p{N}@]+/u';

    public function tokenize($text, $stopwords = []): array
    {
        $text = mb_strtolower(strip_tags($text));
        $text = str_replace(['_', '-', "'", "'"], ' ', $text);
        $splits = preg_split($this->getPattern(), $text, -1, PREG_SPLIT_NO_EMPTY);

        $splits = array_filter($splits, function ($token) {
            return mb_strlen($token) > 2;
        });

        $tokens = array_unique(array_map(fn (string $token) => $this->normalizeToken($token), $splits));

        return array_diff($tokens, $stopwords);
    }
}
