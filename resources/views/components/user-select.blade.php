<div>
    <label for="user_id">
        <span>{{__('User')}}</span>
        @error('user_id'))
        <span>{{ $message }}</span>
        @enderror
    </label>
    <select id="user_id" name="user_id">
        @if($showOptionAll)
            <option value="" {{ $isSelected('') ? 'selected="selected"' : '' }}>
                {{ __('All users') }}
            </option>
        @endif
        @foreach($users as $user)
            <option
                    value="{{ $user->id }}"
                    {{ $isSelected($user->id) ? 'selected="selected"' : '' }}
            >
                {{ $user->name }}
            </option>
        @endforeach
    </select>
</div>