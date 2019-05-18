<template>
  <div>
    <main-navigation class="d-none d-md-flex mb-3"/>
    <mobile-navigation class="d-flex d-md-none"/>
    <router-view/>


  </div>
</template>

<script>

import MainNavigation from '@/components/layout//MainNavigation.vue';
import MobileNavigation from '@/components/layout/MobileNavigation.vue';

export default {
  name: "App",
  components: { MainNavigation, MobileNavigation }
};
</script>