<div class="card mb-3">
    <div class="card-header">{{ __('Order history') }} </div>

    <div class="card-body">
        <table class="table table-hover">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">{{ __('Date') }}</th>
                <th scope="col">{{ __('Title') }}</th>
                <th scope="col">{{ __('Price') }}</th>
                <th scope="col">{{ __('Quantity') }}</th>
                @admin()
                <th scope="col">{{ __('Delete') }}</th>
                @endadmin
            </tr>
            </thead>
            <tbody>
            @foreach ($meals as $meal)
                <tr>
                    <th scope="row">{{ $loop->iteration }}</th>
                    <td>{{ $meal->date->format(__('futtertrog.d.m.Y')) }}</td>
                    <td>{{ $meal->title }}</td>
                    <td class="text-nowrap">{{ number_format($meal->price, 2, ',','.') }} €</td>
                    <td class="text-center">{{ $meal->pivot->quantity }}</td>
                    @admin()
                    <td>
                        <form action="{{ route('user_meals.destroy', $meal) }}" method="post">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-link btn-sm text-danger">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </td>
                    @endadmin
                </tr>
            @endforeach

            </tbody>
        </table>
        {{ $meals->links() }}
    </div>
</div>