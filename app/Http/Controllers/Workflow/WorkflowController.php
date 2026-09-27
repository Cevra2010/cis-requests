<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;

class WorkflowController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->hasPermission('workflow.manage'), 403);

        return view('workflow.index');
    }
}
