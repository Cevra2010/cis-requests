<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTenderBlock;
use Barryvdh\DomPDF\Facade\Pdf;
use CisFoundation\CisCategoryManager\CisCategoryManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter   = $request->get('status');
        $categoryFilter = $request->get('category');

        $query = Project::query()->orderByDesc('updated_at');

        if ($statusFilter) {
            $query->where('status_code', $statusFilter);
        }
        if ($categoryFilter) {
            $query->where('category_id', $categoryFilter);
        }

        $projects = $query->get();
        $projects->each->syncAutoStatus();

        $categories = CisCategoryManager::forType('project.category');

        return view('project.index', compact('projects', 'categories', 'statusFilter', 'categoryFilter'));
    }

    public function trash()
    {
        $projects = Project::onlyTrashed()->orderByDesc('deleted_at')->get();
        return view('project.trash', compact('projects'));
    }

    public function restore(Request $request, string $project)
    {
        $p = Project::onlyTrashed()->where('cis_row_id', $project)->firstOrFail();
        $p->restore();

        session()->flash('success', 'Projekt "' . $p->name . '" wurde wiederhergestellt.');
        return redirect()->route('project.trash');
    }

    public function create()
    {
        $categories = CisCategoryManager::optionsForType('project.category');
        $statuses   = Project::STATUSES;
        return view('project.create', compact('categories', 'statuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'status_code'   => 'required|string|in:' . implode(',', array_keys(Project::STATUSES)),
            'type'          => 'required|string|in:' . implode(',', array_keys(Project::TYPES)),
            'category_id'   => 'nullable|integer',
            'assignee_type' => 'nullable|string|in:user,group',
            'assignee_id'   => 'nullable|string',
            'client'        => 'nullable|string|max:255',
            'tender_year'   => 'nullable|digits:4|integer|min:2000|max:2100',
            'due_date'      => 'nullable|date',
        ]);

        if (empty($data['assignee_type']) || empty($data['assignee_id'])) {
            $data['assignee_type'] = null;
            $data['assignee_id']   = null;
        }

        $project = Project::create($data);

        if ($project->isVehicle()) {
            $this->ensureVehiclePosition($project);
        }

        session()->flash('success', 'Projekt "' . $project->name . '" wurde erstellt.');
        return redirect()->route('project.edit', $project->cis_row_id);
    }

    /**
     * Fahrzeugausschreibungen bekommen genau eine synthetische Produktposition
     * ("Fahrzeug-Gesamtkonfiguration"), damit Angebote/Bestellung/Wareneingang
     * unverändert genutzt werden können – Anbieter geben hier einen Preis für
     * das gesamte, im Baukasten konfigurierte Fahrzeug ab.
     */
    private function ensureVehiclePosition(Project $project): void
    {
        $product = \App\Models\Product::firstOrCreate(['name' => 'Fahrzeug-Gesamtkonfiguration']);

        \App\Models\ProjectProduct::firstOrCreate(
            [
                'cis_row_id_project' => $project->cis_row_id,
                'cis_row_id_product' => $product->cis_row_id,
            ],
            [
                'product_count' => 1,
                'sort_order'    => 0,
            ]
        );
    }

    public function show(string $project)
    {
        $p = Project::where('cis_row_id', $project)->firstOrFail();
        $p->syncAutoStatus();
        return view('project.show', ['project' => $p]);
    }

    public function edit(string $project)
    {
        $p          = Project::where('cis_row_id', $project)->firstOrFail();
        $categories = CisCategoryManager::optionsForType('project.category');
        $statuses   = Project::STATUSES;

        return view('project.edit', [
            'project'    => $p,
            'categories' => $categories,
            'statuses'   => $statuses,
        ]);
    }

    public function update(Request $request, string $project)
    {
        $p    = Project::where('cis_row_id', $project)->firstOrFail();
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'status_code'   => 'required|string|in:' . implode(',', array_keys(Project::STATUSES)),
            'category_id'   => 'nullable|integer',
            'assignee_type' => 'nullable|string|in:user,group',
            'assignee_id'   => 'nullable|string',
            'client'        => 'nullable|string|max:255',
            'tender_year'   => 'nullable|digits:4|integer|min:2000|max:2100',
            'due_date'      => 'nullable|date',
            'min_order_value' => 'nullable|numeric|min:0',
        ]);

        if (empty($data['assignee_type']) || empty($data['assignee_id'])) {
            $data['assignee_type'] = null;
            $data['assignee_id']   = null;
        }

        $p->update($data);

        session()->flash('success', 'Projekt wurde gespeichert.');
        return redirect()->route('project.edit', $p->cis_row_id);
    }

    public function duplicate(string $project)
    {
        $original = Project::where('cis_row_id', $project)->firstOrFail();

        $copy              = $original->replicate();
        $copy->name        = 'Kopie von ' . $original->name;
        $copy->status_code = 'draft';
        $copy->save();

        session()->flash('success', 'Projekt wurde als "' . $copy->name . '" dupliziert.');
        return redirect()->route('project.edit', $copy->cis_row_id);
    }

    public function destroy(string $project)
    {
        $p = Project::where('cis_row_id', $project)->firstOrFail();
        $p->delete();

        session()->flash('success', 'Projekt "' . $p->name . '" wurde in den Papierkorb verschoben.');
        return redirect()->route('project');
    }

    public function advanceStatus(string $project)
    {
        $p    = Project::where('cis_row_id', $project)->firstOrFail();
        $next = $p->nextStatusCode();
        abort_unless($next, 404);
        abort_unless($p->canAdvanceStatus(auth()->user()), 403);

        $willLock = ! $p->isLocked() && Project::codeIsLocked($next);

        $p->advanceStatus();

        $message = $willLock
            ? 'Ausschreibung wurde fixiert. Produkte und Ausschreibungstext sind jetzt gesperrt.'
            : 'Status wurde auf "' . Project::STATUSES[$next]['label'] . '" gesetzt.';

        session()->flash('success', $message);
        return redirect()->route('project.show', $p->cis_row_id);
    }

    public function revertStatus(string $project)
    {
        $p    = Project::where('cis_row_id', $project)->firstOrFail();
        $prev = $p->previousStatusCode();
        abort_unless($prev, 404);
        abort_unless($p->canRevertStatus(auth()->user()), 403);

        $willUnlock = $p->isLocked() && ! Project::codeIsLocked($prev);
        if ($willUnlock) {
            abort_unless(auth()->user()->hasPermission(Project::OVERRIDE_LOCK_PERMISSION, $p->cis_row_id), 403);
        }

        $p->revertStatus();

        $message = $willUnlock
            ? 'Fixierung wurde aufgehoben. Produkte und Ausschreibungstext sind wieder bearbeitbar.'
            : 'Status wurde auf "' . Project::STATUSES[$prev]['label'] . '" zurückgesetzt.';

        session()->flash('success', $message);
        return redirect()->route('project.show', $p->cis_row_id);
    }

    public function exportPdf(string $project)
    {
        $p      = Project::where('cis_row_id', $project)->firstOrFail();
        $blocks = ProjectTenderBlock::where('cis_row_id_project', $p->cis_row_id)
            ->orderBy('sort_order')
            ->get();

        $branding = (Module::find('Branding')?->isEnabled())
            ? \Modules\Branding\Models\BrandingSetting::current()
            : null;

        // Resolve full content for each block
        $resolvedBlocks = $blocks->map(function ($block) use ($p) {
            if (in_array($block->type, ['heading', 'text', 'space'])) {
                return $block;
            }

            $selected         = $block->config['selected'] ?? null;
            $excludedChildren = $block->config['excluded_children'] ?? [];

            $items = DB::table('project_product')
                ->join('products', 'project_product.cis_row_id_product', '=', 'products.cis_row_id')
                ->where('project_product.cis_row_id_project', $p->cis_row_id)
                ->whereNull('products.deleted_at')
                ->orderBy('project_product.sort_order')
                ->select('products.cis_row_id', 'products.name',
                         'project_product.product_count', 'project_product.note')
                ->get()
                ->when($selected !== null, fn($q) => $q->filter(fn($i) => in_array($i->cis_row_id, $selected)))
                ->map(function ($item) use ($excludedChildren) {
                    $desc       = DB::table('product_descriptions')
                        ->where('cis_row_id_product', $item->cis_row_id)
                        ->whereNull('deleted_at')->first();
                    $item->text = $desc?->text ?? '';

                    $item->children = DB::table('product_child')
                        ->join('products', 'product_child.cis_row_id_child', '=', 'products.cis_row_id')
                        ->where('product_child.cis_row_id_parent', $item->cis_row_id)
                        ->whereNull('products.deleted_at')
                        ->select('products.cis_row_id', 'products.name')
                        ->get()
                        ->reject(fn($c) => in_array($c->cis_row_id, $excludedChildren))
                        ->map(function ($child) {
                            $d           = DB::table('product_descriptions')
                                ->where('cis_row_id_product', $child->cis_row_id)
                                ->whereNull('deleted_at')->first();
                            $child->text = $d?->text ?? '';
                            return $child;
                        });

                    return $item;
                });

            $block->resolvedItems = $items->values();
            return $block;
        });

        $pdf = Pdf::loadView('project.tender-pdf', [
            'project'        => $p,
            'resolvedBlocks' => $resolvedBlocks,
            'branding'       => $branding,
        ])->setPaper('a4', 'portrait');

        $filename = str($p->name)->slug() . '-ausschreibung.pdf';

        return $pdf->stream($filename);
    }
}
