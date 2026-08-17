@foreach($menu->getParentEntries() as $parent)
    @php $children = $menu->getChildEntries($parent->slug); @endphp

    @if($children->isNotEmpty())
        {{-- Group label — nicht klickbar --}}
        <div class="flex items-center gap-2 px-4 pt-4 pb-1">
            <i class="fa fa-{{ $parent->icon }} w-4 text-center shrink-0 text-gray-400 text-[10px]"></i>
            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ $parent->text }}</span>
        </div>
        @foreach ($children as $child)
            <a href="{{ $child->getUrl() }}"
               @class([
                   'flex items-center gap-3 pl-8 pr-3 py-1.5 rounded-lg text-sm transition-colors duration-150',
                   'bg-primary-500/20 text-primary-300' => $child->isCurrent(),
                   'text-gray-400 hover:bg-gray-800 hover:text-white' => !$child->isCurrent(),
               ])>
                <span class="w-1 h-1 rounded-full bg-current shrink-0"></span>
                <span>{{ $child->text }}</span>
            </a>
        @endforeach
    @else
        {{-- Normaler Link-Eintrag --}}
        <a href="{{ $parent->getUrl() }}"
           @class([
               'flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150',
               'bg-primary-600 text-white hover:bg-primary-700' => $parent->isCurrent(),
               'text-gray-400 hover:bg-gray-800 hover:text-white' => !$parent->isCurrent(),
           ])>
            <i class="fa fa-{{ $parent->icon }} w-4 text-center shrink-0"></i>
            <span>{{ $parent->text }}</span>
        </a>
    @endif
@endforeach
