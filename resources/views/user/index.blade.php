@extends('layouts.app')

@section('content')
    <div class="container flex-grow-1">
        <div class="row">
            <aside class="col-12 col-lg-3 mb-3">
                @include('partials.user_menu')
            </aside>

            <main class="col-12 col-lg-9 user-index">
                <div class="card">
                    <h1 class="card-header d-flex align-items-center">
                        {{ __('User index') }}
                    </h1>

                    <div class="card-body">
                        <table class="table">
                            <thead>
                            <th>{{__('Name')}}</th>

                            <th class="d-none d-sm-block">{{__('Email')}}</th>

                            <th>{{__('Balance')}}</th>
                            </thead>
                            <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>
                                        <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
                                    </td>

                                    <td class="d-none d-sm-block">{{ $user->email }}</td>

                                    <td class="{{ $user->balance > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($user->balance, 2, ',','.') }} €
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

@endsection
