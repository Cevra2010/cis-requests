<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use CisFoundation\CisCategoryManager\CisCategoryManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $activeType  = $request->get('type', array_key_first(CisCategoryManager::getTypes()) ?? 'project.category');
        $types       = CisCategoryManager::getTypes();
        $categories  = CisCategoryManager::forType($activeType);

        return view('category.index', compact('types', 'categories', 'activeType'));
    }

    public function create(Request $request)
    {
        $types      = CisCategoryManager::getTypes();
        $activeType = $request->get('type', array_key_first($types) ?? 'project.category');

        return view('category.create', compact('types', 'activeType'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'        => 'required|string',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|size:7',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        DB::table('categories')->insert(array_merge($data, [
            'sort_order' => $data['sort_order'] ?? 0,
            'module'     => CisCategoryManager::getTypes()[$data['type']]['module'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        session()->flash('success', 'Kategorie „' . $data['name'] . '" wurde erstellt.');
        return redirect()->route('category.index', ['type' => $data['type']]);
    }

    public function edit(int $id)
    {
        $category = DB::table('categories')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        $types    = CisCategoryManager::getTypes();

        return view('category.edit', compact('category', 'types'));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|size:7',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $category = DB::table('categories')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        DB::table('categories')->where('id', $id)->update(array_merge($data, [
            'sort_order' => $data['sort_order'] ?? 0,
            'updated_at' => now(),
        ]));

        session()->flash('success', 'Kategorie wurde aktualisiert.');
        return redirect()->route('category.index', ['type' => $category->type]);
    }

    public function delete(int $id)
    {
        $category = DB::table('categories')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        return view('category.delete', compact('category'));
    }

    public function destroy(Request $request, int $id)
    {
        $request->validate(['delete_key' => 'required|string']);

        $category = DB::table('categories')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        if ($request->delete_key !== 'DEL-' . $category->name) {
            return back()->withErrors(['delete_key' => 'Sicherheitsabfrage ist nicht korrekt.'])->withInput();
        }

        DB::table('categories')->where('id', $id)->update(['deleted_at' => now()]);

        session()->flash('success', 'Kategorie wurde gelöscht.');
        return redirect()->route('category.index', ['type' => $category->type]);
    }
}
