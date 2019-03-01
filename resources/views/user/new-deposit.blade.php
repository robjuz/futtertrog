<div class="card mb-3">
    <div class="card-header">{{ __('New deposit') }}</div>

    <div class="card-body">
        <form action="{{route('deposits.store')}}" method="post" role="form">
            @csrf()
            <input type="hidden" name="user_id" value="{{$user->id}}">

            <div class="form-group">
                <label for="value" class="col-form-label-sm">{{ __('Value') }}</label>
                <div>
                    <input type="number" class="form-control" name="value" id="value" step="0.01">
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