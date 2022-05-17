<template>
<v-container fluid>
    <v-row>
        <p>
            <i v-show="!products.length">Cart is empty.</i>
        </p>
    </v-row>
    <v-row>
        <v-col cols="12"
               md="6"
               lg="4"
               xl="3"
               v-for="(product, idx) in products"
               :key="product.cartIx">
            <v-card>
                <badgeSampleRender :badge="product" />
                <v-card-actions>
                    <div class="text-truncate">{{product.name}}</div>
                    &nbsp;|&nbsp;
                    <v-badge :value="product.payment_promo_code"
                             color="cyan lighten-3">
                        <template v-slot:badge>
                            <v-icon @click.stop="promoAppliedDialog = idx">mdi-sale</v-icon>
                        </template>
                        <span>{{ product.price | currency }}&nbsp;</span>
                    </v-badge>
                    <v-spacer></v-spacer>
                    <v-badge color="error"
                             overlap
                             :content="badgeErrorCount[product.cartIx]"
                             :value="!!badgeErrorCount[product.cartIx]">
                        <v-btn icon
                               :disabled="disableModifyCart"
                               :to="{name:'editbadge', params: {cartIx: idx}}">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                    </v-badge>
                    <v-btn icon
                           :disabled="disableModifyCart"
                           @click.stop="removeBadge = idx">
                        <v-icon>mdi-delete</v-icon>
                    </v-btn>
                </v-card-actions>
                <v-card-actions v-for="addonid in filterAddons(product)"
                                :key="addonid['addon_id']">
                    <v-icon>mdi-plus</v-icon>
                    <div class="text-truncate">
                        {{getAddonByID(product.context_code, product.badge_type_id, addonid['addon_id']) ? getAddonByID(product.context_code, product.badge_type_id, addonid['addon_id']).name : "Loading..."}}
                    </div>&nbsp;|&nbsp;
                    <span>{{ (getAddonByID(product.context_code, product.badge_type_id, addonid['addon_id']) ? getAddonByID(product.context_code, product.badge_type_id, addonid['addon_id']).price : "Loading" ) | currency }}&nbsp;</span>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>
    <v-row>
        <v-col :cols="2">
            <v-btn color="primary"
                   :to="{name:'addbadge', params: {cartIx: -1}}"
                   left
                   :disabled="disableModifyCart"
                   absolute>Add
                {{products.length ? "another" : "a"}}
                badge
            </v-btn>
        </v-col>

        <v-col :cols="2">
            <v-btn right
                   absolute
                   v-show="products.length"
                   :disabled="disableModifyCart"
                   color="green"
                   dark
                   @click="promocodeDialog = true">
                Enter Promo Code
            </v-btn>
        </v-col>
    </v-row>
    <v-dialog v-model="promoAppliedDialogModal"
              width="500">
        <v-card>
            <v-card-title class="headline grey lighten-2"
                          primary-title>
                Promo Code Applied
            </v-card-title>

            <v-card-text>
                {{promoAppliedDialogModal ? products[promoAppliedDialog].promoDescription : null}}
            </v-card-text>
            <v-card-text class="text--primary">A discount of
                <b>{{promoAppliedDialogModal ? (products[promoAppliedDialog].payment_promo_type == 1 ? "" : "$") : ""}}{{promoAppliedDialogModal ? products[promoAppliedDialog].payment_promo_amount : ""}}{{promoAppliedDialogModal ? (products[promoAppliedDialog].payment_promo_type == 1 ? "%" : "") : ""}}</b>
                has been applied to the base price of
                <b>{{(promoAppliedDialogModal ? products[promoAppliedDialog].payment_badge_price  : "") | currency}}</b>.
            </v-card-text>
            <v-divider></v-divider>

            <v-card-actions>
                <v-btn color="red"
                       text
                       @click="promoRemove">
                    Remove
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn color="primary"
                       @click="promoAppliedDialog = -1">
                    Ok
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog v-model="promocodeDialog"
              width="500">

        <v-card>
            <v-card-title class="headline grey lighten-2"
                          primary-title>
                Enter a Promo Code
            </v-card-title>

            <v-card-text>
                Only one promo code can be used on a badge. Also, editing a badge may remove the promo code from the badge; you will then need to enter the promo code again.
            </v-card-text>

            <v-divider></v-divider>

            <v-col cols="12">
                <v-text-field label="Enter Code"
                              :readonly="promoCodeProcessing"
                              v-model="promocode"
                              :error-messages="promoCodeErrors"></v-text-field>
            </v-col>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary"
                       @click="submitPromoCode"
                       :loading="promoCodeProcessing">
                    Submit
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog v-model="removeBadgeModal"
              persistent
              max-width="290">
        <v-card>
            <v-card-title class="headline">Remove this badge?</v-card-title>
            <v-card-text>You have elected to remove
                <b>{{products[removeBadge] | badgeDisplayName }}</b>
                from the cart.<br />Are you sure?
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="default"
                       @click="removeBadge = -1">Cancel</v-btn>
                <v-btn color="red darken-1"
                       @click="confirmRemoveBadge">Remove</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog v-model="clearCartDialog"
              max-width="300">
        <v-card>
            <v-card-title class="headline">Clear cart?</v-card-title>

            <v-card-text>
                This will remove all items from the cart.
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>

                <v-btn color="primary"
                       @click="clearCartDialog = false">
                    No
                </v-btn>

                <v-btn color="red darken-1"
                       text
                       @click="confirmClearCart">
                    Start Over
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <p v-show="itemsHaveErrors">Can't checkout now, there are problems!</p>

    <v-dialog v-model="processingCheckoutDialog"
              persistent
              width="300">
        <v-card color="primary"
                dark>
            <v-card-text>
                {{orderSteps[cartState] || "Processing..."}}
                <v-progress-linear indeterminate
                                   color="white"
                                   class="mb-0"></v-progress-linear>
            </v-card-text>
        </v-card>
    </v-dialog>
    <v-dialog max-width="600"
              v-model="AwaitingApprovalDialog">
        <v-card>
            <v-toolbar color="primary"
                       dark>
                <h1>Application Submitted!</h1>
            </v-toolbar>
            <v-card-text>
                <v-card-text>Application has been received. Thanks!</v-card-text>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="primary"
                       @click="AwaitingApprovalDialog = false">Ok</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog transition="dialog-top-transition"
              max-width="600"
              v-model="isCartLocked">
        <v-card>
            <v-toolbar color="error"
                       dark>
                <h1>Cart locked error</h1>
            </v-toolbar>
            <v-card-text>
                <div class="text-h5 pa-4">{{cartLocked}}</div>
            </v-card-text>
            <v-card-actions>
                <v-btn color="red"
                       v-show="products.length"
                       @click="confirmClearCart">Reset cart?</v-btn>
                <v-spacer />
                <v-btn color="primary"
                       @click="checkout(products)">Retry Checkout</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog v-model="createAccountDialog"
              transition="dialog-bottom-transition">
        <v-card>
            <v-toolbar color="primary"
                       dark>Supply Contact Information</v-toolbar>
            <v-card-text>
                <profileForm v-model="newAccountData" />
            </v-card-text>
            <v-card-actions class="justify-end">
                <v-btn color="primary"
                       :disabled="creatingAccount"
                       :loading="creatingAccount"
                       @click="createAccount">Save</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog transition="dialog-top-transition"
              max-width="600"
              v-model="isCreateError">
        <v-card>
            <v-toolbar color="error"
                       dark>
                <h1>Profile creation error</h1>
            </v-toolbar>
            <v-card-text>
                <div class="text-h5 pa-4">{{createError}}</div>
            </v-card-text>
            <v-card-actions>
                <v-btn color="green"
                       :disabled="sendingmagicemail"
                       :loading="sendingmagicemail"
                       @click="SendMagicLink">Send magic link?</v-btn>
                <v-spacer />
                <v-btn color="primary"
                       @click="createError = ''">Try again</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog max-width="600"
              v-model="sentmagicmail">
        <v-card>
            <v-toolbar color="primary"
                       dark>
                <h1>Magic link sent</h1>
            </v-toolbar>
            <v-card-text>
                <v-card-text>If you have purchased any badges with the contact email <b>{{newAccountData.email_address}}</b>, you should receive the badge retrieval email shortly to confirm.</v-card-text>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="primary"
                       @click="closeerror">Ok</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-row>
        <v-col>
            <v-btn text
                   x-large
                   disabled
                   block>
                <!-- Hack allocating space for the footer -->
            </v-btn>
        </v-col>
    </v-row>
    <v-footer fixed
              cols="12">
        <v-btn color="red"
               v-show="cartIdSelected"
               @click="clearCartDialog = true">
            <v-icon>mdi-bomb</v-icon>
        </v-btn>
        <v-spacer></v-spacer>

        <v-select :items="activeCarts"
                  v-model="cartIdSelected"
                  dense
                  solo
                  full-width
                  hide-details
                  label="Active Carts"
                  item-value="id">
            <template v-slot:selection="{ item }">
                {{item.id}} -> {{item.requested_by}} Saved {{new Date(item.date_modified).toLocaleString()}}
                <v-spacer></v-spacer>
                <v-chip :color="cartStateColor[item.payment_status]">{{ item.payment_status }}</v-chip>
            </template>
            <template v-slot:prepend-item>
                <v-list-item ripple
                             @click="createNew">
                    <v-list-item-content>
                        <v-list-item-title>
                            <v-row no-gutters
                                   align="center">Create New
                            </v-row>
                        </v-list-item-title>
                    </v-list-item-content>
                </v-list-item>
                <v-divider class="mt-2"></v-divider>
            </template>
            <template v-slot:item="{  item, attrs, on }">
                <v-list-item v-on="on"
                             v-bind="attrs">
                    <v-list-item-content>
                        <v-list-item-title>
                            <v-row no-gutters
                                   align="center">{{item.id}} ->
                                <span>Requestor: {{ item.requested_by }}</span>
                                <span class="pull-right">Saved: {{new Date(item.date_modified).toLocaleString()}}</span>
                                <v-spacer></v-spacer>
                                <v-chip :color="cartStateColor[item.payment_status]">{{ item.payment_status }}</v-chip>
                            </v-row>
                        </v-list-item-title>
                    </v-list-item-content>
                </v-list-item>
            </template>
        </v-select>
        <v-spacer></v-spacer>

        <v-btn color="primary"
               class="float-right"
               v-if="isLoggedIn"
               :disabled="canNotCheckout || disableModifyCart"
               @click="checkout(products)">
            <div v-if="canPay">
                Checkout:
                {{ total | currency }}
            </div>
            <div v-else>
                Submit
            </div>
        </v-btn>
        <v-btn v-else
               color="primary"
               class="float-right"
               @click="createAccountDialog = true">Specify Contact info</v-btn>

    </v-footer>
</v-container>
</template>


<script>
import {
    mapGetters,
    mapState,
    mapActions
} from 'vuex'
import badgeSampleRender from '@/components/badgeSampleRender.vue';
import profileForm from '@/components/profileForm.vue';
export default {
    components: {
        badgeSampleRender,
        profileForm,
    },
    data: () => ({
        createAccountDialog: false,
        newAccountData: {},
        creatingAccount: false,
        createError: "",
        sendingmagicemail: false,
        sentmagicmail: false,
        promocodeDialog: false,
        promocode: '',
        promoCodeProcessing: false,
        promoAppliedDialog: -1,
        promoCodeErrors: [],
        processingCheckoutDialog: false,
        AwaitingApprovalDialog: false,
        removeBadge: -1,
        clearCartDialog: false,
        badgeErrorCount: [],
        orderSteps: {
            'undefined': 'Processing order, please wait...',
            'ready': 'Directing to Merchant...',
            'AwaitingApproval': 'Confirming submission',
            'refused': 'Paypal has refused the payment. That\'s all we know.',
            'confirm': 'Confirming payment...'
        },
        cartStateColor: {
            'NotReady': 'purple',
            'AwaitingApproval': 'yellow',
            'NotStarted': 'gray',
            'Incomplete': 'lime',
            'Cancelled': 'red',
            'Rejected': 'red',
            'Completed': 'green',
            'Refunded': 'indigo',
            'RefundedInPart': 'indigo'
        },
        cartIdSelected: 0,
        cartState: 'undefined',
        cartLocked: ''
    }),
    computed: {
        ...mapState({
            checkoutStatus: state => state.cart.checkoutStatus,
            addons: state => state.products.addons,
            activeCarts: state => state.mydata.activeCarts,
            currentCartId: state => state.cart.cartId,
        }),
        ...mapGetters('mydata', {
            'isLoggedIn': 'getIsLoggedIn',
        }),
        ...mapGetters('cart', {
            products: 'cartProducts',
            total: 'cartTotalPrice',
            needsave: 'isDirty',
            canPayCart: 'canPay',
        }),
        removeBadgeModal: function() {
            return this.removeBadge > -1;
        },
        promoAppliedDialogModal: function() {
            return this.promoAppliedDialog > -1;
        },
        itemsHaveErrors: function() {
            if (this.checkoutStatus == null)
                return false;
            if (typeof this.checkoutStatus.errors == "undefined" || typeof this.checkoutStatus.errors == "object")
                return false;
            return this.checkoutStatus.errors.reduce((result, currentItem) => result | currentItem.length > 0, false);
        },
        canNotCheckout: function() {
            return this.itemsHaveErrors || this.products.length == 0;
        },
        disableModifyCart: function() {
            if (this.checkoutStatus != null)
                switch (this.checkoutStatus.state) {
                    case 'AwaitingApproval':
                    case 'Cancelled':
                    case 'Rejected':
                    case 'Completed':
                    case 'Refunded':
                    case 'RefundedInPart':
                        return true;
                }
            return false;
        },
        canPay: function() {
            return this.canPayCart;
        },
        isCreateError: {
            get() {
                return this.createError.length > 0;
            },
            set(newval) {
                this.createError = newval ? "???" : "";
            }
        },
        isCartLocked: {
            get() {
                return this.cartLocked.length > 0;
            },
            set(newval) {
                this.cartLocked = newval ? "???" : "";
            }
        },
    },
    methods: {
        ...mapActions('mydata', {
            'submitCreateAccount': 'createAccount',
            'sendRetrieveBadgeEmail': 'sendRetrieveBadgeEmail',
        }),
        ...mapActions('cart', [
            'removePromoFromProduct',
            'removeProductFromCart',
            'loadCart',
            'saveCart',
            'checkoutCart',
            'clearCart'
        ]),
        updatedbadgeErrorCount: function() {

            //populate all the badges
            var result = [];
            this.promoCodeErrors = [];
            this.products.forEach(product => result[product.cartIx + ''] = 0);
            if (this.checkoutStatus) {
                if (this.checkoutStatus.errors) {
                    Object.keys(this.checkoutStatus.errors).forEach(key => {
                        result[key] = Object.keys(this.checkoutStatus.errors[key]).length;
                    })
                    if (this.checkoutStatus.errors['promo'])
                        this.promoCodeErrors = this.checkoutStatus.errors['promo'];
                }
            }
            this.badgeErrorCount = result;
            return result;
        },
        createAccount: function() {
            this.creatingAccount = true;
            this.submitCreateAccount(this.newAccountData).then((token) => {
                this.creatingAccount = false;
                this.createAccountDialog = false;
                this.saveCart();
            }).catch((error) => {
                this.createError = error.error.message;
                this.creatingAccount = false;
            })
        },
        SendMagicLink() {
            this.sendingmagicemail = true;
            this.sendRetrieveBadgeEmail(this.newAccountData.email_address).then(() => {
                this.sentmagicmail = true;
                this.sendingmagicemail = false;
            });
        },
        submitPromoCode: function() {
            this.promoCodeProcessing = true;
            this.saveCart(this.promocode);
        },
        promoRemove: function() {
            this.removePromoFromProduct(this.promoAppliedDialog);
            this.promoAppliedDialog = -1;
        },
        confirmRemoveBadge: function() {
            this.removeProductFromCart(this.removeBadge);
            this.removeBadge = -1;
            this.saveCart();
        },
        createNew: async function() {
            this.$store.commit('cart/setcartId', null);
            await this.clearCart();
            this.cartIdSelected = null;
            document.activeElement.blur();
        },
        confirmClearCart: function() {
            this.clearCart().then(() => {

                this.clearCartDialog = false;
                this.cartLocked = "";
                this.$store.dispatch('mydata/fetchCarts', false).then((carts) => {
                    if (carts.length > 0)
                        this.cartIdSelected = carts[carts.length - 1].id;
                    else
                        this.cartIdSelected = 0;
                })
            })
        },
        checkout(products) {
            this.processingCheckoutDialog = true;
            var _this = this;
            //Fancy delays
            setTimeout(function() {
                _this.checkoutCart();
            }, 1000);
        },
        closeerror: function() {
            this.createError = "";
            this.sentmagicmail = false;
            this.creatingAccount = false;
        },
        filterAddons(product) {
            if (product.addons == undefined) return [];
            if (product.editBadgePriorAddons == undefined)
                return product.addons;
            return product.addons.filter(addon => !product.editBadgePriorAddons.includes(addon['addon_id']));
        },
        getAddonByID(context_code, badge_type_id, id) {
            if (undefined == this.addons[context_code][badge_type_id])
                return undefined;
            return this.addons[context_code][badge_type_id].find(addon => addon.id == id);
        }
    },
    watch: {
        'checkoutStatus': function(newstatus) {
            this.updatedbadgeErrorCount();
            this.cartState = newstatus ? newstatus.state : 'undefined';
            if (newstatus)
                switch (newstatus.state) {
                    case 'Incomplete':
                        //Direct to the checkout URL
                        var _this = this;
                        setTimeout(function() {
                            _this.processingCheckoutDialog = false;
                        }, 15000);
                        //TODO: This is a hack!
                        if (newstatus.paymentURL != undefined)
                            window.location.href = newstatus.paymentURL;
                        break;
                    case 'AwaitingApproval':
                        var _this = this;
                        setTimeout(function() {
                            if (_this.processingCheckoutDialog) {
                                _this.processingCheckoutDialog = false;
                                _this.AwaitingApprovalDialog = true;
                            }
                        }, 1500);
                        break;
                    case 'Completed':
                        //Clear the cart and send them to retrieve their badges
                        this.clearCart();
                        this.$router.push({
                            path: '/myBadges',
                            query: {
                                refresh: true
                            }
                        });
                        this.processingCheckoutDialog = false;
                        break;
                    default:
                        this.promocode = "";
                        this.promoCodeProcessing = false;
                        this.promocodeDialog = false;
                        this.processingCheckoutDialog = false;
                }
            //Always refresh the cart list
            this.$store.dispatch('mydata/fetchCarts', false)
        },
        'cartIdSelected': function(newId) {
            console.log('load cart ' + newId);
            (this.dirty ?
                this.saveCart() : Promise.resolve()).then(() => {
                if (newId)
                    this.loadCart(newId);
            })

        }
    },
    created() {
        var query = this.$route.query;
        if (query.checkout != undefined) {
            if (query.checkout == "confirm") {
                this.processingCheckoutDialog = true;
                this.cartState = query.checkout;
                //Un-pop the dialog after a few seconds
                var _this = this;
                setTimeout(function() {
                    _this.checkoutCart();
                }, 2000);

            }
            this.$router.replace({
                ...this.$router.currentRoute,
                query: {}
            })
        }
        this.$store.dispatch('mydata/fetchCarts', false).then(() => {
            this.cartIdSelected = this.currentCartId;
        })
        if (this.needsave) {
            this.saveCart()
                .then((cartId) => {
                    this.cartIdSelected = cartId;
                })
                .catch((error) => {
                    this.cartLocked = error.error.message;
                });
        }

        this.updatedbadgeErrorCount();
    }
}
</script>
