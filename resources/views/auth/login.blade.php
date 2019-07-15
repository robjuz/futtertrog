@extends('layouts.app')

@section('content')
    <main class="container flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <h1 class="card-header">{{ __('Login') }}</h1>

                    <div class="card-body">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="form-group">
                                <label for="email" class="col-form-label-sm">
                                    {{ __('E-Mail Address') }}
                                </label>

                                <input
                                    id="email"
                                    autofocus
                                    class="form-control @error('email') 'is-invalid' @enderror"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    required
                                >

                                @error('email'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password" class="col-form-label-sm">
                                    {{ __('Password') }}
                                </label>

                                <input
                                    id="password"
                                    class="form-control @error('password') 'is-invalid' @enderror"
                                    name="password"
                                    type="password"
                                    required
                                >

                                @error('password'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group pt-2">
                                <div class="custom-control custom-checkbox">
                                    <input
                                        id="remember"
                                        class="custom-control-input"
                                        name="remember"
                                        type="checkbox"
                                        {{ old('remember') ? 'checked' : '' }}
                                    >

                                    <label class="custom-control-label" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group mt-4 mb-0">
                                <div class="d-flex flex-wrap">
                                    <button type="submit" class="btn btn-primary mr-auto">{{ __('Login') }}</button>

                                    @if (Route::has('password.request'))
                                        <a class="btn btn-link" href="{{ route('password.request') }}">
                                            {{ __('Forgot Your Password?') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
