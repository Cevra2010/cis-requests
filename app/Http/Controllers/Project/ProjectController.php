<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index() {
        return view("project.index");
    }

    public function create() {
        return view("project.create");
    }

    public function store(StoreProjectRequest $request) {
        $project = new Project();
        $project->fill($request->all())->save();
        return redirect()->route("project.edit",$project);
    }

    public function edit(Project $project)
    {
        return view("project.edit",[
            'project' => $project,
        ]);
    }

    public function products(Project $project) {
        return view("project.products",[
            'project' => $project,
        ]);
    }

    public function mapPreview(Project $project)
    {
        return view("project.map.preview",[
            'project' => $project,
        ]);
    }
}
