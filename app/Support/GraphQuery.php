<?php

namespace App\Support;

final readonly class GraphQuery
{
    /**
     * @param  list<string>  $concepts
     * @param  list<string>  $terms
     * @param  list<array{id: string, label: string, source: string}>  $entities
     */
    public function __construct(
        public string $intent,
        public array $concepts,
        public array $terms,
        public array $entities,
        public bool $ambiguous = false,
        public string $navigation = 'related',
    ) {}

    public function toArray(): array
    {
        return [
            'intent' => $this->intent,
            'concepts' => $this->concepts,
            'terms' => $this->terms,
            'entities' => $this->entities,
            'ambiguous' => $this->ambiguous,
            'navigation' => $this->navigation,
        ];
    }
}
