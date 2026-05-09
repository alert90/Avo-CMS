<tr>
    <td class="booking-history-type">
        @if($service = $booking->service)
            <i class="{{$service->getServiceIconFeatured()}}"></i>
        @endif
        <small>{{$booking->object_model}}</small>
    </td>
    <td>
        @if($service = $booking->service)
            @php
                $translation = $service->translate();
            @endphp
            <a target="_blank" href="{{$service->getDetailUrl()}}">
                {{$translation->title}}
            </a>
        @else
            {{__("[Deleted]")}}
        @endif
    </td>
    <td class="a-hidden">{{display_datetime($booking->created_at)}}</td>
    <td class="a-hidden">
        {{$booking->start_date ? display_datetime($booking->start_date) : '-'}}
    </td>
    <td>{{format_money($booking->total)}}</td>
    <td>{{format_money($booking->paid)}}</td>
    <td>{{format_money($booking->total - $booking->paid)}}</td>
    <td class="{{$booking->status}} a-hidden">{{$booking->statusName}}</td>
    <td width="2%">
        <a class="btn btn-xs btn-primary btn-info-booking" data-ajax="{{route('booking.modal',['booking'=>$booking])}}" data-toggle="modal" data-id="{{$booking->id}}" data-target="#modal_booking_detail">
            <i class="fa fa-info-circle"></i>{{__("Details")}}
        </a>
        <a href="{{route('user.booking.invoice',['code'=>$booking->code])}}" class="btn btn-xs btn-primary btn-info-booking open-new-window mt-1" onclick="window.open(this.href); return false;">
            <i class="fa fa-print"></i>{{__("Invoice")}}
        </a>
    </td>
</tr>
