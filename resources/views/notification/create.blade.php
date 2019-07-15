@extends('layouts.app')

@section('content')

    <div class="container flex-grow-1">
        <div class="row">
            <aside class="col-12 col-lg-3 mb-3">
                @include('partials.user_menu')
            </aside>

            <main class="col-12 col-lg-9 user-index">
                <div class="card mb-3">
                    <div class="card-header">{{ __('New notification') }}</div>

                    <div class="card-body">
                        <form action="{{route('notification.store')}}" method="post" role="form">
                            @csrf()

                            <div class="form-group">
                                <label for="user_id">{{ __('User') }}</label>

                                <select
                                    id="user_id"
                                    class="custom-select @error('user_id') is-invalid @enderror"
                                    name="user_id[]"
                                    multiple
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
                                <label for="subject" class="col-form-label-sm">{{ __('Subject') }}</label>

                                <input
                                    id="subject"
                                    class="form-control  @error('subject') is-invalid @enderror"
                                    name="subject"
                                >

                                @error('subject')
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="body" class="col-form-label-sm">{{ __('Message') }}</label>

                                <textarea
                                    id="body"
                                    class="form-control @error('body') is-invalid @enderror"
                                    name="body"
                                ></textarea>
                                @error('body')
                                <div class="invalid-tooltip" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection()
