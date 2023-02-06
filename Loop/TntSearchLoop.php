<?php

namespace TntSearch\Loop;

use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Type\EnumListType;
use Thelia\Type\TypeCollection;
use TntSearch\Service\TheliaTntSearch;
use TntSearch\TntSearch;

/**
 * @method string getSearch()
 * @method array getSearchFor()
 * @method string getLocale()
 * @method string getBackendContext()
 */
class TntSearchLoop extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            new Argument(
                'search_for',
                new TypeCollection(
                    new EnumListType(
                        array(
                            'product', 'category', 'brand',
                            'folder', 'content', 'order', 'customer', '*'
                        )
                    )
                )
            ),
            Argument::createAlphaNumStringTypeArgument('locale'),
            Argument::createBooleanTypeArgument('backend_context'),
            Argument::createAnyTypeArgument('search'),
            Argument::createIntTypeArgument('limit', 100)
        );
    }

    public function buildModelCriteria()
    {
        return null;
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function parseResults(LoopResult $loopResult)
    {
        $request = $this->getCurrentRequest();
        $session = $request->getSession();

        if (!$locale = $this->getLocale()) {
            $locale = $session->getLang()->getLocale();
            if ($this->getBackendContext()) {
                $locale = $session->getAdminEditionLang()->getLocale();
            }
        }

        $searchFors = $this->getSearchFor();

        $offset = (int)$this->getOffset();
        $limit = (int)$this->getLimit();

        if (in_array("*", $searchFors, true)) {
            $searchFors = ['customer', 'order', 'product', 'category', 'folder', 'content', 'brand'];
        }

        $loopResultRow = new LoopResultRow();
        $results = [];

        foreach ($searchFors as $searchType) {
            try {
                $idexes = TntSearch::THELIA_INDEXES;
                $currentIndexMetaData = $idexes[$searchType] ?? null;

                $tnt = TntSearch::getTntSearch($locale, $currentIndexMetaData);

                $currentLocale = null;
                if (!in_array($searchType, ["customer", "order"])) {
                    $currentLocale = $locale;
                }

                $results[$searchType] = $this->handleSearch($tnt, $this->getSearch(), $searchType, $offset, $limit, $currentLocale);

            } catch (\Exception $exception) {
                $error = $exception->getMessage();
            }
        }

        $loopResultRow->set("PRODUCTS_COUNT", 0);
        if (isset($results['product']) && is_array($results['product'])) {
            $loopResultRow->set("PRODUCTS_COUNT", count($results['product']));
        }

        foreach ($results as $searchType => $result) {
            $loopResultRow
                ->set(strtoupper($searchType), $result ? implode(',', $result) : 0);
        }

        $loopResult->addRow($loopResultRow);

        return $loopResult;
    }

    protected function handleSearch(TheliaTntSearch $tnt, $searchWords, $searchType, $offset, $limit, $locale = null)
    {
        $index = $locale ? $searchType . '_' . $locale . '.index' : $searchType . '.index';

        return $tnt->searchAndPaginate($searchWords, $index, $offset, $limit);
    }
}
