<?php

namespace Tests\Support;

use App\Contracts\DocumentTextExtractor;
use RuntimeException;

class FakeDocumentTextExtractor implements DocumentTextExtractor
{
    public bool $available = true;

    public bool $shouldFail = false;

    public string $text = '';

    public float $confidence = 0.95;

    public function reads(string $text, float $confidence = 0.95): static
    {
        $this->text = $text;
        $this->confidence = $confidence;

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function extractText(string $contents): array
    {
        if ($this->shouldFail) {
            throw new RuntimeException('OCR service unavailable');
        }

        return ['text' => $this->text, 'confidences' => [$this->confidence]];
    }
}
