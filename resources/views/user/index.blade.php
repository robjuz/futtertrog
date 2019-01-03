@extends('layouts.app')

@section('content')


    <div class="container">

        <div class="card">
            <div class="card-header">
                {{ __('User index') }}
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">{{__('Name')}}</th>
                        <th scope="col">{{__('Email')}}</th>
                        <th scope="col">{{__('Balance')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        <tr>
                            <th scope="row">
                                <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
                            </th>
                            <td>{{ $user->email }}</td>
                            <td>
                        <span class="{{ $user->balance > 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($user->balance, 2, ',','.') }} €
                        </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection