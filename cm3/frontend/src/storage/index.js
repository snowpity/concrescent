import Vue from 'vue';
import Vuex from 'vuex';
import cart from './modules/cart';
import products from './modules/products';
import mydata from './modules/mydata';
import station from './modules/station';

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    cart,
    products,
    mydata,
    station,
  },
});
