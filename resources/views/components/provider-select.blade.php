<label for="provider">{{ __("Provider") }}</label>
<select id="provider" name="provider">
    <option value="">{{ __('All') }}</option>
    @foreach($providers as $provider => $name)
        <option
            value="{{ $provider }}"
            {{ $isSelected($provider) ? 'selected="selected"' : '' }}
        >
            {{ $name }}
        </option>
    @endforeach
</select>
