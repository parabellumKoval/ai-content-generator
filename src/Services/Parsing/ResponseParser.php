<?php

namespace ParabellumKoval\AiContentGenerator\Services\Parsing;

use ParabellumKoval\AiContentGenerator\Exceptions\ParsingException;

class ResponseParser
{
    /**
     * @param array<int, string|null> $messages
     */
    public function parse(array $messages, string $format, string $outputType, int $quantity): mixed
    {
        $messages = $this->normalizeMessages($messages);

        if ($format === 'text') {
            return $outputType === 'collection' ? $messages : ($messages[0] ?? '');
        }

        // Prepare a single text blob for JSON parsing
        $primary = implode("\n", $messages);
        $decoded = $this->extractJson($primary);

        if ($format === 'json') {
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        // format === array
        if ($outputType === 'collection') {
            return is_array($decoded) && array_is_list($decoded) ? $decoded : [$decoded];
        }

        return $decoded;
    }

    /**
     * @param array<int, string|null> $messages
     * @return array<int, string>
     */
    protected function normalizeMessages(array $messages): array
    {
        return array_values(array_filter(array_map(function ($item) {
            if ($item === null) {
                return null;
            }

            $value = trim((string) $item);
            return $value === '' ? null : $value;
        }, $messages)));
    }

    protected function extractJson(string $text): array
    {
        $text = trim($text);

        $candidates = [];
        if ($text !== '') {
            $candidates[] = $text;
        }

        $candidates[] = $this->extractFirstJsonBlock($text);

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }

            $decoded = json_decode($candidate, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        throw new ParsingException('Не удалось извлечь JSON из ответа AI провайдера.');
    }

    protected function extractFirstJsonBlock(string $text): ?string
    {
        $start = str_contains($text, '{') ? strpos($text, '{') : strpos($text, '[');
        $endCurly = strrpos($text, '}');
        $endSquare = strrpos($text, ']');

        $end = max($endCurly ?: 0, $endSquare ?: 0);

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        return substr($text, (int) $start, (int) ($end - $start + 1));
    }
}
