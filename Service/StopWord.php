<?php

namespace TntSearch\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TntSearch\Event\StopWordEvent;

class StopWord
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string|null $locale
     * @return array
     */
    public function getStopWords(string $locale = null): array
    {
        $event = new StopWordEvent();
        $event->setLocale($locale);

        $this->dispatcher->dispatch(StopWordEvent::GET_STOP_WORDS, $event);

        if ($event->getStopWords() || !is_file($file = __DIR__ . '/../StopWords/' . $locale . '.json')) {
            return $event->getStopWords();
        }

        $event->setStopWords(json_decode(file_get_contents($file)));

        return $event->getStopWords();
    }
}