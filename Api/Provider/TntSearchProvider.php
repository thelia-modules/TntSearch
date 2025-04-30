<?php

namespace TntSearch\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Api\Bridge\Propel\Service\ApiResourcePropelTransformerService;
use TntSearch\Api\Resource\TntSearch;
use TntSearch\Service\Search;

readonly class TntSearchProvider implements ProviderInterface
{
    public function __construct(
        private Search                              $search,
        private RequestStack                        $requestStack,
        private ApiResourcePropelTransformerService $apiResourcePropelTransformerService
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|null|object
    {
        $request = $this->requestStack->getCurrentRequest();
        $query = $this->requestStack->getCurrentRequest()?->query;
        $search = $this->search;
        $resultsByIndex = $this->search->search(
            $query->get('search'),
            ($index = $query->get('indexes')) ? explode(',', $index) : null,
            $request->getSession()->getLang()->getLocale(),
            $query->get('offset', 0),
            $query->get('limit', 100),
        );

        $data = [];

        foreach ($resultsByIndex as $index => $ids) {
            if (empty($ids)) {
                continue;
            }

            $model = $search->buildPropelQueryFromIndex($index);
            $modelTableMap = $search->buildPropelTableMapFromIndex($index);

            $rows = $model->filterById($ids, Criteria::IN);

            foreach ($ids as $singleId) {
                $givenIdMatched = 'given_id_matched_' . $singleId;
                $rows->withColumn($modelTableMap::COL_ID . "='$singleId'", $givenIdMatched);
                $rows->orderBy($givenIdMatched, Criteria::DESC);
            }

            $data[$index] = array_map(function ($row) use ($index) {
                $resourceStaticName = 'Thelia\\Api\\Resource\\' . ucwords($index);
                return $this->apiResourcePropelTransformerService->modelToResource(
                    resourceClass: $resourceStaticName,
                    propelModel: $row,
                    context: [TntSearch::GROUP_GLOBAL_TNT_FRONT_READ[$index]],
                );
            }, iterator_to_array($rows));
        }

        return $data;
    }
}
