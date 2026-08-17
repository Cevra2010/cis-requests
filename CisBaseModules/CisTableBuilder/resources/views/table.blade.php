@if($pagination)
    <div class="mb-4">
        {{ $tableData->links("cis-table-builder::pagination",['perpage' => $perpage,'search' => request()->get("search")]) }}
    </div>
@endif

<div class="cis-table-form">
    @if($search || $pagination)
        <form class="space-x-2 mb-4" action="{{ $searchRoute }}" method="GET">
            @csrf
            @if($pagination)
            <label class="cis-form-label">Limit:</label>
            <input type="text" name="perpage" value="{{ $perpage }}" class="cis-form-input w-20">
            @endif

            @if($search)
            <label class="cis-form-label">Suchbegriff:</label>
            <input type="text" name="search" value="{{ request()->get("search") }}" class="cis-form-input">
            @endif
            <button type="submit" class="cis-form-button">Filtern</button>
            <a href="{{ $resetFilersRoute }}" class="cis-form-button bg-purple-400"><i class="fa fa-close"></i></a>
        </form>
    @endif
</div>

<div class="{{ $cssClass }}">
    <table>
        <thead>
            <tr>
                @foreach($fields as $field_name)
                    <th>{{ __($field_name) }}</th>
                @endforeach
                @if($actions->count())
                    <th>{{ __("Aktionen") }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($tableData as $data)
                <tr>
                    @foreach($fields as $field_key => $field_value)
                        @isset($data->$field_key)
                            <td>
                                @if(array_key_exists($field_key,$dateTimes))
                                    {{ Carbon\Carbon::create($data->$field_key)->format($dateTimes[$field_key]) }}
                                @else
                                    {{ $data->$field_key }}
                                @endif
                            </td>
                        @else
                            @if($data->$field_key())
                                <td>{{ $data->$field_key() }}</td>
                            @else
                                <td>no-data</td>
                            @endif
                        @endif
                    @endforeach
                    @if($actions->count())
                        <td class="options">
                            @foreach($actions as $action)
                                {!! $action->getLink($data) !!}
                            @endforeach
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($pagination)
    <div class="mt-4">
        {{ $tableData->links("cis-table-builder::pagination",['perpage' => $perpage,'search' => request()->get("search")]) }}
    </div>
@endif
