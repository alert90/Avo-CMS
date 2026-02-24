@extends('layouts.app')
@section('content')
<div class="bravo-search-page">
    @include('Food::frontend.blocks.form-search-food')
    <div class="bravo-search-result">
        @include('Food::frontend.blocks.list-food')
    </div>
</div>
@endsection
