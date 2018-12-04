@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="card mb-sm-3">
                    <div class="card-header">{{ $user->name }}</div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-3">{{__('Balance')}}</div>
                            <div class="col-sm-9">
                                <span class="{{ auth()->user()->balance > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format(auth()->user()->balance, 2, ',','.') }} €
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <b-card no-body>
                    <b-tabs card>
                        <b-tab title="{{__('New deposit')}}" active>
                            <b-form action="{{route('deposits.store')}}" method="POST">
                                @csrf()
                                <b-form-group
                                        label="{{__('Value')}}"
                                        label-for="value"
                                        invalid-feedback="{{ $errors->first('value') }}"
                                        state="{{ $errors->has('value') ? 'invalid' : 'null' }}"
                                >
                                    <b-form-input
                                            id="value"
                                            name="value"
                                            state="{{ $errors->has('value') ? 'invalid' : 'null' }}"
                                    ></b-form-input>
                                </b-form-group>

                                <b-form-group>
                                    <b-button type="submit" variant="primary">{{__('Submit')}}</b-button>
                                </b-form-group>

                                <input type="hidden" name="user_id" value="{{$user->id}}">
                            </b-form>
                        </b-tab>
                        <b-tab title="{{__('Deposit history')}}">
                            <b-table
                                    striped
                                    hover
                                    :items="{{ json_encode($user->deposits) }}"
                                    :fields="['id', 'value', 'created_at', 'comment]"
                            ></b-table>
                        </b-tab>
                    </b-tabs>
                </b-card>
            </div>
        </div>


@endsection