<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

final class GraphQueryInterpreter
{
    public function interpret(string $input, array $graph, ?GraphInference $inference = null): GraphQuery
    {
        Validator::make(['input' => $input], ['input' => ['required', 'string', 'max:500']])->validate();
        $text = $this->normalize($input);
        $intent = $this->intent($text);
        $navigation = match (true) {
            preg_match('/\bdepend\w*/u', $text) === 1 => 'dependencies',
            preg_match('/\b(?:entre|between|relacion\w*|relationship\w*)\b/u', $text) === 1 => 'path',
            preg_match('/\b(?:enfoc\w*|focus\w*)\b/u', $text) === 1 => 'focus',
            default => 'related',
        };
        if ($intent === 'modify') {
            return new GraphQuery('modify', [$input], [], []);
        }

        $vocabulary = [];
        $literalVocabulary = [];
        foreach ($graph['nodes'] as $node) {
            $label = $node['label'] ?? $node['id'];
            if (! is_string($label)) {
                continue;
            }
            foreach ([$label, (string) ($node['source_file'] ?? ''), ...$this->tokens($label)] as $term) {
                if (mb_strlen($term) >= 2 && mb_strlen($term) <= 120 && ! preg_match('/\A-|[\x00-\x1f]/', $term)) {
                    $literalVocabulary[$term] = $term;
                    $vocabulary[$this->normalize($term)] ??= $term;
                }
            }
        }

        // Prefer whole labels, then content tokens. No domain synonym dictionary.
        $literal = trim($input);
        $terms = isset($literalVocabulary[$literal]) ? [$literal] : (isset($vocabulary[$text]) ? [$vocabulary[$text]] : []);
        if ($terms === []) {
            foreach ($this->tokens($input) as $token) {
                $key = $this->normalize($token);
                if (isset($vocabulary[$key]) && ! in_array($key, ['como', 'how', 'que', 'what', 'quien', 'who', 'the', 'los', 'las', 'una', 'con', 'and', 'does'], true)) {
                    $terms[] = $literalVocabulary[$token] ?? $vocabulary[$key];
                }
            }
        }
        $concepts = [$input];
        if ($terms === [] && $inference !== null && $vocabulary !== []) {
            // Bound provider context; never read another project's graph to expand vocabulary.
            $suggestion = $inference->interpret($input, array_slice(array_values($literalVocabulary), 0, 1200));
            $validator = Validator::make($suggestion, [
                'navigation' => ['sometimes', Rule::in(['related', 'dependencies', 'path', 'focus'])],
                'intent' => ['required', Rule::in(['query', 'explore', 'navigate', 'modify'])],
                'concepts' => ['present', 'array', 'list', 'max:8'],
                'concepts.*' => ['required', 'string', 'max:120'],
                'terms' => ['present', 'array', 'list', 'max:8'],
                'terms.*' => ['required', 'string', 'max:120'],
            ]);
            if ($validator->fails()) {
                throw new RuntimeException('La IA no devolvió una interpretación válida.');
            }
            $intent = $suggestion['intent'];
            $navigation = $navigation === 'related' ? ($suggestion['navigation'] ?? $navigation) : $navigation;
            // An isolated topic always remains a valid exploration, even if the LLM calls it a query.
            if (in_array($this->intent($text), ['explore', 'navigate'], true) && $intent === 'query') {
                $intent = $this->intent($text);
            }
            $concepts = array_values(array_unique([...$concepts, ...$suggestion['concepts']]));
            foreach ($suggestion['terms'] as $term) {
                $key = $this->normalize($term);
                if (isset($vocabulary[$key])) {
                    $terms[] = $literalVocabulary[$term] ?? $vocabulary[$key];
                }
            }
        }
        if ($intent === 'modify') {
            return new GraphQuery($intent, $concepts, [], []);
        }

        $terms = array_slice(array_values(array_unique($terms)), 0, 8);
        $entities = [];
        $exactEntities = [];
        $labels = [];
        foreach ($graph['nodes'] as $node) {
            $label = (string) ($node['label'] ?? $node['id']);
            $keys = array_map($this->normalize(...), [$label, (string) ($node['source_file'] ?? ''), ...$this->tokens($label)]);
            foreach ($terms as $term) {
                if (in_array($this->normalize($term), $keys, true)) {
                    $entity = ['id' => (string) $node['id'], 'label' => $label, 'source' => (string) ($node['source_file'] ?? '')];
                    if (in_array($label, $terms, true) || in_array($entity['source'], $terms, true)) {
                        $exactEntities[] = $entity;
                    } else {
                        $entities[] = $entity;
                    }
                    $labels[$label] = ($labels[$label] ?? 0) + 1;
                    break;
                }
            }
        }
        $ambiguous = count($terms) === 1 && ($labels[$terms[0]] ?? 0) > 1;

        return new GraphQuery($intent, $concepts, $terms, array_slice([...$exactEntities, ...$entities], 0, 20), $ambiguous, $navigation);
    }

    private function intent(string $text): string
    {
        if (preg_match('/\A(?:(?:por favor|please)[, ]+)?(?:crea(?:r|me)?|anadi(?:r)?|anade|agrega(?:r)?|genera(?:r)?|guarda(?:r)?|borra(?:r)?|elimina(?:r)?|modifica(?:r)?|actualiza(?:r)?|descarga(?:r|me)?|importa(?:r)?|importe|create|add|generate|save|delete|remove|update|modify|download|import)\b(?=\s)/u', $text)) {
            return 'modify';
        }
        if (preg_match('/\b(?:relacion\w*|relationship\w*|related|destac\w*|highlight|enfoc\w*|focus|conecta\w*|connected|muestr\w*|show|navega\w*|navigate)\b/u', $text)) {
            return 'navigate';
        }
        if (! str_contains($text, '?') && count(preg_split('/\s+/u', $text)) <= 2) {
            return 'explore';
        }

        return 'query';
    }

    private function normalize(string $text): string
    {
        return trim(Str::ascii(mb_strtolower($text)));
    }

    /** @return list<string> */
    private function tokens(string $text): array
    {
        $text = preg_replace('/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', ' ', $text);

        return preg_split('/[^\pL\pN_]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    }
}
