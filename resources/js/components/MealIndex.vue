<template>
    <div class="row">
        <div class="col-md-4">

            <div class="position-sticky" style="top: 5px;">
                <date-picker
                        v-model="date"
                        inline
                />
            </div>

        </div>

        <div class="col-md-8">
            <div v-for="meal in meals" class="border-top border-bottom py-3">
                <a :href="'/meals/' + meal.id + '/edit'" v-if="canUpdate(meal)" class="btn btn-link text-info pl-0">
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
                    {{ meal.title }}
                    <small> {{ meal.price.toLocaleString('de-DE') }} €</small>
                </h3>

                <p> {{ meal.description }}</p>

                <template v-if="canOrder(meal)">
                    <button
                            v-if="userMeals.find(item => item.id === meal.id)"
                            class="btn btn-danger"
                            @click="toggleOrder(meal)"
                    >
                        Abbestellen
                    </button>

                    <button
                            v-else
                            class="btn btn-outline-secondary"
                            @click="toggleOrder(meal)"
                    >
                        Bestellen
                    </button>
                </template>
            </div>
        </div>
    </div>

</template>
<script>

  import moment from 'moment';

  export default {
    name: 'MealIndex',
    data () {
      return {
        date: moment.now(),
        meals: [],
        userMeals: [],
      };
    },
    created () {
      this.fetchData();
    },
    watch: {
      'date': 'fetchData',
    },
    methods: {

      canDelete (meal) {
        return user.is_admin;
      },

      canOrder (meal) {
        return moment(meal.date).isAfter(moment.now(), 'day');
      },

      canUpdate (meal) {
        return user.is_admin;
      },

      async deleteMeal (meal) {
        try {
          await axios.delete('/meals/' + meal.id);
          this.meals = this.meals.filter(item => item.id !== meal.id);
        } catch (e) {
          let response = e.response;

          if (response.data && response.data.message) {
            alert(response.data.message);
          }
        }

      },

      fetchData () {
        axios.get('/meals', {
          params: {
            date: moment(this.date).format('YYYY-M-D'),
          },
        }).then(({data}) => {
          this.meals = data;
        });

        axios.get('/user_meals', {
          params: {
            date: moment(this.date).format('YYYY-M-D'),
          },
        }).then(({data}) => {
          this.userMeals = data;
        });

      },

      toggleOrder (meal) {
        axios.post('/user_meal/' + meal.id);

        if (this.userMeals.find(item => item.id === meal.id)) {
          this.userMeals = this.userMeals.filter(item => item.id !== meal.id);
        } else {
          this.userMeals.push(meal);
        }
      },
    },
  };
</script>

<style>
    .vdp-datepicker__calendar {
        margin: auto;
    }
</style>