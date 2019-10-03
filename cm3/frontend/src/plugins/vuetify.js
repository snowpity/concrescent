import '@mdi/font/css/materialdesignicons.css' // Ensure you are using css-loader
import Vue from 'vue';
import Vuetify, {
  VTextField
} from 'vuetify/lib';

Vue.use(Vuetify, {
  components: {
    VTextField
  }
});

export default new Vuetify({
  icons: {
    iconfont: 'mdi',
  },
});