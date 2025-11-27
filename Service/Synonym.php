<?php

namespace TntSearch\Service;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Translation\Translator;
use TntSearch\Model\TntSynonymQuery;

class Synonym
{
    public function __construct()
    {
    }

    public function getSynonyms(?int $groupId = null): ?array
    {
        if (!$groupId) {
            return TntSynonymQuery::create()->orderByGroupId()->orderByPosition()->find()->toArray();
        }

        return TntSynonymQuery::create()
            ->filterByGroupId($groupId)
            ->find()
            ->toArray();
    }

    public function getSynonymGroups(): array
    {
        $synonyms = TntSynonymQuery::create()->orderByGroupId()->orderByPosition()->find();
        $groupedSynonyms = [];

        foreach ($synonyms as $synonym) {
            $groupId = $synonym->getGroupId();
            if (!isset($groupedSynonyms[$groupId])) {
                $groupedSynonyms[$groupId] = [
                    'group_id' => $groupId,
                    'terms' => [],
                    'enabled' => $synonym->getEnabled(),
                    'id' => $synonym->getId()
                ];
            }
            $groupedSynonyms[$groupId]['terms'][] = $synonym->getTerm();
        }

        return array_values($groupedSynonyms);
    }

    /**
     * @throws \Exception
     */
    public function saveTerms(string $terms, ?int $groupId = null): void
    {
        if (!$groupId) {
            $max = (int) TntSynonymQuery::create()->withColumn('MAX(group_id)', 'max_group_id')->select('max_group_id')->findOne();
            $groupId = $max ? $max + 1 : 1;
        }

        $terms = explode(',', $terms);

        foreach ($terms as $index => $term) {
            if (TntSynonymQuery::create()->filterByGroupId($groupId, Criteria::NOT_EQUAL)->filterByTerm($term)->exists()) {
                throw new \Exception(Translator::getInstance()->trans('Synonym already exists : ') . $term);
            }

            TntSynonymQuery::create()
                ->filterByGroupId($groupId)
                ->filterByTerm(trim($term))
                ->findOneOrCreate()
                ->setPosition($index + 1)
                ->save();
        }
    }
}