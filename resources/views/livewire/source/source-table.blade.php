<div>

    <div class="cis-search">
        <div>
            <i class="fa fa-magnifying-glass"></i>
        </div>
        <input type="text" wire:model='searchString'>
        <a href="#clear" wire:click='$set("searchString",null)'><i class="fa fa-broom"></i></a>
    </div>

    <div class="cis-table mt-3">
        <table>
            <thead>
                <tr>
                    <th wire:click='order("name")' class="cursor-pointer">
                        Name
                        @if($orderBy == "name")
                            @if($orderDirection == "ASC")
                                <i class="fa fa-arrow-down-wide-short"></i>
                            @else
                                <i class="fa fa-arrow-up-wide-short"></i>
                            @endif
                        @endif
                    </th>
                    <th wire:click='order("created_at")' class="cursor-pointer">
                        Erstellt am
                        @if($orderBy == "created_at")
                            @if($orderDirection == "ASC")
                                <i class="fa fa-arrow-down-wide-short"></i>
                            @else
                                <i class="fa fa-arrow-up-wide-short"></i>
                            @endif
                        @endif
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($sources as $source)
                <tr onclick='location.href="{{ route("source.edit",$source) }}";' class="cursor-pointer">
                    <td>
                        {{ $source->name }}
                    </td>
                    <td>{{ $source->created_at->format("d.m.Y, H:i") }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
