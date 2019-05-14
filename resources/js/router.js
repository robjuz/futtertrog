import Vue from 'vue';
import VueRouter from 'vue-router'

Vue.use(VueRouter);

import Login from '@/components/auth/Login'
import PasswordEmail from '@/components/auth/password/Email'
import PasswordReset from '@/components/auth/password/Reset'

import Dashboard from '@/components/Dashboard'

const routes = [
    {
        path: '/login',
        name: 'login',
        component: Login,
        // beforeEnter: ifNotAuthenticated,
        meta: {
            auth: false,
            redirect: '/'
        }
    },
    {
        path: '/password/reset/',
        name: 'password-email',
        component: PasswordEmail,
        // beforeEnter: ifNotAuthenticated,
    },
    {
        path: '/password/reset/:token',
        name: 'password-reset',
        component: PasswordReset,
        // beforeEnter: ifNotAuthenticated,
    },
    {
        path: '/',
        component: {
            template: '<div><main-navigation class="mb-3"/><router-view/></div>'
        },
        meta: {
            auth: true,
        },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: Dashboard
            },
        ]
    },
  
];



export default new VueRouter({
    mode: 'history',
    base: 'app/',
    routes
});

