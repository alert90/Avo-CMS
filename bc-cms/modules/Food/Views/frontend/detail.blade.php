@extends('layouts.app')
@section('content')
<div class="bravo-single-page">
    <div class="booking-form form">
        {!! $booking_data !!}
    </div>
    @include('Food::frontend.blocks.booking-detail')
</div>
@endsection
