import '@mdi/font/css/materialdesignicons.css'; // Ensure you are using css-loader
import Vue from 'vue';
import Vuetify, {
  VTextField,
} from 'vuetify/lib';

const config = require('../../customization/config.js');

Vue.use(Vuetify, {
  components: {
    VTextField,
  },
});

export default new Vuetify({
  icons: {
    iconfont: 'mdi',
  },
  theme: {
    themes: {
      light: config.themeLight,
      dark: config.themeDark,
    },
  },
});
