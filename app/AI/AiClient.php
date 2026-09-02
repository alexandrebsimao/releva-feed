<?php

namespace App\AI;

interface AiClient
{
    /** @return array<string, mixed> */
    public function structured(string $task, string $instructions, string $prompt, string $model): array;

    public function text(string $task, string $instructions, string $prompt, string $model): string;
}
