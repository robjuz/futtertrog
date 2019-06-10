@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header">{{ __('Edit user', ['name' => $user->name]) }}</div>

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
                </div>
            </div>
        </div>
    </div>

@endsection