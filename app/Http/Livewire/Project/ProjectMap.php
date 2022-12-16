<?php

namespace App\Http\Livewire\Project;

use App\Models\Project;
use Livewire\Component;

class ProjectMap extends Component
{
    public function render()
    {
        $projects = Project::all();
        return view('livewire.project.project-map',[
            'projects' => $projects,
        ]);
    }
}
