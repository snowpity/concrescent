<template>
<v-container class="pa-2" fluid>
    <h2>Your Cart</h2>
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
                    <v-badge :value="product.promoType != null" color="cyan lighten-3">
                        <template v-slot:badge>
                            <v-dialog v-model="promoAppliedDialog" width="500">
                                <template v-slot:activator="{ on }">
                                    <v-icon v-on="on">mdi-sale</v-icon>
                                </template>

                                <v-card>
                                    <v-card-title class="headline grey lighten-2" primary-title>
                                        Promo Code Applied
                                    </v-card-title>

                                    <v-card-text>
                                        {{product.promoDescription}}
                                    </v-card-text>
                                    <v-card-text class="text--primary">A discount of
                                        <b>{{product.promoType == 1 ? "" : "$"}}{{product.promoPrice}}{{product.promoType == 1 ? "%" : ""}}</b>
                                        has been applied to the base price of
                                        <b>{{product.basePrice | currency}}</b>.</v-card-text>
                                    <v-divider></v-divider>

                                    <v-card-actions>
                                        <v-btn color="red" text @click="promoAppliedDialog = false">
                                            Remove
                                        </v-btn>
                                        <v-spacer></v-spacer>
                                        <v-btn color="primary" @click="promoAppliedDialog = false">
                                            Ok
                                        </v-btn>
                                    </v-card-actions>
                                </v-card>
                            </v-dialog>

                        </template>
                        <span>{{ product.price | currency }}&nbsp;</span>
                    </v-badge>
                    <v-spacer></v-spacer>
                    <v-btn icon :to="{name:'editbadge', params: {cartId: product.cartId}}">
                        <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                    <v-btn icon @click.stop="removeBadge = idx">
                        <v-icon>mdi-delete</v-icon>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>
    <v-row>
        <v-col>
            <v-btn color="primary" to="/addbadge" left absolute>Add
                {{products.length ? "another" : "a"}}
                badge</v-btn>
        </v-col>
    </v-row>
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
                <v-text-field label="Enter Code"></v-text-field>
            </v-col>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="promocodeDialog = false">
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
    <p v-show="checkoutStatus">Checkout status:
        {{ checkoutStatus }}.</p>

    <v-dialog v-model="processingCheckoutDialog" hide-overlay persistent width="300">
        <v-card color="primary" dark>
            <v-card-text>
                Processing order, please wait...
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
    promoAppliedDialog: false,
    processingCheckoutDialog: false,
    removeBadge: -1,
    clearCartDialog: false,
  }),
computed: {
  ...mapState({
    checkoutStatus: state => state.cart.checkoutStatus
  }),
  ...mapGetters('cart', {
    products: 'cartProducts',
    total: 'cartTotalPrice'
  }),
  removeBadgeModal: function() { return this.removeBadge > -1;}
},
methods: {
    ...mapActions('cart', [
    'applyPromoToProduct',
    'removeProductFromCart',
    'clearCart'
  ]),
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
    this.$store.dispatch('cart/checkout', products)
  }
}
}
</script>
