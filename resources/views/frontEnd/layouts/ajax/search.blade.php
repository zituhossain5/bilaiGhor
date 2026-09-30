@if($keyword !== '')

<div class="search_product">
		<ul>
		@foreach($packs as $pack)
		<a href="{{ route('kitten.packs') }}#kitten-pack-{{ $pack->id }}">
			<li>
					<div class="search_img">
						<img src="{{ $pack->image ? asset('public/'.$pack->image) : asset('public/no-image.png') }}" alt="{{ $pack->image_alt ?: $pack->name }}">
					</div>
					<div class="search_content">
						<p class="name">{{ $pack->name }} <span class="bilai-search-tag">Kitten Pack</span></p>
						<p  class="price">৳{{ number_format($pack->price, 0) }} @if($pack->old_price && $pack->old_price > $pack->price)<del>৳{{ number_format($pack->old_price, 0) }}</del>@endif</p>
					</div>
			</li>
		</a>
		@endforeach
		@foreach($products as $value)
		<a href="{{route('product',$value->slug)}}">
			<li>
					<div class="search_img">
						<img src="{{asset($value->image?$value->image->image:'')}}" alt="{{ optional($value->image)->image_alt ?: $value->name }}">
					</div>
					<div class="search_content">
						<p class="name">{{$value->name}} @if(!$value->is_digital && $value->stock <= 0)<span class="bilai-search-tag bilai-search-tag--soldout">Sold out</span>@endif</p>
						<p  class="price">৳{{$value->new_price}} @if($value->old_price)<del>৳{{$value->old_price}}</del>@endif</p>
					</div>
			</li>
		</a>
		@endforeach
	</ul>
	<a class="bilai-search-all" href="{{ route('search', ['keyword' => $keyword]) }}">
		@if(count($products) || $packs->isNotEmpty())
			View all results for "{{ $keyword }}"
		@else
			No quick matches — search for "{{ $keyword }}"
		@endif
	</a>
</div>
@endif
