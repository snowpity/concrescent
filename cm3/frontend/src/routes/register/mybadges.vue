<template>
<v-container class="pa-2" fluid>
    <p v-show="!ownedbadgecount">
        <i>You have no badges. Click on the link in your confirmation email, or Add one.</i><br/>
    </p>
    <v-row >
        <v-col cols="12" md="6" lg="4" xl="3" v-for="(badge, idx) in ownedbadges" :key="badge['id-string']">
            <v-card>
                <v-row>
                    <v-spacer></v-spacer>
                    <v-card-title >{{badge | badgeDisplayName}}</v-card-title>
                    <v-spacer></v-spacer>
                </v-row>
                <v-row>
                    <v-spacer></v-spacer>
                    <h3 >{{badge | badgeDisplayName(true)}}&zwj;</h3>
                    <v-spacer></v-spacer>
                </v-row>
                <v-card-actions>
                    {{badge['badge-type-name']}}

                    <v-spacer></v-spacer>
                    <v-btn icon @click.stop="displayBadge = idx">
                        <v-icon>mdi-information</v-icon>
                    </v-btn>
                    <v-btn icon :to="{name:'editbadge', params: {editId: badge['id-string']}}">
                        <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>
    <v-row>
        <v-col>
            <v-btn color="primary" :to="{name:'addbadge', params: {cartId: 0}}" left absolute>Add a badge</v-btn>
        </v-col>
        <v-spacer></v-spacer>
        <v-col>
            <v-btn color="primary" :to="{name:'login'}" right absolute>Retrieve badges</v-btn>
        </v-col>
    </v-row>

    <v-dialog v-model="displayBadgeModal" max-width="600" persistent
    :hide-overlay="printingBadge" :fullscreen="printingBadge"
    >
        <v-card>
          <v-card-actions class="d-print-none">
              <v-spacer></v-spacer>
              <v-btn color="blue darken-1"  @click="printBadgeInfo"><v-icon>mdi-printer</v-icon></v-btn>
              <v-btn color="success" @click="displayBadge = ''"><v-icon>mdi-close</v-icon></v-btn>
          </v-card-actions>
            <v-card-title class="headline">Badge Info</v-card-title>
            <v-card-text><p  class="text-center" ><v-btn height=266 width=266 elevation=4><qr-code :text="displayBadgeData ? displayBadgeData['qr-data'] : ''"></qr-code></v-btn></p>
                <v-row>
                    <v-spacer></v-spacer>
                    <v-card-title >{{displayBadgeData | badgeDisplayName}}</v-card-title>
                    <v-spacer></v-spacer>
                </v-row>
                <v-row>
                    <v-spacer></v-spacer>
                    <h3 >{{displayBadgeData | badgeDisplayName(true)}}&zwj;</h3>
                    <v-spacer></v-spacer>
                </v-row>
                <v-card-title class="title">{{displayBadgeData && displayBadgeData['badge-type-name']}}</v-card-title>
                <badgePerksRender :description="displayBadgeProduct ? displayBadgeProduct.description : null " :rewardlist="displayBadgeProduct ? displayBadgeProduct.rewards : null"></badgePerksRender>
                <v-card-title>Addons purchased:</v-card-title>

                    <v-card v-for="addon in (displayBadgeProduct ? displayBadgeData.addons : null)" v-bind:key="addon.id">
                        <v-card-title>
                            <h3 class="black--text">{{addon.name}}</h3>
                        </v-card-title>
                        <v-card-text>
                            <div v-html="addon.description"></div>
                        </v-card-text>
                    </v-card>
            </v-card-text>
        </v-card>
    </v-dialog>
    <v-snackbar
          v-model="displayImportResult"
          :timeout="0"
        >
          {{ importResult }}
          <v-btn
            color="primary"
            text
            @click="clearBadgeRetrievalResult"
          >
            Close
          </v-btn>
    </v-snackbar>
</v-container>

</template>


<script>
import { mapGetters, mapState, mapActions } from 'vuex';

import VueQRCodeComponent from 'vue-qrcode-component';
import badgePerksRender from '@/components/badgePerksRender.vue';

export default {
  components: {
    'qr-code': VueQRCodeComponent,
    badgePerksRender,
  },
  data: () => ({
    promocodeDialog: false,
    promoAppliedDialog: false,
    displayBadge: '',
    printingBadge: false,
  }),
  computed: {
    ...mapState({
      ownedbadges: (state) => state.mydata.ownedbadges,
      products: (state) => state.products.all,
      questions: (state) => state.products.questions,
      addons: (state) => state.products.addons,
      importResult: (state) => state.mydata.BadgeRetrievalResult,
  }),
    ...mapGetters('cart', {
      badges: 'cartProducts',
      total: 'cartTotalPrice',
  }),
    ownedbadgecount() { return Object.keys(this.ownedbadges).length; },
    displayBadgeModal() { return this.displayBadge != ''; },
    displayBadgeData() {
    if (!this.displayBadgeModal) return null;
    return this.ownedbadges[this.displayBadge];
  },
    displayBadgeProduct() {
    if (!this.displayBadgeModal) return null;
    let badgeId = this.displayBadgeData['badge-type-id'];
    let result = this.products.find((item)=> { return item.id == badgeId });
      return result;
  },
    displayImportResult() {
    return this.importResult.length > 0;
  },
  },
  methods: {
    ...mapActions('mydata', [
      'retrieveBadges',
      'clearBadgeRetrievalResult',
  ]),
    ...mapActions('cart', [
      'clearCart',
  ]),
    printBadgeInfo() {
    this.printingBadge = true;
    if (this.printingBadge)
    {
      (function (app) {
          setTimeout(() => {
              window.print();
              setTimeout(()=> {
                app.printingBadge = false;
                // Also, spin up a function to zoom back out
                let viewport = document.querySelector('meta[name="viewport"]');
                let original = viewport.getAttribute('content');
                let force_scale = `${original  }, maximum-scale=0.99`;
                viewport.setAttribute('content', force_scale);
                setTimeout(()=> {
                      viewport.setAttribute('content', original);
                  }, 100);
              }, 1000);
          }, 30);
      }(this));
    }

    },
  },
  created() {
    let { query } = this.$route;
    if (query.gid != undefined) {
      this.retrieveBadges(query);
      // Presumably they're here from a Review Order link or the checkout summary page
      // Which *probably* means it was successful, so... clear the cart!
      this.clearCart();
      this.$router.replace({ ...this.$router.currentRoute, query: {} });
    }
    this.$store.dispatch('products/getAllProducts');
    this.$store.dispatch('products/getAllAddons');

  }
};
</script>
