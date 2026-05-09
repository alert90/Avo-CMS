<tr>
    <td class="booking-history-type">
        @if($service = $booking->service)
            <i class="{{$service->getServiceIconFeatured()}}"></i>
        @endif
        <small>{{$booking->object_model}}</small>
    </td>
    <td>
        @if($service = $booking->service)
            <a target="_blank" href="{{$service->getDetailUrl()}}">
                {{$service->title}}
            </a>
            <small>
                <div>{{ __("Customer Info") }}</div>
                <div>
                    {{ __("First Name") }}: {{ $booking->first_name }}
                </div>
                <div>
                    {{ __("Last Name") }}: {{ $booking->last_name }}
                </div>
            </small>
        @else
            {{__("[Deleted]")}}
        @endif
    </td>
    <td class="a-hidden">{{display_date($booking->created_at)}}</td>
    <td class="a-hidden">
        {{ $booking->start_date ? display_date($booking->start_date) : '-' }}
    </td>
    <td>
        <div>{{__("Total")}}: {{format_money_main($booking->total)}}</div>
        <div>{{__("Paid")}}: {{format_money_main($booking->paid)}}</div>
        <div>{{__("Remain")}}: {{format_money($booking->total - $booking->paid)}}</div>
    </td>
    <td>
        {{ format_money($booking->commission) }}
    </td>
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
