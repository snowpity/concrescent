const config = require("../customization/config.js");
global.config = config;
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

import vRouter from 'vue-router';
import routes from './router'
Vue.use(vRouter);

var router = new vRouter({
    mode: global.config.hashMode ? 'hash' : 'history',
    //history: global.config.hashMode ? vRouter.createWebHashHistoy() : vRouter.createWebHistory(),
    routes: routes
})

import store from './storage'

//Phat editor, creates the component v-md-editor
import VueMarkdownEditor from '@kangc/v-md-editor';
import VMdPreview from '@kangc/v-md-editor/lib/preview';
import '@kangc/v-md-editor/lib/style/base-editor.css';
import kancenUS from '@kangc/v-md-editor/lib/lang/en-US';
VueMarkdownEditor.lang.use('en-US', kancenUS);
import vuepressTheme from '@kangc/v-md-editor/lib/theme/vuepress.js';
VueMarkdownEditor.use(vuepressTheme, {
    config: {
        'disabled-menus': ['save', 'toc']
    }
});
VMdPreview.use(vuepressTheme, {});
Vue.use(VueMarkdownEditor)
Vue.use(VMdPreview)

//JSON Editor
import JsonEditorVue from 'json-editor-vue'

Vue.use(JsonEditorVue, {
    // global props & attrs (one-way data flow)
})

//interact components
import VueInteractJs from "vue-interactjs";
Vue.use(VueInteractJs);



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
        //Retrieve the station's data
        this.$store.commit('station/initialiseData');
        //Initiate a call to get the products
        //this.$store.dispatch("products/getAllProducts");
    },
    render: h => h(App)
}).$mount('#app')

//Set a trigger whenever the cart changes
store.subscribe((mutation, state) => {
    //For debug only
    if (mutation.type.startsWith("products/")) {
        // Store the state object as a JSON string
        localStorage.setItem('debug_products', JSON.stringify(state.products));
    }
    //Only paying attention to the cart
    if (mutation.type.startsWith("cart/")) {
        // Store the state object as a JSON string
        localStorage.setItem('cart', JSON.stringify(state.cart));
    }
    if (mutation.type.startsWith("mydata/")) {
        // Store the state object as a JSON string
        localStorage.setItem('mydata', JSON.stringify(state.mydata));
    }
    if (mutation.type.startsWith("station/")) {
        // Store the state object as a JSON string
        localStorage.setItem('station', JSON.stringify(state.station));
    }
});