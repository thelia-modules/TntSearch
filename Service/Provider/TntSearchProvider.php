<?php

namespace TntSearch\Service\Provider;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use TntSearch\Service\Stemmer;
use TntSearch\Service\StopWord;
use TntSearch\Service\Support\TntSearch;

class TntSearchProvider
{
    /** @var Stemmer */
    private $stemmer;

    /** @var StopWord */
    private $stopWord;

    /** @var string */
    const INDEXES_DIR = THELIA_LOCAL_DIR . "TNTIndexes";

    public function __construct(
        Stemmer $stemmer,
        StopWord $stopWord
    )
    {
        $this->stemmer = $stemmer;
        $this->stopWord = $stopWord;
    }

    /**
     * @param string|null $locale
     * @param string|null $tokenizer
     * @return TntSearch
     */
    public function getTntSearch(string $tokenizer = null , string $locale = null): TntSearch
    {
        return $this->buildTntSearch(
            $this->stemmer->getStemmer($locale),
            $locale ? $this->stopWord->getStopWords($locale) : [],
            $tokenizer
        );
    }

    /**
     * @param string $stemmer
     * @param array $stopWords
     * @param string|null $tokenizer
     * @return TntSearch
     */
    public function buildTntSearch(string $stemmer, array $stopWords = [], string $tokenizer = null): TntSearch
    {
        if (!is_dir(self::INDEXES_DIR)) {
            $fs = new Filesystem();
            $fs->mkdir(self::INDEXES_DIR);
        }

        // You need to empty the modes to set the sql_mode parameter to null
        $config = array_merge($this->getConnectionData(), [
            'storage' => self::INDEXES_DIR,
            'modes' => [''],
            'stemmer' => $stemmer,
            'tokenizer' => $tokenizer
        ]);

        $tnt = new TntSearch($config);

        if (!empty($stopWords)) {
            $tnt->setStopWords($stopWords);
        }

        return $tnt;
    }

    /**
     * @return array
     */
    protected function getConnectionData(): array
    {
        $configFile = THELIA_CONF_DIR . "database.yml";

        $propelParameters = Yaml::parse(file_get_contents($configFile))['database']['connection'];

        $driver = $propelParameters['driver'];
        $user = $propelParameters['user'];
        $password = $propelParameters['password'];

        $explodeDns = explode(';', $propelParameters['dsn']);
        $arrayDns = [];

        foreach ($explodeDns as $param) {
            $value = explode('=', $param);
            $arrayDns[$value[0]] = $value[1];
        }

        $host = $arrayDns['mysql:host'];
        $database = $arrayDns['dbname'];

        return [
            'driver' => $driver,
            'host' => $host,
            'charset' => 'utf8',
            'database' => $database,
            'username' => $user,
            'password' => $password,
        ];
    }
}