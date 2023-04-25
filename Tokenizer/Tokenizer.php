<?php
namespace TntSearch\Tokenizer;

use TeamTNT\TNTSearch\Support\AbstractTokenizer;
use TeamTNT\TNTSearch\Support\TokenizerInterface;

class Tokenizer extends AbstractTokenizer implements TokenizerInterface
{
    private ?string $key = null;

    static protected $pattern = [
        'email' => '/[^\p{L}\p{N}\p{Pc}\p{Pd}@\.]+/u',
        'default'=>'/[^\p{L}\p{N}\p{Pc}\p{Pd}\.]+/u',
        'search'=> '/[^\p{L}\p{N}\p{Pc}\p{Pd}@\.]+/u'
    ];

    public function tokenize($text ,$stopwords = [],$columunName = null)
    {

        $text  = mb_strtolower($text);
        $this->key=$columunName;
        $split = preg_split($this->getPattern(), $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_diff($split, $stopwords);
    }


    public function getPattern()
    {
        if (empty(static::$pattern)) {
            throw new \LogicException("Tokenizer must define split \$pattern value");
        } else {
            return $this->key && key_exists($this->key,static::$pattern) ? static::$pattern[$this->key] : static::$pattern['default'];
        }
    }
}