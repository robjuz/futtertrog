@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card mb-3">
                    <div class="card-header">{{ __('New money transfer') }}</div>

                    <div class="card-body">
                        <form action="{{ route('deposites.transfer') }}" method="post" role="form">
                            @csrf()
                            
                            <div class="form-group">
                                <label for="source">{{ __('From user') }}</label>
                                <div>
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
                                    @if ($errors->has('source'))
                                        <div class="invalid-tooltip" role="alert">
                                            <strong>{{ $errors->first('source') }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="target">{{ __('To user') }}</label>
                                <div>
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
                                    @if ($errors->has('target'))
                                        <div class="invalid-tooltip" role="alert">
                                            <strong>{{ $errors->first('target') }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="value" class="col-form-label-sm">{{ __('Value') }}</label>
                                <div>
                                    <input 
                                        type="number" 
                                        class="form-control{{ $errors->has('value') ? ' is-invalid' : '' }}" 
                                        name="value" 
                                        id="value" 
                                        step="any"
                                        pattern="\d*"
                                        value="{{ old('value') }}"
                                    >
                                    @if ($errors->has('value'))
                                        <div class="invalid-tooltip" role="alert">
                                            <strong>{{ $errors->first('value') }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="comment" class="col-form-label-sm">{{ __('Comment') }}</label>
                                <div>
                                    <textarea class="form-control" name="comment" id="comment"></textarea>
                                    @if ($errors->has('value'))
                                        <div class="invalid-tooltip" role="alert">
                                            <strong>{{ $errors->first('comment') }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection()