@extends('layouts.app')

@section('content')
    <div class="container flex-grow-1">
        <div class="row">
            <aside class="col-12 col-lg-3 mb-3">
                @include('partials.user_menu')
            </aside>

            <main class="col-12 col-lg-9 user-index">
                <div class="card">
                    <h1 class="card-header">{{ __('Edit user', ['name' => $user->name]) }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @method('put')
                        @csrf

                        <div class="form-group">
                            <label for="name" class="col-form-label-sm">
                                {{ __('Name') }}
                            </label>

                            <input id="name"
                                   type="text"
                                   class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   required
                                   autofocus
                            >

                            @if ($errors->has('name'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $errors->first('name') }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="email" class="col-form-label-sm">
                                {{ __('E-Mail Address') }}
                            </label>

                            <input id="email" type="email"
                                   class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                                   name="email" value="{{ old('email', $user->email) }}"
                                   required
                            >

                            @if ($errors->has('email'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="phone_number" class="col-form-label-sm">
                                {{ __('Phone number') }}
                            </label>

                            <input id="phone_number" type="text"
                                   class="form-control{{ $errors->has('phone_number') ? ' is-invalid' : '' }}"
                                   name="phone_number" value="{{ old('phone_number', $user->phone_number) }}"
                            >

                            @if ($errors->has('phone_number'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $errors->first('phone_number') }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="password" class="col-form-label-sm">
                                {{ __('Password') }}
                            </label>

                            <input id="password"
                                   type="password"
                                   class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                                   name="password"
                            >

                            @if ($errors->has('password'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="password-confirm" class="col-form-label-sm">
                                {{ __('Confirm Password') }}
                            </label>

                            <input id="password-confirm"
                                   type="password"
                                   class="form-control"
                                   name="password_confirmation"
                            >
                        </div>

                        @if($user->id !== auth()->id())
                            <div class="form-group pt-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="is_admin" value="0">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           name="is_admin"
                                           id="is_admin"
                                           {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                                           value="1"
                                    >
                                    <label class="custom-control-label" for="is_admin">
                                        {{ __('Is admin') }}
                                    </label>
                                </div>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </form>
                </div>
        </main>
    </div>

@endsection
