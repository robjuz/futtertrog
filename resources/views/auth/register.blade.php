@extends('layouts.app')

@section('content')
    <main class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <h1 class="card-header">{{ __('Register') }}</h1>

                    <div class="card-body">
                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <div class="form-group">
                                <label for="name" class="col-form-label-sm">
                                    {{ __('Name') }}
                                </label>

                                <input
                                    id="name"
                                    type="text"
                                    class="form-control @error('name') 'is-invalid' @enderror"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    autofocus
                                >

                                @error('name'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="email" class="col-form-label-sm">
                                    {{ __('E-Mail Address') }}
                                </label>

                                <input
                                    id="email"
                                    type="email"
                                    class="form-control @error('email') 'is-invalid' @enderror"
                                    name="email" value="{{ old('email') }}"
                                    required
                                >

                                @error('email'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password" class="col-form-label-sm">{{ __('Password') }}</label>

                                <input
                                    id="password"
                                    type="password"
                                    class="form-control @error('email') 'is-invalid' @enderror"
                                    name="password"
                                    required
                                >

                                @error('password'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password-confirm" class="col-form-label-sm">
                                    {{ __('Confirm Password') }}
                                </label>

                                <input
                                    id="password-confirm"
                                    type="password"
                                    class="form-control"
                                    name="password_confirmation"
                                    required
                                >
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Register') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
