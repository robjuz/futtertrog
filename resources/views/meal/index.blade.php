@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <h2 class="py-3">Essen bestellen</h2>

        <meal-index inline-template :orders="{{ json_encode($orders) }}">
            <div>

                <div class="row">
                <div class="col-md-4">

                    <date-picker
                            v-model="date"
                            :language="de"
                            full-month-name
                            inline
                            monday-first
                    ></date-picker>

                </div>

                <div class="col-md-8">

                    <b-alert
                            show
                            v-for="message in messages"
                            :key="message.text"
                            :variant="message.type"
                    >
                        @{{ message.text }}
                    </b-alert>

                    <div v-for="meal in meals" class="border-top border-bottom py-3">
                        <a :href="'/meals/' + meal.id + '/edit'" v-if="canUpdate(meal)"
                           class="btn btn-link text-info pl-0">
                            Bearbeiten
                        </a>
                        <button
                                v-if="canDelete(meal)"
                                class="btn btn-link text-danger"
                                @click="deleteMeal(meal)"
                        >
                            Löschen
                        </button>
                        <h4 class="d-flex justify-content-between">
                            @{{ meal.title }}
                            <div>
                                <small> @{{ meal.price.toLocaleString('de-DE') }} €</small>

                                <template v-if="canOrder(meal)">
                                    <button
                                            v-if="ordersLocal.find(item => item.id === meal.id)"
                                            class="btn btn-outline-danger btn-sm"
                                            @click="toggleOrder(meal)"
                                    >
                                        Abbestellen
                                    </button>

                                    <button
                                            v-else
                                            class="btn btn-outline-primary btn-sm"
                                            @click="toggleOrder(meal)"
                                    >
                                        Bestellen
                                    </button>
                                </template>

                            </div>
                        </h4>

                        <p class="text-dark"> @{{ meal.description }}</p>
                    </div>
                </div>
            </div>

            </div>
        </meal-index>

    </div>
@endsection
