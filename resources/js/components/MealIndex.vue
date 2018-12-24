<script>

  import moment from 'moment';
  import de from 'vuejs-datepicker/dist/locale/translations/de';

  export default {
    name: 'MealIndex',
    props: {
      orders: {
        type: Array,
        required: true,
      },
        initialMessage: {
          type: Object,
            default: function() {return {}}
        }
    },
    data () {
      return {
        de,
        date: moment.now(),
        meals: [],
        ordersLocal: this.orders,
          moment: moment,
          messages: []
      };
    },
    created () {
      this.fetchData();
    },
    watch: {
      'date': 'fetchData',
      orders (newValue) { this.ordersLocal = newValue;},
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
            this.meals = data.meals;
            this.messages = data.messages;
            console.log(this.messages);
        });
      },

      toggleOrder (meal) {
        axios.post('/user_meal/' + meal.id);

        if (this.ordersLocal.find(item => item.id === meal.id)) {
          this.ordersLocal = this.ordersLocal.filter(item => item.id !== meal.id);
        } else {
          this.ordersLocal.push(meal);
        }
      },
    },
  };
</script>

<style lang="scss">
    .vdp-datepicker__calendar {
        width: 100%;
    }

    .meal-select-button-wrapper {
        button:first-child {
            display: none;
        }

        button:last-child {
            display: block;
        }
    }

    .meal-select-button-wrapper:hover {
        button:first-child {
            display: block;
        }

        button:last-child {
            display: none;
        }
    }
</style>