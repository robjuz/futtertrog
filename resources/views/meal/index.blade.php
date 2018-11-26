@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        @can('create', \App\Meal::class)
            <div>
                <a href="{{ route('meals.create') }}" class="btn btn-info mb-3" title="Neues Essen">
                    Neues Essen
                </a>
            </div>

        @endcan
        <meal-index/>

    </div>
@endsection
