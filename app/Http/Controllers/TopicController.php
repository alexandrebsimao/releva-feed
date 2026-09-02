<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTopicRequest;
use App\Http\Requests\UpdateTopicRequest;
use App\Models\Topic;
use App\Services\Topic\TopicAliasManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TopicController extends Controller
{
    public function index(): View
    {
        $topicsByStatus = Topic::query()
            ->with('aliases')
            ->withCount('articles')
            ->orderBy('name')
            ->get()
            ->groupBy('status');

        return view('topics.index', [
            'topicsByStatus' => $topicsByStatus,
            'statusLabels' => [
                Topic::STATUS_APPROVED => 'Aprovados',
                Topic::STATUS_SUGGESTED => 'Sugeridos',
                Topic::STATUS_IGNORED => 'Ignorados',
            ],
            'typeLabels' => [
                Topic::TYPE_TOPIC => 'Assunto',
                Topic::TYPE_COMPANY => 'Empresa',
                Topic::TYPE_LOCATION => 'Local',
                Topic::TYPE_TECHNOLOGY => 'Tecnologia',
                Topic::TYPE_PERSON => 'Pessoa',
                Topic::TYPE_PRODUCT => 'Produto',
            ],
        ]);
    }

    public function create(): View
    {
        return view('topics.create', [
            'topic' => new Topic([
                'type' => Topic::TYPE_TOPIC,
                'status' => Topic::STATUS_APPROVED,
                'active' => true,
            ]),
            'types' => $this->typeLabels(),
            'statuses' => $this->statusLabels(),
        ]);
    }

    public function store(StoreTopicRequest $request, TopicAliasManager $aliases): RedirectResponse
    {
        $data = $request->validated();
        $aliasText = $data['aliases'] ?? null;
        unset($data['aliases']);

        $topic = DB::transaction(function () use ($data, $aliasText, $aliases): Topic {
            $topic = Topic::create($data);
            $aliases->sync($topic, $aliasText);

            return $topic;
        });

        return redirect()->route('topics.edit', $topic)->with('status', 'Assunto criado.');
    }

    public function edit(Topic $topic): View
    {
        $topic->load('aliases');

        return view('topics.edit', [
            'topic' => $topic,
            'types' => $this->typeLabels(),
            'statuses' => $this->statusLabels(),
        ]);
    }

    public function update(UpdateTopicRequest $request, Topic $topic, TopicAliasManager $aliases): RedirectResponse
    {
        $data = $request->validated();
        $aliasText = $data['aliases'] ?? null;
        unset($data['aliases']);

        DB::transaction(function () use ($data, $aliasText, $aliases, $topic): void {
            $topic->update($data);
            $aliases->sync($topic, $aliasText);
        });

        return redirect()->route('topics.edit', $topic)->with('status', 'Assunto atualizado.');
    }

    public function destroy(Topic $topic): RedirectResponse
    {
        $topic->delete();

        return redirect()->route('topics.index')->with('status', 'Assunto removido.');
    }

    public function approve(Topic $topic): RedirectResponse
    {
        $topic->update(['status' => Topic::STATUS_APPROVED, 'active' => true]);

        return redirect()->route('topics.index')->with('status', 'Assunto aprovado.');
    }

    public function ignore(Topic $topic): RedirectResponse
    {
        $topic->update(['status' => Topic::STATUS_IGNORED, 'active' => false]);

        return redirect()->route('topics.index')->with('status', 'Assunto ignorado.');
    }

    /** @return array<string, string> */
    private function typeLabels(): array
    {
        return [
            Topic::TYPE_TOPIC => 'Assunto',
            Topic::TYPE_COMPANY => 'Empresa',
            Topic::TYPE_LOCATION => 'Local',
            Topic::TYPE_TECHNOLOGY => 'Tecnologia',
            Topic::TYPE_PERSON => 'Pessoa',
            Topic::TYPE_PRODUCT => 'Produto',
        ];
    }

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return [
            Topic::STATUS_APPROVED => 'Aprovado',
            Topic::STATUS_SUGGESTED => 'Sugerido',
            Topic::STATUS_IGNORED => 'Ignorado',
        ];
    }
}
