<div class="infobox-item">
    <div class="infobox-item-header">
        @if(!empty($row->image_id))
            <div class="infobox-image">
                <a href="{{ $row->getDetailUrl() }}">
                    <img src="{{ get_file_url($row->image_id) }}" alt="{{ $row->title }}" class="img-responsive">
                </a>
            @endif
        <div class="infobox-item-content">
            <h5 class="title">
                <a href="{{ $row->getDetailUrl() }}">{{ $row->title }}</a>
            </h5>
            <div class="location">
                <i class="icon-map-marker"></i>
                <span>{{ $row->address }}</span>
            </div>
            @if(!empty($row->price))
                <span class="price">{{ format_money($row->price) }}</span>
            @endif
        </div>
    </div>
</div>
