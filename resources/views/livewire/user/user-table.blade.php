<div>
    <div class="flex items-center gap-2 mb-4">
        <div class="relative flex-1 max-w-sm">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <i class="fa fa-magnifying-glass text-gray-400 text-sm"></i>
            </div>
            <input type="text"
                   wire:model.live.debounce.300ms="searchString"
                   placeholder="Benutzer suchen…"
                   class="cis-input pl-9">
        </div>
        @if($searchString)
            <button wire:click='$set("searchString", null)' class="btn-ghost btn-sm">
                <i class="fa fa-xmark"></i>
            </button>
        @endif
    </div>

    <div class="mb-3">{{ $users->links('pagination::simple-tailwind') }}</div>

    <div class="cis-table">
        <table>
            <thead>
                <tr>
                    @foreach([['firstname','Vorname'],['lastname','Nachname'],['email','E-Mail'],['created_at','Erstellt']] as [$field,$label])
                    <th wire:click='order("{{ $field }}")' class="cursor-pointer select-none">
                        <span class="flex items-center gap-1">
                            {{ $label }}
                            @if($orderBy === $field)
                                <i class="fa fa-arrow-{{ $orderDirection === 'ASC' ? 'down' : 'up' }}-wide-short text-primary-400"></i>
                            @else
                                <i class="fa fa-sort text-gray-300"></i>
                            @endif
                        </span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr onclick='location.href="{{ route("user.edit", $user) }}"' class="cursor-pointer">
                        <td class="font-medium text-gray-900">{{ $user->firstname }}</td>
                        <td>{{ $user->lastname }}</td>
                        <td class="text-gray-600">{{ $user->email }}</td>
                        <td class="text-gray-500 text-sm">{{ $user->created_at->format('d.m.Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-10 text-gray-400 text-sm">Keine Benutzer gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
