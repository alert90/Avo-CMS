@if(!empty($booking_data['start_date_html']))
    <div class="form-group">
        <label class="control-label">{{__("Start Date")}}</label>
        <div class="form-controls">
            <input type="text" name="start_date" class="form-control" value="{{ $booking_data['start_date'] }}" readonly>
        </div>
    </div>
@endif
@if(!empty($booking_data['end_date_html']))
    <div class="form-group">
        <label class="control-label">{{__("End Date")}}</label>
        <div class="form-controls">
            <input type="text" name="end_date" class="form-control" value="{{ $booking_data['end_date'] }}" readonly>
        </div>
    </div>
@endif
@if(!empty($booking_data['duration']))
    <div class="form-group">
        <label class="control-label">{{__("Duration")}}</label>
        <div class="form-controls">
            <input type="text" name="duration" class="form-control" value="{{ $booking_data['duration'] }}" readonly>
        </div>
    </div>
@endif
@if(!empty($booking_data['total']))
    <div class="form-group">
        <label class="control-label">{{__("Total")}}</label>
        <div class="form-controls">
            <input type="text" name="total" class="form-control" value="{{ format_money($booking_data['total']) }}" readonly>
        </div>
    </div>
@endif
@if(!empty($booking_data['total_html']))
    <div class="form-group">
        <label class="control-label">{{__("Total")}}</label>
        <div class="form-controls">
            <input type="text" name="total_html" class="form-control" value="{{ $booking_data['total_html'] }}" readonly>
        </div>
    </div>
@endif
