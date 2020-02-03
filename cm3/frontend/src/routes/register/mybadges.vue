<template>
<v-container class="pa-2" fluid>
    <p v-show="!badges.length">
        <i>You have no badges. Click on the link in your confirmation email, or Add one.</i><br/>
    </p>
    <v-row >
        <v-col cols="12" md="6" lg="4" xl="3" v-for="(product, idx) in badges" :key="product.cartId">
            <v-card>
                <v-row>
                    <v-spacer></v-spacer>
                    <v-card-title >{{product | badgeDisplayName}}</v-card-title>
                    <v-spacer></v-spacer>
                </v-row>
                <v-row>
                    <v-spacer></v-spacer>
                    <h3 >{{product | badgeDisplayName(true)}}&zwj;</h3>
                    <v-spacer></v-spacer>
                </v-row>
                <v-card-actions>
                    {{product.name}}

                    <v-spacer></v-spacer>
                    <v-btn icon @click.stop="displayBadge = idx">
                        <v-icon>mdi-information</v-icon>
                    </v-btn>
                    <v-btn icon :to="{name:'editbadge', params: {cartId: product.cartId}}">
                        <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>
    <v-row>
        <v-col>
            <v-btn color="primary" :to="{name:'addbadge', params: {cartId: 0}}" left absolute>Add
                {{badges.length ? "another" : "a"}}
                badge</v-btn>
        </v-col>
    </v-row>
    <v-dialog v-model="displayBadgeModal" max-width="600" persistent
    :hide-overlay="printingBadge" :fullscreen="printingBadge"
    >
        <v-card>
      <v-card-actions class="d-print-none">
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1"  @click="printBadgeInfo"><v-icon>mdi-printer</v-icon></v-btn>
          <v-btn color="success" @click="displayBadge = -1"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-actions>
            <v-card-title class="headline">Badge Info</v-card-title>
            <v-card-text><p  class="text-center" ><v-btn height=266 width=266 elevation=4><qr-code text="CM*A1*efe092dc-337f-11ea-af03-ac1f6bb52d6a"></qr-code></v-btn></p></v-card-text>
            <v-card-text>
                <v-row>
                    <v-spacer></v-spacer>
                    <v-card-title >{{badges[displayBadge] | badgeDisplayName}}</v-card-title>
                    <v-spacer></v-spacer>
                </v-row>
                <v-row>
                    <v-spacer></v-spacer>
                    <h3 >{{badges[displayBadge] | badgeDisplayName(true)}}&zwj;</h3>
                    <v-spacer></v-spacer>
                </v-row>
                <v-card-title class="title">{{badges[displayBadge] && badges[displayBadge].name}}</v-card-title>
                <badgePerksRender :description="displayBadgeProduct ? displayBadgeProduct.description : null " :rewardlist="displayBadgeProduct ? displayBadgeProduct.rewards : null"></badgePerksRender>
                List of addons<br>

              </v-card-text>
        </v-card>
    </v-dialog>

</v-container>
</template>


<script>
import { mapGetters, mapState, mapActions } from 'vuex'

import VueQRCodeComponent from 'vue-qrcode-component'
import badgePerksRender from '@/components/badgePerksRender.vue'

export default {
  components: {
    'qr-code': VueQRCodeComponent,
          badgePerksRender
  },
  data: () => ({
    promocodeDialog: false,
    promoAppliedDialog: false,
    processingCheckoutDialog: false,
    displayBadge: -1,
    printingBadge: false
  }),
computed: {
  ...mapState({
    products: state => state.products.all,
    questions: state => state.products.questions,
    addons: state => state.products.addons
  }),
  ...mapGetters('cart', {
    badges: 'cartProducts',
    total: 'cartTotalPrice'
  }),
  displayBadgeModal: function() { return this.displayBadge > -1;},
  displayBadgeProduct: function() {
    if(!this.displayBadgeModal) return null;
    var badgeId = this.badges[this.displayBadge].selectedBadgeId;
    return this.products.find(function(item){return item.id == badgeId})
  }
},
methods: {
    ...mapActions('cart', [
    'applyPromoToProduct',
    'removeProductFromCart',
    'clearCart'
  ]),
  printBadgeInfo: function() {
    this.printingBadge = true;
    if(this.printingBadge)
    {
      (function(app){
          setTimeout(function() {
              window.print();
              setTimeout(function(){
                app.printingBadge =false;
                //Also, spin up a function to zoom back out
                var viewport = document.querySelector('meta[name="viewport"]');
                var original = viewport.getAttribute("content");
                var force_scale = original + ", maximum-scale=0.99";
                viewport.setAttribute("content", force_scale);
                setTimeout(function()
                  {
                      viewport.setAttribute("content", original);
                  }, 100);
              }, 1000);
          }, 30);
      })(this);
    }

  },
  checkout (products) {
    this.processingCheckoutDialog = true;
    this.$store.dispatch('cart/checkout', products)
  }
}
}
</script>
