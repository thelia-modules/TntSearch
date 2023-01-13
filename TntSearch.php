<?php

namespace TntSearch;

use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Thelia\Install\Database;
use Thelia\Module\BaseModule;
use TntSearch\Event\IndexesEvent;
use TntSearch\Model\TntSearchIndexQuery;
use TntSearch\Service\TheliaTntSearch;

class TntSearch extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'tntsearch';

    const INDEXES_DIR = THELIA_LOCAL_DIR . "TNTIndexes";

    const ON_THE_FLY_UPDATE = 'tntsearch.on_the_fly_update';

    public function postActivation(ConnectionInterface $con = null)
    {
        try {
            TntSearchIndexQuery::create()->findOne();
        } catch (PropelException $ex) {
            $database = new Database($con->getWrappedConnection());
            $database->insertSql(null, array(__DIR__ . "/Config/thelia.sql"));
            $database->insertSql(null, array(__DIR__ . "/Config/data.sql"));
        }
        self::setConfigValue(self::ON_THE_FLY_UPDATE, true);
        /*
        if (!is_dir($this::INDEXES_DIR)) {
            $this->getDispatcher()->dispatch(
                IndexesEvent::GENERATE_INDEXES,
                new IndexesEvent()
            );
        }*/
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

        if(is_file($file = __DIR__ . '/StopWords/'.$locale.'.json')){
            return json_decode(file_get_contents($file));
        }

        return [];
    }

    /**
     * @param $locale
     * @return \TntSearch\Service\TheliaTntSearch
     */
    public static function getTntSearch($locale = null)
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
            'modes' => ''
        ];

        $myTntSearch = new TheliaTntSearch($config);
        $myTntSearch->setStopWords(self::getStopWords($locale));

        return $myTntSearch;
    }
}
