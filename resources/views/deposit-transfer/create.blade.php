@extends('layouts.app')

@section('content')
    <div class="container flex-grow-1">
        <div class="row">
            <aside class="col-12 col-lg-3 mb-3">
                @include('partials.user_menu')
            </aside>

            <main class="col-12 col-lg-9 user-index">
                <div class="card">
                    <h1 class="card-header">{{ __('New money transfer') }}</h1>

                    <div class="card-body">
                        <form action="{{ route('deposits.transfer') }}" method="post" role="form">
                            @csrf()

                            <div class="form-group">
                                <label for="source">{{ __('From user') }}</label>

                                <select
                                    id="source"
                                    class="custom-select{{ $errors->has('source') ? ' is-invalid' : '' }}"
                                    name="source"
                                >
                                    @foreach ($users as $user)
                                        <option
                                            value="{{ $user->id }}"
                                            {{ old('source') == $user->id ? 'selected' : ''}}
                                        >
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('source')
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="target">{{ __('To user') }}</label>

                                <select
                                    id="target"
                                    class="custom-select{{ $errors->has('target') ? ' is-invalid' : '' }}"
                                    name="target"
                                >
                                    @foreach ($users as $user)
                                        <option
                                            value="{{ $user->id }}"
                                            {{ old('target') == $user->id ? 'selected' : ''}}
                                        >
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('target'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="value" class="col-form-label-sm">{{ __('Value') }}</label>

                                <input
                                    type="number"
                                    class="form-control{{ $errors->has('value') ? ' is-invalid' : '' }}"
                                    name="value"
                                    id="value"
                                    step="any"
                                    pattern="\d*"
                                    value="{{ old('value') }}"
                                >

                                @error('value'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="comment" class="col-form-label-sm">{{ __('Comment') }}</label>

                                <textarea class="form-control" name="comment" id="comment"></textarea>
                                @error('comment'))
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $errors->first('comment') }}</strong>
                                </div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection()
