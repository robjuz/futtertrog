@extends('layouts.app')

@section('content')

    <h1>{{__('Pot generator')}}</h1>


    <form action="/pot-generator" method="POST" id="pot-form"
          is="pot-generator"
          :lid-width="lidWidth"
          :lid-handle-radius="lidHandleRadius"
          :lid-color="lidColor"
          :pot-width="potWidth"
          :pot-height="potHeight"
          :pot-radius="potRadius"
          :pot-color="potColor"
    >
        @csrf

        <fieldset>
            <legend>{{__('Lid')}}</legend>

            <label for="lid-width">{{__('Width')}}</label>
            <input id="lid-width" name="lidWidth" v-model="lidWidth" type="range" value="320" min="50" max="500" autofocus>

            <label for="lid-handle-radius">{{__('Handle radius')}}</label>
            <input id="lid-handle-radius" name="lidHandleRadius" v-model="lidHandleRadius" type="range" value="15" min="10" max="30">

            <label for="lid-color">
                <span>{{__('Color')}}</span>
                <small>{{__('in HEX notation, e.g. #555 or #fefefe')}}</small>
                @error('lidColor')
                    <span>{{ $message }}</span>
                @enderror
            </label>
            <input id="lid-color" name="lidColor" v-model="lidColor" type="color" value="{{ old('lidColor', '#f28705') }}">
        </fieldset>

        <fieldset>
            <legend>{{__('Pot')}}</legend>

            <label for="pot-width">{{__('Width')}}</label>
            <input id="pot-width" name="potWidth" v-model="potWidth" type="range" value="300" min="50" max="500">

            <label for="pot-height">{{__('Height')}}</label>
            <input id="pot-height" name="potHeight" v-model="potHeight" type="range" value="200" min="50" max="400">

            <label for="pot-radius">{{__('Bottom radius')}}</label>
            <input id="pot-radius" name="potRadius" v-model="potRadius" type="range" value="15" min="10" max="30">

            <label for="pot-color">
                <span>{{__('Color')}}</span>
                <small>{{__('in HEX notation, e.g. #FFF or #fefefe')}}</small>
            </label>
            <input id="pot-color" name="potColor" v-model="potColor" type="color" value="{{ old('potColor', '#f28705') }}">
        </fieldset>

        {{--<button type="submit">{{__('Create')}}</button>--}}
        <input type="submit" value="{{__('Download')}}" name="download">


    </form>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>

<script>

    Vue.component('pot-generator', {
        props: ['lidWidth', 'lidHandleRadius', 'lidColor', 'potWidth', 'potHeight', 'potRadius', 'potColor'],
        data() {
            return {
                lidHeight: 20,
                lidRadius: 5,
                lidGap: 5
            }
        },
        template: `
    <form v-bind="$attrs">
        <slot></slot>
        <output>
            <svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' width='100%' height='100%' viewBox="0 0 512 512">
                <path :stroke="lidColor" :fill="lidColor" :d="
                    'M' + (256 - lidWidth/2) + ',' + (512 - 1 - potHeight - lidGap - lidHeight) +
                    'h' + (lidWidth/2 - lidHandleRadius/2) +
                    'a' + lidHandleRadius + ',' + lidHandleRadius + ' 0,1,1 ' + lidHandleRadius + ' ,0' +
                    'h' + ( lidWidth/2 - lidHandleRadius/2 ) +
                    'v' + ( lidHeight - lidRadius ) +
                    'a' + lidRadius + ',' + lidRadius + ' 0,0,1, ' + ( -1*lidRadius ) + ',' + lidRadius +
                    'h' + ( -1*(lidWidth - 2*lidRadius) ) +
                    'a' + lidRadius + ',' + lidRadius + ' 0,0,1, ' + (-1*lidRadius) + ',' + ( -1*lidRadius ) +
                    'v' + ( -1*(lidHeight - lidRadius) ) +
                    'z'">
                </path>

                <path :stroke="potColor" :fill="potColor" :d="
                    'M' + ( 256 - potWidth/2 ) + ',' + ( 512 - 1 - potHeight ) +
                    'h' + ( potWidth ) +
                    'v' + ( potHeight - potRadius ) +
                    'a' + potRadius + ',' + potRadius + ' 0,0,1, ' + ( -1*potRadius ) + ',' + potRadius +
                    'h' + ( -1*(potWidth - 2*potRadius) ) +
                    'a' + potRadius + ',' + potRadius + ' 0,0,1, ' + ( -1*potRadius ) + ',' + ( -1*potRadius ) +
                    'v' + ( -1*(potHeight - potRadius) ) +
                    'z'">
                </path>
            </svg>
        </output>
    </form>

`
    });

    new Vue({
        el: '#main',
        data: {
            lidWidth: 320,
            lidHandleRadius: 15,
            lidColor: "#f28705",
            potWidth: 300,
            potHeight: 200,
            potRadius: 15,
            potColor: "#f28705",
        },
    })
</script>
@endpush
