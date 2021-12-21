<?php
/*************************************************************************************/
/*      Copyright (c) OpenStudio                                                     */
/*      web : https://www.openstudio.fr                                              */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

/**
 * Created by Franck Allimant, OpenStudio <fallimant@openstudio.fr>
 * Projet: oberflex
 * Date: 21/12/2021
 */

namespace TntSearch\Search;

use TeamTNT\TNTSearch\TNTSearch;

class TntSearchExt extends TNTSearch
{
    public function __construct(array $config)
    {
        parent::__construct();

        $this->loadConfig($config);
    }

    /**
     * Allow a kind of results pagination using offset and limit.
     *
     * @param string $search
     * @param TNTSearch $tnt
     * @param string $index
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function searchAndPaginate(string $search, string $index, int $offset = 0, int $limit = 100): array
    {
        $searchLimit = $limit + $offset;

        $this->selectIndex($index);

        $result = $this->search($search, $searchLimit)['ids'];

        if ($offset === 0) {
            return $result;
        }

        return array_slice($result, $offset, $limit, true);
    }
}
