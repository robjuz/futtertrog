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
            </tr>
            </thead>
            <tbody>
            @foreach ($meals as $meal)
                <tr>
                    <th scope="row">{{ $loop->iteration }}</th>
                    <td>{{ $meal->date->format(__('futtertrog.d.m.Y')) }}</td>
                    <td>{{ $meal->title }}</td>
                    <td>{{ number_format($meal->price, 2, ',','.') }} €</td>
                </tr>
            @endforeach

            </tbody>
        </table>
        {{ $meals->links() }}
    </div>
</div>