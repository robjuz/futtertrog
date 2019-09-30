<template>
  <b-container class="mt-3">
    <b-row align-h="center">
      <b-col md="8" lg="6">
        <b-card :header="$t('Login')">
          <b-form @submit.prevent="login">
            <b-form-group
              label-for="email"
              :label="$t('E-Mail Address')"
              :state="errors.email ? false : null"
              :invalid-feedback="errors.email ? errors.email[0] : ''"
            >
              <b-form-input
                id="email"
                v-model="form.email"
                required
                autofocus
                :state="errors.email ? false : null"
              />
            </b-form-group>

            <b-form-group
              label-for="password"
              :label="$t('Password')"
              :state="errors.password ? false : null"
              :invalid-feedback="errors.password ? errors.password[0] : ''"
            >
              <b-form-input
                id="password"
                type="password"
                v-model="form.password"
                required
                :state="errors.password ? false : null"
              />
            </b-form-group>

            <b-form-checkbox
              id="checkbox-1"
              v-model="rememberMe"
              class="mb-5"
            >
                {{ $t('Remember Me') }}
            </b-form-checkbox>

            <b-btn type="submit" variant="primary">{{ $t('Login') }}</b-btn>
          </b-form>
        </b-card>
      </b-col>
    </b-row>
  </b-container>
</template>

<script>
export default {
  name: "Login",
  data() {
    return {
      form: {},
      rememberMe: false,
      errors: {}
    };
  },

  methods: {
    login() {

      this.$auth.login({
        data: this.form,
        rememberMe: this.rememberMe
      }).catch(({ response }) => {
          if (response.status === 422) {
              this.errors = response.data.errors;
          }
      });
    }
  }
};
</script>
