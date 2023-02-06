<?php

namespace TntSearch;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Thelia\Module\BaseModule;
use TntSearch\Service\TheliaTntSearch;

class TntSearch extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'tntsearch';
    /** @var string */
    const INDEXES_DIR = THELIA_LOCAL_DIR . "TNTIndexes";
    /** @var string */
    const ON_THE_FLY_UPDATE = 'tntsearch.on_the_fly_update';

    //WIP
    /** @var array[] */
    public const THELIA_INDEXES = [
        'customer' => [
            'is_translatable' => false,
            'tokenizer' => \TntSearch\Tokenizer\CustomerTokenizer::class
        ],
        'order' => [
            'is_translatable' => false,
            'tokenizer' => \TntSearch\Tokenizer\CustomerTokenizer::class
        ],
        'brand' => [
            'is_translatable' => true
        ],
        'content' => [
            'is_translatable' => true
        ],
        'category' => [
            'is_translatable' => true
        ],
        'folder' => [
            'is_translatable' => true
        ],
        'product' => [
            'is_translatable' => true
        ]
    ];

    public function postActivation(ConnectionInterface $con = null)
    {
        self::setConfigValue(self::ON_THE_FLY_UPDATE, false);
    }

    public function update($currentVersion, $newVersion, ConnectionInterface $con = null)
    {
        if (version_compare($currentVersion, '0.7.0') === -1) {
            self::setConfigValue(self::ON_THE_FLY_UPDATE, true);
        }
    }

    /**
     * @param string $locale
     * @return array|mixed
     */
    public static function getStopWords(string $locale = null)
    {
        if ($locale == null) {
            return [];
        }

        if (is_file($file = __DIR__ . '/StopWords/' . $locale . '.json')) {
            return json_decode(file_get_contents($file));
        }

        return [];
    }

    /**
     * @param $locale
     * @return \TntSearch\Service\TheliaTntSearch
     */
    public static function getTntSearch($locale = null, $tokenizer = null)
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

        if (!is_dir(self::INDEXES_DIR)) {
            $fs = new Filesystem();
            $fs->mkdir(self::INDEXES_DIR);
        }

        switch ($locale) {
            case 'fr_FR':
                $stemmer = \TntSearch\Stemmer\FrenchStemmer::class;
                break;
            case 'it_IT':
                $stemmer = \TeamTNT\TNTSearch\Stemmer\ItalianStemmer::class;
                break;
            case 'de_DE':
                $stemmer = \TeamTNT\TNTSearch\Stemmer\GermanStemmer::class;
                break;
            case 'pt_PT':
                $stemmer = \TeamTNT\TNTSearch\Stemmer\PortugueseStemmer::class;
                break;

            default:
                $stemmer = \TeamTNT\TNTSearch\Stemmer\PorterStemmer::class;
        }

        $config = [
            'driver' => $driver,
            'host' => $host,
            'charset' => 'utf8',
            'database' => $database,
            'username' => $user,
            'password' => $password,
            'storage' => self::INDEXES_DIR,
            'stemmer' => $stemmer,
            'modes' => ['']
        ];

        if ($tokenizer) {
            $config['tokenizer'] = $tokenizer;
        }

        $myTntSearch = new TheliaTntSearch($config);
        $myTntSearch->setStopWords(self::getStopWords($locale));

        return $myTntSearch;
    }
}
