<template>
<v-container class="pa-2" fluid>
    <p v-show="!products.length">
        <i>Cart is empty.</i><br/>
    </p>
    <v-row >
        <v-col cols="12" md="6" lg="4" xl="3" v-for="(product, idx) in products" :key="product.cartId">
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
                    &nbsp;|&nbsp;
                    <v-badge  :value="product.promo != null" color="cyan lighten-3">
                        <template v-slot:badge>
                              <v-icon  @click.stop="promoAppliedDialog = idx">mdi-sale</v-icon>
                        </template>
                        <span>{{ product.price | currency }}&nbsp;</span>
                    </v-badge>
                    <v-spacer></v-spacer>
                    <v-badge color="error" overlap :content="badgeErrorCount[product.cartId]" :value="!!badgeErrorCount[product.cartId]">
                      <v-btn icon :to="{name:'editbadge', params: {cartId: product.cartId}}">
                          <v-icon>mdi-pencil</v-icon>
                      </v-btn>
                    </v-badge>
                    <v-btn icon @click.stop="removeBadge = idx">
                        <v-icon>mdi-delete</v-icon>
                    </v-btn>
                </v-card-actions>
                <v-card-actions v-for="addonid in filterAddons(product)" :key="addonid">
                  <v-icon>mdi-plus</v-icon>
                  {{getAddonByID(product.badge_type_id, addonid) ? getAddonByID(product.badge_type_id, addonid).name : "Loading..."}}
                  &nbsp;|&nbsp;
                  <span>{{ (getAddonByID(product.badge_type_id, addonid) ? getAddonByID(product.badge_type_id, addonid).price : "Loading" ) | currency }}&nbsp;</span>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>
    <v-row>
        <v-col>
            <v-btn color="primary" :to="{name:'addbadge', params: {cartId: -1}}" left absolute>Add
                {{products.length ? "another" : "a"}}
                badge</v-btn>
        </v-col>
    </v-row>
    <v-dialog v-model="promoAppliedDialogModal" width="500">
        <v-card>
            <v-card-title class="headline grey lighten-2" primary-title>
                Promo Code Applied
            </v-card-title>

            <v-card-text>
                {{promoAppliedDialogModal ? products[promoAppliedDialog].promoDescription : null}}
            </v-card-text>
            <v-card-text class="text--primary">A discount of
                <b>{{promoAppliedDialogModal ? (products[promoAppliedDialog].promoType == 1 ? "" : "$") : ""}}{{promoAppliedDialogModal ? products[promoAppliedDialog].promoPrice : ""}}{{promoAppliedDialogModal ? (products[promoAppliedDialog].promoType == 1 ? "%" : "") : ""}}</b>
                has been applied to the base price of
                <b>{{(promoAppliedDialogModal ? products[promoAppliedDialog].basePrice  : "") | currency}}</b>.</v-card-text>
            <v-divider></v-divider>

            <v-card-actions>
                <v-btn color="red" text @click="promoRemove">
                    Remove
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="promoAppliedDialog = -1">
                    Ok
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog v-model="promocodeDialog" width="500">
        <template v-slot:activator="{ on }">
            <v-row>
                <v-col>
                    <v-btn right absolute v-show="products.length" color="green" dark v-on="on">
                        Enter Promo Code
                    </v-btn>
                </v-col>
            </v-row>
        </template>

        <v-card>
            <v-card-title class="headline grey lighten-2" primary-title>
                Enter a Promo Code
            </v-card-title>

            <v-card-text>
                Only one promo code can be used on a badge. Also, editing a badge may remove the promo code from the badge; you will then need to enter the promo code again.
            </v-card-text>

            <v-divider></v-divider>

            <v-col cols="12">
                <v-text-field
                  label="Enter Code"
                  :readonly="promoCodeProcessing"
                  v-model="promocode"
                  :error-messages="promoCodeErrors"
                  ></v-text-field>
            </v-col>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="submitPromoCode" :loading="promoCodeProcessing">
                    Submit
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog v-model="removeBadgeModal" persistent max-width="290">
        <v-card>
            <v-card-title class="headline">Remove this badge?</v-card-title>
            <v-card-text>You have elected to remove
                <b>{{products[removeBadge] | badgeDisplayName }}</b>
                from the cart.<br/>Are you sure?</v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="default" @click="removeBadge = -1">Cancel</v-btn>
                <v-btn color="red darken-1" @click="confirmRemoveBadge">Remove</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog v-model="clearCartDialog" max-width="300">
        <v-card>
            <v-card-title class="headline">Clear cart?</v-card-title>

            <v-card-text>
                This will remove all items from the cart.
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>

                <v-btn color="primary" @click="clearCartDialog = false">
                    No
                </v-btn>

                <v-btn color="red darken-1" text @click="confirmClearCart">
                    Start Over
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <p v-show="checkoutStatus? checkoutStatus.errors : false">Can't checkout now, there are problems!</p>

    <v-dialog v-model="processingCheckoutDialog" persistent width="300">
        <v-card color="primary" dark>
            <v-card-text>
                {{orderSteps[cartState]}}
                <v-progress-linear indeterminate color="white" class="mb-0"></v-progress-linear>
            </v-card-text>
        </v-card>
    </v-dialog>

    <v-row>
      <v-col>
        <v-btn text x-large disabled block>
            <!-- Hack allocating space for the footer -->
        </v-btn>
      </v-col>
    </v-row>
    <v-footer fixed cols="12">
        <v-btn color="red" v-show="products.length" @click="clearCartDialog = true">
            <v-icon>mdi-bomb</v-icon>
        </v-btn>
        <v-spacer></v-spacer>
        <v-btn color="primary" :disabled="!products.length" @click="checkout(products)">Checkout:
            {{ total | currency }}
        </v-btn>

    </v-footer>
</v-container>
</template>


<script>
import { mapGetters, mapState, mapActions } from 'vuex'
export default {
  data: () => ({
    promocodeDialog: false,
    promocode: '',
    promoCodeProcessing: false,
    promoAppliedDialog: -1,
    promoCodeErrors : [],
    processingCheckoutDialog: false,
    removeBadge: -1,
    clearCartDialog: false,
    badgeErrorCount:[],
    orderSteps: {
      'undefined':'Processing order, please wait...',
      'ready':'Directing to Merchant...',
      'refused':'Paypal has refused the payment. That\'s all we know.'
    },
    cartState: 'undefined'
  }),
computed: {
  ...mapState({
    checkoutStatus: state => state.cart.checkoutStatus,
    addons: state => state.products.addons
  }),
  ...mapGetters('cart', {
    products: 'cartProducts',
    total: 'cartTotalPrice'
  }),
  removeBadgeModal: function() { return this.removeBadge > -1;},
  promoAppliedDialogModal: function() { return this.promoAppliedDialog > -1;}
},
methods: {
    ...mapActions('cart', [
    'applyPromoToProducts',
    'removePromoFromProduct',
    'removeProductFromCart',
    'clearCart'
  ]),
  updatedbadgeErrorCount: function() {

    //populate all the badges
    var result = [];
    this.promoCodeErrors = [];
    this.products.forEach(product => result[product.cartId +''] = 0);
    if(this.checkoutStatus)
    {
      if(this.checkoutStatus.errors)
      {
        Object.keys(this.checkoutStatus.errors).forEach( key => {
          result[key] = Object.keys(this.checkoutStatus.errors[key]).length;
        })
        if( this.checkoutStatus.errors['promo'] )
          this.promoCodeErrors = this.checkoutStatus.errors['promo'] ;
      }
    }
    this.badgeErrorCount = result;
    return result;
  },
  submitPromoCode: function() {
    this.promoCodeProcessing = true;
    this.applyPromoToProducts(this.promocode);
  },
  promoRemove: function() {
    this.removePromoFromProduct(this.products[this.promoAppliedDialog].cartId);
    this.promoAppliedDialog = -1;
  },
  confirmRemoveBadge: function() {
    this.removeProductFromCart(this.products[this.removeBadge]);
    this.removeBadge = -1;
  },
  confirmClearCart: function() {
    this.clearCart();
    this.clearCartDialog = false;
  },
  checkout (products) {
    this.processingCheckoutDialog = true;
    var _this = this;
    //Fancy delays
    setTimeout(function()
      {
        _this.$store.dispatch('cart/checkout', products);
      }, 1000);
  },
  filterAddons(product) {
    if(product.editBadgePriorAddons == undefined)
      return product.addonsSelected;
    return product.addonsSelected.filter(addon => !product.editBadgePriorAddons.includes(addon));
  },
  getAddonByID(badge_type_id, id) {
      if(undefined == this.addons[badge_type_id])
      return undefined;
    return this.addons[badge_type_id].find(addon => addon.id == id);
  }
},
watch :{
  'checkoutStatus': function(newstatus) {
    this.updatedbadgeErrorCount();
    this.cartState = newstatus ? newstatus.state : 'undefined';
    if(newstatus)
    switch(newstatus.state)
    {
      case 'ready':
      //Direct to the checkout.php
      var _this = this;
      setTimeout(function()
        {
          _this.processingCheckoutDialog = false;
        }, 15000);
        //TODO: This is a hack!
        window.location.href = global.config.apiHostURL + "cart.php?action=checkout";
        break;
      case 'promoapplied':

        this.promoCodeProcessing = false;
        this.promocodeDialog = false;
        break;
      case undefined:
        this.promoCodeProcessing = false;
        this.processingCheckoutDialog = false;
    }

  }
},
created () {
  var query = this.$route.query;
  if(query.result != undefined){
    if(query.result)
    {
      this.processingCheckoutDialog = true;
      this.cartState = query.result;
      //Un-pop the dialog after a few seconds
      var _this = this;
      setTimeout(function()
        {
          _this.processingCheckoutDialog = false;
        }, 6000);

    }
    this.$router.replace({...this.$router.currentRoute, query: {}})
  }
    this.$store.dispatch('products/getAllProducts')
    this.$store.dispatch('products/getAllAddons')
  }
}
</script>
