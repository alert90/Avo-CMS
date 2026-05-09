@extends('admin.layouts.app')

@section('content')
    <form action="{{ route('hotel.admin.store', ['id' => $row->id ? $row->id : '-1', 'lang' => request()->query('lang')]) }}"
        method="post">
        @csrf
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb20">
                <div class="">
                    <h1 class="title-bar">{{ $row->id ? __('Edit: ') . $row->title : __('Add new hotel') }}</h1>
                    @if ($row->slug)
                        <p class="item-url-demo">{{ __('Permalink') }}: {{ url(config('hotel.hotel_route_prefix')) }}/<a
                                href="#" class="open-edit-input" data-name="slug">{{ $row->slug }}</a>
                        </p>
                    @endif
                </div>
                <div class="">
                    @if ($row->id)
                        <a class="btn btn-warning btn-xs"
                            href="{{ route('hotel.admin.room.index', ['hotel_id' => $row->id]) }}" target="_blank"><i
                                class="fa fa-hand-o-right"></i> {{ __('Manage Rooms') }}</a>
                    @endif
                    @if ($row->slug)
                        <a class="btn btn-primary btn-xs" href="{{ $row->getDetailUrl(request()->query('lang')) }}"
                            target="_blank">{{ __('View Hotel') }}</a>
                    @endif
                </div>
            </div>
            @include('admin.message')
            @if ($row->id)
                @include('Language::admin.navigation')
            @endif
            <div class="lang-content-box">
                <div class="row">
                    <div class="col-md-9">
                        @include('Hotel::admin.hotel.content')
                        @include('Hotel::admin.hotel.pricing')
                        @include('Hotel::admin.hotel.location')
                        @include('Hotel::admin.hotel.surrounding')
                        @include('Core::admin/seo-meta/seo-meta')
                    </div>
                    <div class="col-md-3">
                        <div class="panel">
                            <div class="panel-title"><strong>{{ __('Publish') }}</strong></div>
                            <div class="panel-body">
                                @if (is_default_lang())
                                    <div>
                                        <label><input @if ($row->status == 'publish') checked @endif type="radio"
                                                name="status" value="publish"> {{ __('Publish') }}
                                        </label>
                                    </div>
                                    <div>
                                        <label><input @if ($row->status == 'draft') checked @endif type="radio"
                                                name="status" value="draft"> {{ __('Draft') }}
                                        </label>
                                    </div>
                                @endif
                                <div class="text-right">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i>
                                        {{ __('Save Changes') }}</button>
                                </div>
                            </div>
                        </div>
                        @if (is_default_lang())
                            <div class="panel">
                                <div class="panel-title"><strong>{{ __('Author Setting') }}</strong></div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <?php $user = !empty($row->author_id) ? App\User::find($row->author_id) : false;
                                        \App\Helpers\AdminForm::select2(
                                            'author_id',
                                            [
                                                'configs' => [
                                                    'ajax' => [
                                                        'url' => route('user.admin.getForSelect2'),
                                                        'dataType' => 'json',
                                                    ],
                                                    'allowClear' => true,
                                                    'placeholder' => __('-- Select User --'),
                                                ],
                                            ],
                                            !empty($user->id) ? [$user->id, $user->getDisplayName() . ' (#' . $user->id . ')'] : false,
                                        );
                                        ?>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if (is_default_lang())
                            <div class="panel">
                                <div class="panel-title"><strong>{{ __('Availability') }}</strong></div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label>{{ __('Hotel Featured') }}</label>
                                        <br>
                                        <label>
                                            <input type="checkbox" name="is_featured"
                                                @if ($row->is_featured) checked @endif value="1">
                                            {{ __('Enable featured') }}
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('Hotel Related IDs') }}</label>
                                        <input type="text" value="{{ $row->related_ids }}"
                                            placeholder="{{ __('Eg: 100,200') }}" name="related_ids" class="form-control">
                                        <p>
                                            <i>{{ __('Separated by comma') }}</i>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @include('Hotel::admin.hotel.attributes')

                            <div class="panel">
                                <div class="panel-title"><strong>{{ __('Feature Image') }}</strong></div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        {!! \Modules\Media\Helpers\FileHelper::fieldUpload('image_id', $row->image_id) !!}
                                    </div>
                                </div>
                            </div>
                            {{--                            @include('Hotel::admin.hotel.ical') --}}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
    {!! App\Helpers\MapEngine::scripts() !!}
    <script>
        jQuery(function($) {
            new BCMapEngine('map_content', {
                disableScripts: true,
                fitBounds: true,
                center: [{{ $row->map_lat ?? setting_item('map_lat_default', 51.505) }},
                    {{ $row->map_lng ?? setting_item('map_lng_default', -0.09) }}
                ],
                zoom: {{ $row->map_zoom ?? '8' }},
                ready: function(engineMap) {
                    @if ($row->map_lat && $row->map_lng)
                        engineMap.addMarker([{{ $row->map_lat }}, {{ $row->map_lng }}], {
                            icon_options: {}
                        });
                    @endif
                    engineMap.on('click', function(dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                    engineMap.on('zoom_changed', function(zoom) {
                        $("input[name=map_zoom]").attr("value", zoom);
                    });
                    if (bookingCore.map_provider === "gmap") {
                        engineMap.searchBox($('#customPlaceAddress'), function(dataLatLng) {
                            engineMap.clearMarkers();
                            engineMap.addMarker(dataLatLng, {
                                icon_options: {}
                            });
                            $("input[name=map_lat]").attr("value", dataLatLng[0]);
                            $("input[name=map_lng]").attr("value", dataLatLng[1]);
                        });
                    }
                    engineMap.searchBox($('.bc_searchbox'), function(dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });

                    // Use My Location button handler
                    $('.btn-use-my-location').on('click', function(e) {
                        e.preventDefault();
                        var $btn = $(this);
                        var originalHtml = $btn.html();

                        if (!navigator.geolocation) {
                            alert('{{ __("Geolocation is not supported by your browser") }}');
                            return;
                        }

                        // Show loading state
                        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

                        var watchId = null;
                        var timeoutId = setTimeout(function() {
                            if (watchId) {
                                navigator.geolocation.clearWatch(watchId);
                            }
                            $btn.prop('disabled', false).html(originalHtml);
                            alert('{{ __("Location request timed out. Please try again.") }}');
                        }, 15000); // 15 second timeout

                        // First try to get a quick position, then improve accuracy
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                clearTimeout(timeoutId);
                                if (watchId) {
                                    navigator.geolocation.clearWatch(watchId);
                                }

                                var lat = position.coords.latitude;
                                var lng = position.coords.longitude;

                                // Update input fields
                                $("input[name=map_lat]").val(lat);
                                $("input[name=map_lng]").val(lng);
                                $("input[name=map_zoom]").val(16);

                                // Update map
                                engineMap.clearMarkers();
                                engineMap.addMarker([lat, lng], {
                                    icon_options: {}
                                });
                                engineMap.setCenter([lat, lng]);
                                engineMap.setZoom(16);

                                $btn.prop('disabled', false).html(originalHtml);
                            },
                            function(error) {
                                clearTimeout(timeoutId);
                                if (watchId) {
                                    navigator.geolocation.clearWatch(watchId);
                                }
                                var errorMessage = '';
                                switch(error.code) {
                                    case error.PERMISSION_DENIED:
                                        errorMessage = '{{ __("Location permission denied. Please allow location access.") }}';
                                        break;
                                    case error.POSITION_UNAVAILABLE:
                                        errorMessage = '{{ __("Location information unavailable.") }}';
                                        break;
                                    case error.TIMEOUT:
                                        errorMessage = '{{ __("Location request timed out. Please try again.") }}';
                                        break;
                                    default:
                                        errorMessage = '{{ __("An unknown error occurred.") }}';
                                        break;
                                }
                                alert(errorMessage);
                                $btn.prop('disabled', false).html(originalHtml);
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 15000,
                                maximumAge: 0
                            }
                        );
                    });
                }
            });
        })
    </script>
@endpush
