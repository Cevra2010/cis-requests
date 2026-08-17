<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::orderBy('name')->get();
        return view('property.index', compact('properties'));
    }

    public function create()
    {
        return view('property.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Property::create($data);

        session()->flash('success', 'Eigenschaft wurde angelegt.');
        return redirect()->route('property.index');
    }

    public function edit(Property $property)
    {
        return view('property.edit', compact('property'));
    }

    public function update(Request $request, Property $property)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $property->update($data);

        session()->flash('success', 'Eigenschaft wurde gespeichert.');
        return redirect()->route('property.index');
    }

    public function delete(Property $property)
    {
        $usageCount = \Illuminate\Support\Facades\DB::table('project_property')
            ->where('cis_row_id_property', $property->cis_row_id)
            ->count();

        return view('property.delete', compact('property', 'usageCount'));
    }

    public function destroy(Property $property)
    {
        \Illuminate\Support\Facades\DB::table('project_property')
            ->where('cis_row_id_property', $property->cis_row_id)
            ->delete();

        $property->delete();

        session()->flash('success', 'Eigenschaft "' . $property->name . '" wurde gelöscht.');
        return redirect()->route('property.index');
    }
}
