@extends('layouts.app')

@section('content')

    <div class="container flex-grow-1">
        <div class="row">
            <aside class="col-12 col-lg-3 mb-3">
                @include('partials.user_menu')
            </aside>

            <main class="col-12 col-lg-9 user-index">
                <div class="card mb-3">
                    <div class="card-header">{{ __('New deposit') }}</div>

                    <div class="card-body">
                        <form action="{{route('deposits.store')}}" method="post" role="form">
                            @csrf()

                            <div class="form-group">
                                <label for="user_id">{{ __('User') }}</label>

                                <select
                                    id="user_id"
                                    class="custom-select @error('user_id') is-invalid @enderror"
                                    name="user_id"
                                >
                                    @foreach ($users as $user)
                                        <option
                                            value="{{ $user->id }}"
                                            {{ old('user_id') == $user->id ? 'selected' : ''}}
                                        >
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('user_id')
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="value" class="col-form-label-sm">{{ __('Value') }}</label>

                                <input
                                    id="value"
                                    type="number"
                                    class="form-control  @error('value') is-invalid @enderror"
                                    name="value"
                                    step="0.01"
                                    pattern="\d*"
                                >

                                @error('value')
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="comment" class="col-form-label-sm">{{ __('Comment') }}</label>

                                <textarea
                                    id="comment"
                                    class="form-control @error('comment') is-invalid @enderror"
                                    name="comment"
                                ></textarea>
                                @error('comment')
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
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
