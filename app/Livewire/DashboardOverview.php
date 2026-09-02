<?php

namespace App\Livewire;

use Livewire\Component;

class DashboardOverview extends Component
{
    public int $selectedTopics = 0;

    public function render()
    {
        return view('livewire.dashboard-overview');
    }

    public function addTopic(): void
    {
        $this->selectedTopics++;
    }
}
