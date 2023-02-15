<template>
<v-container fluid>
    <v-expansion-panels v-model="cartsExpanded"
                        focusable
                        multiple>
        <v-expansion-panel v-for="cart in activeCarts"
                           :key="cart.id">
            <v-expansion-panel-header dense>
                <v-list-item-content>
                    <v-list-item-title>
                        {{cart.id}} ->
                        <span>Requestor: {{ cart.requested_by }}</span>
                    </v-list-item-title>
                    <v-list-item-subtitle>Saved: {{new Date(cart.date_modified).toLocaleString()}}</v-list-item-subtitle>
                </v-list-item-content>

                <template v-slot:actions>

                    <v-sheet rounded="lg"
                             class="pa-3"
                             elevation="4"
                             :color="cartStateColor[cart.state]">{{ cartStateTranslation(cart.state) }}</v-sheet>

                    <v-icon color="primary">
                        $expand
                    </v-icon>
                </template>
            </v-expansion-panel-header>
            <v-expansion-panel-content v-if="cart">&nbsp;
                <v-container fluid>
                    <v-row>
                        <v-col cols="12"
                               md="6"
                               lg="4"
                               xl="3"
                               v-for="(product, idx) in cart.items"
                               :key="product.cartIx">
                            <v-card>
                                <badgeSampleRender :badge="product" />
                                <v-card-actions>
                                    <div class="text-truncate">{{product.badge_type_name}}</div>
                                    &nbsp;|&nbsp;
                                    <v-badge :value="product.payment_promo_code != undefined && product.payment_promo_code.length > 0"
                                             color="cyan lighten-3">
                                        <template v-slot:badge>
                                            <v-icon @click.stop="promoAppliedDialogData = product">mdi-sale</v-icon>
                                        </template>
                                        <span>{{ product.payment_promo_price | currency }}&nbsp;</span>
                                    </v-badge>
                                    <v-spacer></v-spacer>
                                    <v-badge color="error"
                                             overlap
                                             :content="badgeErrorCount(cart,idx)"
                                             :value="!!badgeErrorCount(cart,idx)">
                                        <v-btn icon
                                               :disabled="!cart.canEdit"
                                               @click.stop="editBadge(cart.id, idx)">
                                            <v-icon>mdi-pencil</v-icon>
                                        </v-btn>
                                    </v-badge>
                                    <v-btn icon
                                           :disabled="!cart.canEdit"
                                           @click.stop="startRemoveBadge(cart.id, idx)">
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
                                <v-card-actions v-for="(subbadge,ix) in product.subbadges"
                                                :key="ix">
                                    <v-icon>mdi-account</v-icon>
                                    <div class="text-truncate">
                                        {{subbadge | badgeDisplayName(false)}}
                                    </div>&nbsp;|&nbsp;
                                    <span>{{ (subbadge.payment_price ? subbadge.payment_price: "Loading" ) | currency }}&nbsp;</span>

                                </v-card-actions>
                            </v-card>
                        </v-col>
                        <v-col cols="12"
                               md="6"
                               lg="4"
                               xl="3"
                               v-if="!cart.RequiresApproval && cart.canEdit">
                            <v-card class="fill-height ma-0"
                                    justify="center">
                                <v-btn block
                                       color="primary"
                                       rounded
                                       @click="addBadge(cart.id)"
                                       align="center">Add
                                    {{cart.items.length ? "another" : "a"}} badge
                                </v-btn>
                            </v-card>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="3"
                               sm="2">
                            <v-btn color="red"
                                   @click="showclearCartDialog(cart.id)">
                                <v-icon>mdi-bomb</v-icon>
                            </v-btn>
                        </v-col>

                        <v-col cols="3"
                               sm="2">
                            <v-btn v-show="products.length"
                                   :disabled="!cart.canEdit"
                                   color="green"
                                   dark
                                   @click="showpromocodeDialog(cart.id)">
                                <v-icon v-if="$vuetify.breakpoint.xs">mdi-sale</v-icon>
                                <div v-else>
                                    Enter Promo Code
                                </div>
                            </v-btn>
                        </v-col>

                        <v-col cols="2"
                               sm="3">&nbsp;

                            <v-btn color="primary"
                                   right
                                   absolute
                                   v-if="isLoggedIn"
                                   :disabled="!cart.canCheckout"
                                   @click="checkout(cart.id)">
                                <div v-if="!cart.RequiresApproval || cart.canPay">
                                    <v-icon>mdi-credit-card-outline</v-icon>
                                    {{ cart.payment_txn_amt | currency }}
                                </div>
                                <div v-else>
                                    <v-icon v-if="$vuetify.breakpoint.xs">mdi-send</v-icon>
                                    <div v-else>
                                        Submit
                                    </div>

                                </div>
                            </v-btn>
                            <v-btn v-else
                                   color="primary"
                                   right
                                   absolute
                                   @click="createAccountDialog = true">Specify Contact info</v-btn>
                        </v-col>
                    </v-row>
                    <p v-show="itemsHaveErrors">Can't checkout now, there are problems!</p>
                </v-container>
            </v-expansion-panel-content>
        </v-expansion-panel>
    </v-expansion-panels>
    <v-dialog v-model="promoAppliedDialog"
              @click:outside="closepromo"
              width="500">
        <v-card>
            <v-card-title class="headline grey lighten-2"
                          primary-title>
                Promo Code Applied
            </v-card-title>

            <v-card-text class="text--primary">
                {{promoAppliedDialogData ? promoAppliedDialogData.payment_promo_description : null}}
            </v-card-text>
            <v-card-text class="text--primary">A discount of
                <b>{{promoAppliedDialogData ? (promoAppliedDialogData.payment_promo_type == 1 ? "" : "$") : ""}}{{promoAppliedDialogData ? promoAppliedDialogData.payment_promo_amount : ""}}{{promoAppliedDialogData ? (promoAppliedDialogData.payment_promo_type == 1 ? "%" : "") : ""}}</b>
                has been applied to the base price of
                <b>{{(promoAppliedDialogData ? promoAppliedDialogData.payment_badge_price  : "") | currency}}</b>.
            </v-card-text>
            <v-divider></v-divider>

            <v-card-actions>
                <v-btn color="red"
                       text
                       :disabled="!selectedCart.canEdit"
                       @click="promoRemove">
                    Remove
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn color="primary"
                       @click="promoAppliedDialog = false">
                    Ok
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog v-model="promocodeDialog"
              width="500">
        <v-form @submit.prevent="submitPromoCode">
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
                           type="submit"
                           :loading="promoCodeProcessing">
                        Submit
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
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
    <v-dialog v-model="AwaitingApprovalDialog"
              max-width="600">
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

    <v-dialog v-model="isCartLocked"
              transition="dialog-top-transition"
              max-width="600">
        <v-card>
            <v-toolbar color="error"
                       dark>
                <h1>Cart locked error</h1>
            </v-toolbar>
            <v-card-text>
                <div class="text-h5 pa-4">{{cartLocked}}</div>
            </v-card-text>
            <v-card-actions v-if="isLoggedIn">
                <v-btn color="red"
                       v-show="products.length"
                       @click="confirmClearCart">Reset cart?</v-btn>
                <v-spacer />
                <v-btn color="primary"
                       @click="checkout(products)">Retry Checkout</v-btn>
            </v-card-actions>
            <v-card-actions v-else>
                <v-spacer />
                <v-btn color="primary"
                       @click="cartLocked=''">Close</v-btn>
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

    <v-dialog v-model="isCreateError"
              transition="dialog-top-transition"
              max-width="600">
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
    <v-dialog v-model="sentmagicmail"
              max-width="600">
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
    <v-container fluid>
        <v-row>
            <v-col>
                <v-btn color="primary"
                       @click="addBadge(null)"
                       left
                       rounded
                       absolute>Add a badge</v-btn>
            </v-col>
            <v-spacer></v-spacer>
        </v-row>

    </v-container>

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
        cartsExpanded: [],
        createAccountDialog: false,
        newAccountData: {},
        creatingAccount: false,
        createError: "",
        sendingmagicemail: false,
        sentmagicmail: false,
        promocodeDialog: false,
        promocode: '',
        promoCodeProcessing: false,
        promoAppliedDialog: false,
        promoAppliedDialogData: {},
        promoCodeErrors: [],
        processingCheckoutDialog: false,
        AwaitingApprovalDialog: false,
        removeBadge: -1,
        clearCartDialog: false,
        //badgeErrorCount: [],
        orderSteps: {
            'undefined': 'Processing order, please wait...',
            'ready': 'Directing to Merchant...',
            'AwaitingApproval': 'Confirming submission',
            'refused': 'Payment has been refused. That\'s all we know.',
            'confirm': 'Confirming payment...',
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
        selectedCart: function() {
            if (this.activeCarts)
                return this.activeCarts.find(cart => cart.id == this.cartIdSelected) || {
                    canEdit: false,
                    canPay: false,

                };
            return {
                canEdit: false,
                canPay: false,

            };
        },
        removeBadgeModal: function() {
            return this.removeBadge > -1;
        },
        itemsHaveErrors: function() {
            if (this.checkoutStatus == null)
                return false;
            if (typeof this.checkoutStatus.errors == "undefined" || typeof this.checkoutStatus.errors == "object")
                return false;
            return this.checkoutStatus.errors.reduce((result, currentItem) => result | currentItem.length > 0, false);
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
        ...mapActions('products', {
            'selectContext': 'selectContext',
        }),
        ...mapActions('cart', [
            'removePromoFromProduct',
            'removeProductFromCart',
            'loadCart',
            'saveCart',
            'switchCart',
            'checkoutCart',
            'checkoutCartByUUID',
            'clearCart'
        ]),
        badgeErrorCount: function(cart, ix) {
            if (cart == undefined) return 0;
            if (ix == undefined) return 0;
            if (cart.errors == undefined) return 0;
            if (cart.errors.length < ix) return 0;
            return Object.keys(cart.errors[ix]).length;
        },
        cartStateTranslation: function(state) {
            return ({
                'NotReady': 'Not ready',
                'AwaitingApproval': 'Waiting for approval',
                'NotStarted': 'Ready to continue',
                'Incomplete': 'Waiting for payment',
                'Cancelled': 'Cancelled',
                'Rejected': 'Rejected',
                'Completed': 'Completed',
                'Refunded': 'Refunded',
                'RefundedInPart': 'Partially refunded'
            })[state] || state;
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
        showpromocodeDialog: function(cartid) {
            this.promocodeDialog = true;
            this.cartIdSelected = cartid;
        },
        submitPromoCode: function() {
            this.promoCodeProcessing = true;
            this.saveCart(this.promocode);
        },
        promoRemove: function() {
            this.removePromoFromProduct(this.promoAppliedDialogData);
            this.saveCart();
            this.promoAppliedDialog = false;
        },
        addBadge: async function(cartId) {
            this.loadCart(cartId);

            this.$router.push({
                path: '/addbadge',
                params: {
                    cartIx: -1
                }
            });
        },
        editBadge: async function(cartId, badgeix) {
            await this.loadCart(cartId);
            this.cartIdSelected = cartId;
            console.log("badgeix", badgeix);
            this.$router.push({
                name: 'addbadge',
                params: {
                    cartIx: badgeix
                }
            });
        },
        async startRemoveBadge(cartId, badgeix) {
            await this.loadCart(cartId);
            this.cartIdSelected = cartId;
            this.removeBadge = badgeix;
        },
        confirmRemoveBadge: function() {
            this.removeProductFromCart(this.removeBadge);
            this.removeBadge = -1;
            this.saveCart();
        },
        showclearCartDialog: function(cartid) {
            this.clearCartDialog = true;
            this.cartIdSelected = cartid;
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
        async checkout(cartid) {
            console.log('aerhaerg?', this)
            this.processingCheckoutDialog = true;
            if (cartid != undefined)
                await this.loadCart(cartid);
            this.processingCheckoutDialog = true;
            //Fancy delays
            await new Promise(resolve => setTimeout(resolve, 3000));
            this.checkoutCart();
        },
        closepromo: function() {
            this.promoAppliedDialog = false;
            this.promocodeDialog = false;
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
            var result = {
                "id": 0,
                "display_order": 0,
                "name": "[Loading...]",
                "description": "Loading description, please wait",
                "rewards": null,
                "price": "",
                "payable_onsite": 0,
                "quantity": null,
                "start_date": null,
                "end_date": null,
                "min_age": null,
                "max_age": null,
                "dates_available": "forever to forever",
                "quantity_sold": 0,
                "quantity_remaining": null
            }
            if (undefined == this.addons[context_code])
                return result;
            if (undefined == this.addons[context_code][badge_type_id])
                return result;
            return this.addons[context_code][badge_type_id].find(addon => addon.id == id) || result;
        }
    },
    watch: {
        'checkoutStatus': function(newstatus) {
            this.cartState = newstatus ? newstatus.state : 'undefined';
            if (newstatus) {
                switch (newstatus.state) {
                    case 'Incomplete':
                        //Direct to the checkout URL
                        var _this = this;
                        setTimeout(function() {
                            _this.processingCheckoutDialog = false;
                        }, 15000);
                        //TODO: This is a hack!
                        if (newstatus.paymentURL != undefined) {
                            console.log('Will redirect to', newstatus.paymentURL);
                            window.location.href = newstatus.paymentURL;
                        } else if (!this.isLoggedIn) {
                            //Not able to complete and no redirect. Display message?
                            this.cartLocked = 'Still awaiting payment.'
                            this.processingCheckoutDialog = false;
                        }
                        break;
                    case 'AwaitingApproval':
                        var _this = this;
                        setTimeout(function() {
                            if (_this.processingCheckoutDialog) {
                                _this.processingCheckoutDialog = false;
                                _this.AwaitingApprovalDialog = true;
                            }
                            if (_this.isLoggedIn)
                                _this.$store.dispatch('mydata/fetchCarts', false)
                        }, 1500);
                        break;
                    case 'Completed':
                        //Determine if this was a normal Attendee or a group application
                        var path = (this.products[0].context_code == 'A' || this.products[0].context_code == 'S') ?
                            '/myBadges' : '/myApplications';

                        //Clear the cart and send them to retrieve their badges
                        this.loadCart(null);
                        this.$router.push({
                            path: path,
                            query: {
                                refresh: true
                            }
                        });
                        this.processingCheckoutDialog = false;
                        break;
                    default:
                        if (this.promoCodeProcessing) {

                            this.promoCodeProcessing = false;
                            this.promocode = "";
                            this.promocodeDialog = false;
                            this.processingCheckoutDialog = false;
                        }
                }
                //Always refresh the cart list if we're logged in
                // if (this.isLoggedIn)
                //     this.$store.dispatch('mydata/fetchCarts', false);
            }
        },
        'cartIdSelected': async function(newId) {
            console.log('showing cart because selected', this.cartIdSelected);
            //Find the cart index associated with the ID and ensure it's expanded
            var cartIx = this.activeCarts.findIndex(cart => cart.id == newId);
            if (cartIx != undefined) {
                if (this.cartsExpanded.find(x => x == cartIx) == undefined)
                    this.cartsExpanded.push(cartIx);
            }
            await this.loadCart(newId);
        },
        'cartsExpanded': async function(newcarts) {

            //Determine all the contexts and then ask for them to be loaded
            for (var cartIx of newcarts) {
                if (this.activeCarts[cartIx] != undefined) {
                    for (var item of this.activeCarts[cartIx].items) {
                        await this.selectContext(item.context_code);
                    };
                }
            };

        },
        'promoAppliedDialogData': function(newData) {
            this.promoAppliedDialog = newData != null;
        }
    },
    async created() {
        var query = this.$route.query;
        var attemptCheckout = query.checkout != undefined && query.checkout == "confirm";
        var cart_id = query.id;
        var cart_uuid = query.cart_uuid;

        if (this.isLoggedIn) {

            await this.$store.dispatch('mydata/fetchCarts', false);
            var wantedCart = this.activeCarts.find(cart => cart.uuid == cart_uuid || cart.id == cart_id);
            console.log('carts loaded', this.activeCarts.length)
            if (wantedCart != undefined) {
                console.log('selecting found cart', wantedCart)
                await this.loadCart(wantedCart.id);
                if (attemptCheckout) {
                    console.log('Attempting checkout')
                    this.processingCheckoutDialog = true;
                    this.cartState = 'confirm';
                    this.checkout();
                }
            } else if (cart_uuid != undefined) {
                //Huh. Not one of ours? Well, do it manually then...
                if (attemptCheckout) {
                    console.log('Cart may be someone else\'s or we\'re not logged in. Trying anonymously for UUID', cart_uuid)
                    this.processingCheckoutDialog = true;
                    this.cartState = 'confirm';
                    this.checkoutCartByUUID(cart_uuid);
                }
            } else if (this.needsave)
                await this.saveCart();
            this.cartIdSelected = this.currentCartId;
        } else {

            //Couldn't do that.If they're not logged in, redirect to get a magic link!
            if (query.id)
                this.$router.push({
                    name: 'login',
                    params: {
                        returnTo: this.$route.fullPath,
                        message: 'You need to be logged in to do that.'
                    }
                })
            if (cart_uuid != undefined) {
                //Huh. Not one of ours? Well, do it manually then...
                if (attemptCheckout) {
                    console.log('we\'re not logged in. Trying to check out anonymously for UUID', cart_uuid)
                    this.processingCheckoutDialog = true;
                    this.cartState = 'confirm';
                    this.checkoutCartByUUID(cart_uuid);
                }
            }
        }


        if (this.needsave && this.cartIdSelected != null) {
            console.log('Saving cart because we need to and we have it selected')
            this.saveCart()
                .then((cartId) => {
                    this.cartIdSelected = cartId;
                })
                .catch((error) => {
                    this.cartLocked = error.error.message;
                });
        }
    }
}
</script>
