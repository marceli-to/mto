/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

// Require Vue
window.Vue = require('vue');

// VueAxios
import VueAxios from 'vue-axios';
import axios from 'axios';
Vue.use(VueAxios, axios);

// Store
import store from './store';

// Routes
import routes from './routes';

// VueRouter
import VueRouter from 'vue-router';
Vue.use(VueRouter);

// Import notifications
import Notifications from 'vue-notification';
Vue.use(Notifications);

Vue.use(require('vue-moment'));

// Filters
require('./filters');

// Set up VueRouter
const router = new VueRouter({ mode: 'history', routes: routes});

router.beforeEach((to, from, next) => {

  // check if the route requires authentication and user is not logged in
  if (to.matched.some(route => route.meta.requiresAuth) && !store.state.isLoggedIn) {
    // redirect to login page
    next({ name: 'login' });
    return;
  }

  // if logged in redirect to dashboard
  if(to.path === '/login' && store.state.isLoggedIn) {
    next({ name: 'dashboard' });
    return;
  }
  next();
});


// Intercept Axios Response (401, Unauthorized)
Vue.axios.interceptors.response.use((response) => {
    return response;
  }, (error) => {
  if (error.response.status == 401) {
    window.location.href = '/admin/login';
  }
  return Promise.reject(error);
});


// Mount App
import AppComponent from '@/components/App.vue';

const app = new Vue({
  components: { AppComponent },
  router
}).$mount('#app')