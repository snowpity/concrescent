<template>
<v-container
      class="pa-6"
      fluid
    >
<v-row>
        <v-col
          v-for="card in cards"
          :key="card.title"
          v-bind="{ [`xs${card.flex}`]: true }"
        >
          <v-card :to="card.route">
          <v-icon size=200>{{card.icon}}</v-icon>
              <v-card-title
                class="fill-height align-end"
                v-text="card.title"
              ></v-card-title>
            

            <v-card-actions>
              <v-spacer></v-spacer>
<v-btn >{{card.actiontext}}</v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
      </v-container>
</template>

<script>
// @ is an alias to /src
//import HelloWorld from '@/components/HelloWorld.vue'

export default {
  data: () => ({
cards: [
{ title: 'Register', icon: 'mdi-cart-plus', route: 'addbadge', actiontext: "Add Badge", flex: 12 },
       { title: 'View Cart', icon: 'mdi-cart', route: 'cart', actiontext: "Checkout",flex: 6 },
       { title: 'Login', icon: 'mdi-login',route: 'login',  actiontext: "Access your badges",flex: 6 },
]

  }),
  components: {
//    HelloWorld
  }
}
</script>
