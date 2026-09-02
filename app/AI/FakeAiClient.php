<?php

namespace App\AI;

use RuntimeException;

class FakeAiClient implements AiClient
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $structuredResponses = [];

    /** @var array<string, list<string>> */
    private array $textResponses = [];

    /** @var list<array{kind: string, task: string, prompt: string, model: string}> */
    private array $calls = [];

    /** @param array<string, array<string, mixed>|list<array<string, mixed>>> $structuredResponses */
    public function __construct(array $structuredResponses = [], array $textResponses = [])
    {
        foreach ($structuredResponses as $task => $responses) {
            $this->structuredResponses[$task] = array_is_list($responses) ? $responses : [$responses];
        }

        foreach ($textResponses as $task => $responses) {
            $this->textResponses[$task] = is_array($responses) ? array_values($responses) : [$responses];
        }
    }

    /** @param array<string, mixed>|list<array<string, mixed>> $responses */
    public function queueStructured(string $task, array $responses): static
    {
        $responses = array_is_list($responses) ? $responses : [$responses];
        $this->structuredResponses[$task] = array_merge($this->structuredResponses[$task] ?? [], $responses);

        return $this;
    }

    public function queueText(string $task, string|array $responses): static
    {
        $responses = is_array($responses) ? array_values($responses) : [$responses];
        $this->textResponses[$task] = array_merge($this->textResponses[$task] ?? [], $responses);

        return $this;
    }

    public function structured(string $task, string $instructions, string $prompt, string $model): array
    {
        $this->calls[] = ['kind' => 'structured', 'task' => $task, 'prompt' => $prompt, 'model' => $model];

        if (($this->structuredResponses[$task] ?? []) === []) {
            throw new RuntimeException("Fake de IA sem resposta para a task [{$task}].");
        }

        return array_shift($this->structuredResponses[$task]);
    }

    public function text(string $task, string $instructions, string $prompt, string $model): string
    {
        $this->calls[] = ['kind' => 'text', 'task' => $task, 'prompt' => $prompt, 'model' => $model];

        if (($this->textResponses[$task] ?? []) === []) {
            throw new RuntimeException("Fake de IA sem resposta para a task [{$task}].");
        }

        return (string) array_shift($this->textResponses[$task]);
    }

    /** @return list<array{kind: string, task: string, prompt: string, model: string}> */
    public function calls(): array
    {
        return $this->calls;
    }

    public function assertCalled(string $task, ?callable $callback = null): void
    {
        foreach ($this->calls as $call) {
            if ($call['task'] === $task && ($callback === null || $callback($call))) {
                return;
            }
        }

        throw new RuntimeException("A task de IA [{$task}] não foi chamada como esperado.");
    }
}
