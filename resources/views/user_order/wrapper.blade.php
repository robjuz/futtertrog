@extends('layouts.app')

@section('content')
    <main class="container flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <h1 class="card-header d-flex justify-content-between">
                        <span>{{ __('Create order') }}</span>
                        {{ isset($date) ? $date->format(__('futtertrog.date_format')) : null }}
                    </h1>

                    <div class="card-body">
                        @yield('wrapper-content')
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
