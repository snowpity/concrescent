import '@mdi/font/css/materialdesignicons.css'; // Ensure you are using css-loader
import Vue from 'vue';
import Vuetify, {

    VApp,
    VToolbar,
    VCard,
    VBtn,
    VIcon,
    VProgressCircular,
    VList,
    VAvatar,
    VListGroup,
    VTextField,
    VTreeview,
} from 'vuetify/lib';

const config = require('../../customization/config.js');

Vue.use(Vuetify, {
    components: {
        VApp,
        VToolbar,
        VCard,
        VBtn,
        VIcon,
        VProgressCircular,
        VList,
        VAvatar,
        VListGroup,
        VTextField,
        VTreeview,
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