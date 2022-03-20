const config = require("../customization/config.js");
import Vue from 'vue'
import App from './App.vue'
import vuetify from './plugins/vuetify';
import VuetifyGoogleAutocomplete from 'vuetify-google-autocomplete';
import {
  currency
} from './plugins/currency'
import {
  subname,
  badgeDisplayName
} from './plugins/subname'
import {
  split_carriagereturn
} from './plugins/split_carriagereturn'
import router from './router'
import store from './storage'

//Just the renderer
import mdRenderer from "vue-markdown-renderer";
Vue.use(mdRenderer, {/* Configuration */});


//Phat editor:
import mavonEditor from 'md-editor2'
import 'md-editor2/dist/css/index.css'
Vue.use(mavonEditor)

Vue.config.productionTip = false
Vue.filter('currency', currency)
Vue.filter('subname', subname)
Vue.filter('badgeDisplayName', badgeDisplayName)
Vue.filter('split_carriagereturn', split_carriagereturn)

Vue.use(VuetifyGoogleAutocomplete, {
  apiKey: config.GoogleAutoCompleteAPIKey
})

new Vue({
  vuetify,
  router,
  store,
  beforeCreate() {
    //Retrieve the cart
    this.$store.commit('cart/initialiseCart');
    //Retrieve the users' data
    this.$store.commit('mydata/initialiseData');
    //Initiate a call to get the products
    //this.$store.dispatch("products/getAllProducts");
  },
  render: h => h(App)
}).$mount('#app')

//Set a trigger whenever the cart changes
store.subscribe((mutation, state) => {
  //Only paying attention to the cart
  if (mutation.type.startsWith("cart/")) {
    // Store the state object as a JSON string
    localStorage.setItem('cart', JSON.stringify(state.cart));
  }
  if (mutation.type.startsWith("mydata/")) {
    // Store the state object as a JSON string
    localStorage.setItem('mydata', JSON.stringify(state.mydata));
  }
});
