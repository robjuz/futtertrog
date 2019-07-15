@extends('layouts.app')

@section('content')
    <main class="container flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <h1 class="card-header">{{ __('Reset Password') }}</h1>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <div class="form-group">
                                <label for="email" class="col-form-label-sm">{{ __('E-Mail Address') }}</label>

                                <input
                                    id="email"
                                    type="email"
                                    class="form-control @error('email') 'is-invalid' @enderror"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                >

                                @error('email'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">
                                {{ __('Send Password Reset Link') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
