@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <span>{{ __('Create order') }}</span>
                        {{ isset($date) ? $date->format(__('futtertrog.d.m.Y')) : null }}
                    </div>

                    <div class="card-body">
                        @yield('wrapper-content')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection