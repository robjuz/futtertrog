<template>
  <b-navbar fixed="bottom" type="dark" variant="primary">
    <b-navbar-nav pills fill class="w-100">
      <b-nav-item :to="{ name: 'dashboard'}">
        <fa icon="home" :title="$t('Dashboard')"/>
      </b-nav-item>

      <b-nav-item :to="{ name: 'meals.index' }">
        <fa icon="utensils" :title="$t('Place order')"/>
      </b-nav-item>

      <b-nav-item-dropdown right dropup no-caret>
        <template slot="button-content">
          <fa icon="ellipsis-v" :title="$t('Admin area')"/>
        </template>
        <b-dropdown-item>one</b-dropdown-item>
        <b-dropdown-item>two</b-dropdown-item>

        <b-dropdown-divider></b-dropdown-divider>


      <b-dropdown-item :to="{ name: 'settings.index'}">
        <fa icon="cog" fixed-width/>
        {{ $t('Settings') }}
      </b-dropdown-item>

        <b-dropdown-item-button @click="$auth.logout()">
          <fa icon="sign-out-alt" fixed-width/>
          {{ $t('Logout') }}
        </b-dropdown-item-button>
      </b-nav-item-dropdown>
    </b-navbar-nav>
  </b-navbar>
</template>

<script>
export default {
  name: "MobileNavigation"
};
</script>
