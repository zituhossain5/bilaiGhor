{{-- Shared listing top bar: result count + sort dropdown (preserves all other query params).
     Expects: $products (paginator). --}}
<div class="bilai-cat-topbar">
    <p class="bilai-cat-count">
        @if($products->total() > 0)
            Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }} results
        @else
            No products found
        @endif
    </p>
    <form action="" method="GET" id="bilaiSortForm">
        {{-- preserve all active filters in sort form --}}
        @foreach(request()->except(['sort', 'page']) as $key => $val)
            @if(is_array($val))
                @foreach($val as $v)
                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                @endforeach
            @else
            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endif
        @endforeach
        <select name="sort" class="bilai-cat-sort-select" id="bilaiSortSelect">
            <option value="1" @if(request('sort')==1) selected @endif>Sort by Latest</option>
            <option value="2" @if(request('sort')==2) selected @endif>Oldest First</option>
            <option value="3" @if(request('sort')==3) selected @endif>Price: High to Low</option>
            <option value="4" @if(request('sort')==4) selected @endif>Price: Low to High</option>
            <option value="5" @if(request('sort')==5) selected @endif>Name: A–Z</option>
            <option value="6" @if(request('sort')==6) selected @endif>Name: Z–A</option>
        </select>
    </form>
</div>
