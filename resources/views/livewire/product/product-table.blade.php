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
                    <th>
                        Preis
                        @if($orderBy == "price")
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
                @foreach($products as $product)
                <tr @access("product.edit") onclick='location.href="{{ route("product.edit",$product) }}";' class="cursor-pointer" @endaccess>
                    <td>
                        @if($product->hasParent())
                        [{{ $product->getParent()->name }}] =>
                        @endif
                        {{ $product->name }}
                    </td>
                    <td>
                        @if($product->hasChild() && $product->price() && $product->price()->amount == 0)
                            {{ $product->getGroupPriceForHumans() }} (Sammelpreis)
                        @else
                            {{ $product->priceForHumans() }}
                        @endif
                    </td>
                    <td>{{ $product->created_at->format("d.m.Y, H:i") }}</td>
                </tr>
                    @if($product->hasChild())
                        @foreach($product->getChild() as $child)
                        <tr @access("product.edit") onclick='location.href="{{ route("product.edit",$child) }}";' class="cursor-pointer" @endaccess>
                            <td><i class="fa fa-arrow-right"></i> {{ $child->name }}</td>
                            <td>@if($child->prices()->count()) {{ $child->prices()->first()->amountForHumans() }} @else nicht gesetzt @endif</td>
                            <td>{{ $child->created_at->format("d.m.Y, H:i") }}</td>
                        </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
