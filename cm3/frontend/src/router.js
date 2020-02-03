import Vue from 'vue'
import Router from 'vue-router'
import Home from './routes/Home.vue'

Vue.use(Router)

export default new Router({
  routes: [{
      path: '/',
      name: 'home',
      meta: {
        title: 'Main Menu'
      },
      component: Home
    },
    {
      path: '/about',
      name: 'about',
      meta: {
        title: 'About ConCrescent'
      },
      component: () => import( /* webpackChunkName: "about" */ './routes/About.vue')
    },
    {
      path: '/config',
      name: 'config',
      meta: {
        title: 'Configuration'
      },
      component: () => import( /* webpackChunkName: "about" */ './routes/Config.vue')
    },
    {
      path: '/login',
      name: 'login',
      meta: {
        title: 'Login'
      },
      component: () => import( /* webpackChunkName: "login" */ './routes/Login.vue')
    },
    {
      path: '/myBadges',
      name: 'myBadges',
      meta: {
        title: 'My Badges'
      },
      component: () => import( /* webpackChunkName: "login" */ './routes/register/mybadges.vue')
    },
    {
      path: '/addbadge',
      name: 'addbadge',
      meta: {
        title: 'Add a badge'
      },
      component: () => import( /* webpackChunkName: "AddEditBadge" */ './routes/register/addeditbadge.vue')
    },
    {
      path: '/editbadge/:cartid?',
      name: 'editbadge',
      meta: {
        title: 'Edit badge'
      },
      component: () => import( /* webpackChunkName: "AddEditBadge" */ './routes/register/addeditbadge.vue')
    },
    {
      path: '/cart',
      name: 'cart',
      meta: {
        title: 'View Cart'
      },
      component: () => import( /* webpackChunkName: "Cart" */ './routes/register/cart.vue')
    },
    {
      path: '/checkout',
      name: 'checkout',
      meta: {
        title: 'Checkout'
      },
      component: () => import( /* webpackChunkName: "Cart" */ './routes/register/checkout.vue')
    },
    {
      path: '/unsubscribe',
      name: 'unsubscribe',
      meta: {
        title: 'Unsubscribe'
      },
      component: () => import( /* webpackChunkName: "Cart" */ './routes/register/unsubscribe.vue')
    },
  ]
})
