<div>

    <div class="cis-search">
        <div>
            <i class="fa fa-magnifying-glass"></i>
        </div>
        <input type="text" wire:model='searchString'>
        <a href="#clear" wire:click='$set("searchString",null)'><i class="fa fa-broom"></i></a>
    </div>

    {{ $users->links('pagination::simple-tailwind') }}
    <div class="cis-table mt-3">
        <table>
            <thead>
                <tr>
                    <th wire:click='order("firstname")' class="cursor-pointer">
                        Vorname
                        @if($orderBy == "firstname")
                            @if($orderDirection == "ASC")
                                <i class="fa fa-arrow-down-wide-short"></i>
                            @else
                                <i class="fa fa-arrow-up-wide-short"></i>
                            @endif
                        @endif
                    </th>
                    <th wire:click='order("lastname")' class="cursor-pointer">
                        Nachname
                        @if($orderBy == "lastname")
                            @if($orderDirection == "ASC")
                                <i class="fa fa-arrow-down-wide-short"></i>
                            @else
                                <i class="fa fa-arrow-up-wide-short"></i>
                            @endif
                        @endif
                    </th>
                    <th wire:click='order("email")' class="cursor-pointer">
                        E-Mail Adresse
                        @if($orderBy == "email")
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
                @foreach($users as $user)
                <tr @access("user.edit.base") onclick='location.href="{{ route("user.edit",$user) }}";' class="cursor-pointer" @endaccess>
                    <td>{{ $user->firstname }}</td>
                    <td>{{ $user->lastname }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->created_at->format("d.m.Y") }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
