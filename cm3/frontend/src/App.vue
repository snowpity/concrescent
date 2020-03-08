<template>
<v-app id="app" :style="{ background: $vuetify.theme.themes.light.backgroundcolor}">
    <v-navigation-drawer v-model="drawer" app temporary>
        <v-list dense>
            <v-list-item v-for="menuitem in drawerItems" :key="menuitem.route" :to="menuitem.route" v-show="menuitem.show == null || menuitem.show()">
                <v-list-item-action>
                  <v-badge :value="menuitem.badge != null && menuitem.badge() != null">
                    <template v-slot:badge>
                      {{menuitem.badge == null ? null : menuitem.badge()}}
                    </template>
                    <v-icon>{{menuitem.icon}}</v-icon>
                  </v-badge>
                </v-list-item-action>
                <v-list-item-content>
                    <v-list-item-title>{{menuitem.label}}</v-list-item-title>
                </v-list-item-content>
            </v-list-item>
        </v-list>
    </v-navigation-drawer>

    <v-app-bar app color="appbar" dark>
        <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
        <v-toolbar-title>{{appTitle}}</v-toolbar-title>
    </v-app-bar>
    <v-content>
        <router-view/>
    </v-content>
</v-app>
</template>


<script>
const config = require("../customization/config.js");
import { mapGetters } from 'vuex'
export default {
  data: () => ({
  "drawer":false,
  }),
  computed: {
    appTitle: function() {
      return this.AppName + (this.$route.name == null ?"" : " - " + (this.$route.meta.title || this.$route.name));
    },
    drawerItems: function() {
      var items = [
        {
          route: "/",
          icon: "mdi-home",
          label: "Home"
        },{
          route: "/login",
          icon: "mdi-login",
          label: "Login"
        },{
          route: "/myBadges",
          icon: "mdi-account-badge-horizontal",
          label: "My badges",
          show: () => {return this.ownedBadgeCount > 0;}
        },{
          route: "/addbadge",
          icon: "mdi-cart-plus",
          label: "Add Badge"
        },{
          route: "/cart",
          icon: "mdi-cart",
          label: "View cart",
          badge: () => {return this.cartCount > 0 ? this.cartCount : null;}
        }
      ];

      return items;
    },
    ...mapGetters('cart', {
      'cartCount': 'cartCount'
    }),
    ...mapGetters('mydata', {
      'ownedBadgeCount': 'ownedBadgeCount'
    }),
    isAdmin: function() {
      //TODO: Detect this. :P
      return false;
    },
    AppName: function() {
      return this.isAdmin ? config.AppNameAdmin : config.AppName;
    }
  },
  watch: {
  '$route.name' : function(name) {
  //Do something when the route changes?
  "Switching route to " + name;
    document.title = this.appTitle;
    }
  },
  created() {
    document.title = this.appTitle;
  }
};
</script>
