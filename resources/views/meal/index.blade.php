@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <h2>Essen bestellen</h2>

        <meal-index inline-template :orders="{{ json_encode($orders) }}">

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
                        <h3 class="d-flex justify-content-between">
                            @{{ meal.title }}
                            <div>
                                <small> @{{ meal.price.toLocaleString('de-DE') }} €</small>

                                <template v-if="canOrder(meal)">
                                    <button
                                            v-if="ordersLocal.find(item => item.id === meal.id)"
                                            class="btn btn-danger btn-sm"
                                            @click="toggleOrder(meal)"
                                    >
                                        Abbestellen
                                    </button>

                                    <button
                                            v-else
                                            class="btn btn-outline-secondary btn-sm"
                                            @click="toggleOrder(meal)"
                                    >
                                        Bestellen
                                    </button>
                                </template>

                            </div>
                        </h3>

                        <p> @{{ meal.description }}</p>
                    </div>
                </div>
            </div>

        </meal-index>

    </div>
@endsection
