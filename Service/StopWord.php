<?php

namespace TntSearch\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TntSearch\Event\StopWordEvent;

class StopWord
{
    public function __construct(protected EventDispatcherInterface $dispatcher) {}

    public function getStopWords(string $locale = null): array
    {
        $event = new StopWordEvent();
        $event->setLocale($locale);

        $this->dispatcher->dispatch($event, StopWordEvent::GET_STOP_WORDS);

        if ($event->getStopWords() || !is_file($file = __DIR__ . '/../StopWords/' . $locale . '.json')) {
            return $event->getStopWords();
        }

        $event->setStopWords(json_decode(file_get_contents($file)));

        return $event->getStopWords();
    }
}