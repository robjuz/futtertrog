import Vue from 'vue';
import BootstrapVue from 'bootstrap-vue';
import router from '@/router';
import { library } from '@fortawesome/fontawesome-svg-core'
import { fas } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import messages from '@/messages';
import axios from 'axios';
import VueAxios from 'vue-axios';
import VueAuth from '@websanova/vue-auth'
import VueI18n from 'vue-i18n'

library.add(fas);


Vue.use(BootstrapVue);


Vue.component('font-awesome-icon', FontAwesomeIcon)
Vue.component('MainNavigation', require('@/components/MainNavigation').default);

Vue.router = router;

Vue.use(VueI18n);
Vue.use(VueAxios, axios);
Vue.use(VueAuth, {
    auth: require('@/auth_driver.js'),
    http: require('@websanova/vue-auth/drivers/http/axios.1.x.js'),
    router: require('@websanova/vue-auth/drivers/router/vue-router.2.x.js'),
    registerData: { url: '/api/register', method: 'POST', redirect: '/login' },
    loginData: { url: '/api/login', method: 'POST', redirect: '/', fetchUser: true },
    logoutData: { url: 'api/logout', method: 'POST', redirect: '/', makeRequest: true },
    fetchData: { url: '/api/user', method: 'GET', enabled: true },
    refreshData: {url: 'oauth/token/refresh', method: 'POST', enabled: false, interval: 30},
    parseUserData: (data) => data,
    notFoundRedirect: {path: '/'}
});



// Create VueI18n instance with options
const i18n = new VueI18n({
    locale: document.documentElement.lang, // set locale
    messages: {
        de: messages['de.strings'],
        en: messages['en.strings']
    }
});


new Vue({
    el: '#app',
    router,
    i18n,
});