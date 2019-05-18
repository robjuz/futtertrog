<template>
  <b-navbar toggleable="lg" type="dark" variant="primary">
    <b-navbar-brand>
      <fa icon="dog" size="2x"/>
    </b-navbar-brand>

    <b-navbar-toggle target="nav-collapse"></b-navbar-toggle>

    <b-collapse id="nav-collapse" is-nav>
      <b-navbar-nav>
        <b-nav-item :to="{ name: 'dashboard' }">{{ $t('Dashboard')}}</b-nav-item>
        <b-nav-item :to="{ name: 'meals.index' }">{{ $t('Place order')}}</b-nav-item>
      </b-navbar-nav>

      <!-- Right aligned nav items -->

      <b-navbar-nav class="ml-auto flex-row align-items-center">
        <gravatar :email="$auth.user().email" size="50" :alt="$auth.user().name" class="rounded-circle"/>
        <div class="d-flex flex-column text-left">
          <b-nav-item :to="{ name: 'settings.index'}">
            <fa icon="cog" fixed-width/>
            {{ $t('Settings') }}
          </b-nav-item>
          <b-nav-item href="/logout" @click.prevent="$auth.logout()">
            <fa icon="sign-out-alt" fixed-width/>
            {{ $t('Logout') }}
          </b-nav-item>
        </div>
      </b-navbar-nav>
    </b-collapse>
  </b-navbar>
</template>

<script>
export default {
  name: "MainNavigation"
};
</script>
