<?php

namespace App\AI;

use Closure;
use Laravel\Ai\StructuredAnonymousAgent;

class ConfiguredStructuredAgent extends StructuredAnonymousAgent
{
    public function __construct(
        string $instructions,
        Closure $schema,
        private readonly int $configuredMaxTokens,
        private readonly float $configuredTemperature,
    ) {
        parent::__construct($instructions, [], [], $schema);
    }

    public function maxTokens(): int
    {
        return $this->configuredMaxTokens;
    }

    public function temperature(): float
    {
        return $this->configuredTemperature;
    }
}
