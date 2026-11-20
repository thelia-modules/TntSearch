<?php

declare(strict_types=1);

namespace TntSearch\Synonym;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Domain\Search\Dictionary\SearchTermNormalizer;
use Thelia\Domain\Search\Event\SearchSynonymWriteEvent;
use Thelia\Model\LangQuery;
use Thelia\Model\SearchSynonymQuery;
use TntSearch\Model\TntSynonymQuery;

#[Autoconfigure(public: true)]
final readonly class LegacySynonymMigration
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function migrate(): int
    {
        $groups = [];

        foreach (TntSynonymQuery::create()->filterByEnabled(true)->orderByGroupId()->orderByPosition()->find() as $synonym) {
            $term = SearchTermNormalizer::normalize((string) $synonym->getTerm());

            if ('' !== $term) {
                $groups[(int) $synonym->getGroupId()][] = $term;
            }
        }

        $created = 0;

        foreach (LangQuery::create()->filterByActive(true)->find() as $lang) {
            foreach ($groups as $groupId => $terms) {
                $terms = array_values(array_unique($terms));

                if (\count($terms) < 2 || SearchSynonymQuery::create()->filterByLangId($lang->getId())->filterByTerm($terms[0])->exists()) {
                    continue;
                }

                try {
                    $this->dispatcher->dispatch(
                        (new SearchSynonymWriteEvent())
                            ->setLangId($lang->getId())
                            ->setTerm($terms[0])
                            ->setSynonyms(\array_slice($terms, 1))
                            ->setBidirectional(true)
                            ->setActive(true),
                        TheliaEvents::SEARCH_SYNONYM_CREATE,
                    );
                    ++$created;
                } catch (\Throwable $exception) {
                    $this->logger->warning('TntSearch: the synonym group {group} was not copied to the {locale} dictionary: {message}', [
                        'group' => $groupId,
                        'locale' => $lang->getLocale(),
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $created;
    }
}
