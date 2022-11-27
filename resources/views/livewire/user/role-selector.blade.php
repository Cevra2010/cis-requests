<div>
    <div class="flex items-center space-x-5 text-slate-700 my-4">
        <i class="fa fa-info-circle text-2xl"></i>
        <p>
            Um eine Rolle zuzuweisen oder abzuwählen klicken Sie einfach auf die jeweilige Rolle.<br>
            Folgende Rollen sind verfügbar.
        </p>
    </div>
    @foreach($roles as $role)
            <div class="flex items-center mb-2 space-x-3 bg-slate-100 hover:bg-slate-200 p-2 cursor-pointer" wire:click='changeRole("{{ $role->cis_row_id }}")'>
                @if($user->roles()->find($role))
                    <div class="p-4 bg-green-300 text-green-600 text-2xl px-6">
                        <i class="fa fa-circle-check"></i>
                    </div>
                @else
                    <div class="p-4 bg-red-300 text-red-600 text-2xl px-6">
                        <i class="fa fa-circle-check"></i>
                    </div>
                @endif
                <div>
                    {{ $role->name }}
                    @if($user->roles()->find($role))
                    <p class="text-green-600">Rolle: aktiv</p>
                    @else
                    <p class="text-red-600">Rolle: inaktiv</p>
                    @endif
                </div>
            </div>
    @endforeach
</div>
