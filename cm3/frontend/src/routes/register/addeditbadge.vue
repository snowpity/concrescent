<template>
<v-stepper v-model="step"
           :vertical="true">
    <v-stepper-step :editable="reachedStep >= 1"
                    :complete="reachedStep > 1"
                    step="1">Badge Information <small>{{compiledBadge | badgeDisplayName}} &mdash; {{ badges[selectedBadge] ? badges[selectedBadge].name: "Nothing yet!" | subname }}</small></v-stepper-step>
    <v-stepper-content step="1">

        <v-form ref="fGenInfo"
                v-model="validGenInfo">
            <v-row>
                <v-col cols="12"
                       md="6">
                    <v-text-field v-model="real_name"
                                  :counter="500"
                                  :rules="RulesName"
                                  label="Real Name"
                                  required></v-text-field>
                </v-col>

                <v-col cols="12"
                       md="6">
                    <v-text-field v-model="fandom_name"
                                  :counter="255"
                                  :rules="RulesNameFandom"
                                  label="Fandom Name (Optional)"></v-text-field>
                </v-col>
                <v-col cols="12"
                       md="6">
                    <v-select v-if="fandom_name"
                              v-model="name_on_badge"
                              :rules="RulesNameDisplay"
                              :items="name_on_badgeType"
                              label="Display on badge"></v-select>
                </v-col>
            </v-row>
            <v-row>
                <v-col cols="12"
                       md="6">
                    <v-menu ref="menuBDay"
                            v-model="menuBDay"
                            :close-on-content-click="false"
                            transition="scale-transition"
                            offset-y
                            min-width="290px">
                        <template v-slot:activator="{ on }">
                            <v-text-field v-model="date_of_birth"
                                          type="date"
                                          label="Date of Birth"
                                          v-on="on"
                                          :rules="RulesRequired"></v-text-field>
                        </template>
                        <v-date-picker ref="pickerBDay"
                                       v-model="date_of_birth"
                                       :max="new Date().toISOString().substr(0, 10)"
                                       min="1920-01-01"
                                       @change="saveBDay"
                                       :active-picker.sync="bdayActivePicker"></v-date-picker>
                    </v-menu>
                </v-col>
                <v-col cols="12"
                       md="6">
                    <v-btn @click="resetBadge"
                           class="float-right">Reset Form</v-btn>
                </v-col>
            </v-row>
            <h3>Badge Type</h3>
            <div v-if="badges.length == 0">
                <h1>No badges currently available!</h1>
            </div>
            <div class="d-none d-sm-block">
                <v-slide-group v-model="selectedBadge"
                               class="pa-4"
                               :class="{warning:isProbablyDowngrading}"
                               show-arrows
                               mandatory
                               center-active>
                    <v-icon slot="prev"
                            size="100">mdi-chevron-left</v-icon>
                    <v-icon slot="next"
                            size="100">mdi-chevron-right</v-icon>
                    <v-slide-item v-for="(product,idx) in badges"
                                  :key="product.id"
                                  v-slot:default="{ active, toggle }"
                                  :value="idx">
                        <v-card :dark="active"
                                :color="active ? 'primary' : 'grey lighten-1'"
                                class="ma-4"
                                min-width="220"
                                @click="toggle"
                                :disabled="product.quantity != null && !product.quantity">
                            <v-card-title align="center"
                                          justify="center">
                                {{ product.name | subname}}
                            </v-card-title>
                            <v-card-text>
                                {{ product.name | subname(true)}}&nbsp;
                            </v-card-text>
                            <v-card-actions>&nbsp;
                                <h4 text
                                    v-if="product.quantity_remaining">Only
                                    {{product.quantity_remaining}}
                                    left!
                                </h4>
                                <h4 v-else-if="product.quantity_remaining == 0">Sold out!</h4>
                                <v-spacer></v-spacer>
                                <v-btn color="green"
                                       dark>{{product.price | currency}}</v-btn>
                            </v-card-actions>

                        </v-card>
                    </v-slide-item>
                </v-slide-group>
            </div>
            <div class="d-block d-sm-none">
                <v-select v-model="selectedBadge"
                          :items="badges"
                          label="Select Badge"
                          :item-value="badgeIndex"
                          :item-disabled="quantityZero"
                          :class="{warning:isProbablyDowngrading}">
                    <template v-slot:item="{item}">
                        <v-card color="grey lighten-1"
                                class="ma-4"
                                min-width="220"
                                :disabled="item.quantity != null && !item.quantity">
                            <v-card-title align="center"
                                          justify="center">
                                {{ item.name | subname}}
                            </v-card-title>
                            <v-card-text>
                                {{ item.name | subname(true)}}&nbsp;
                            </v-card-text>
                            <v-card-actions>&nbsp;
                                <h4 text
                                    v-if="item.quantity_remaining">Only
                                    {{item.quantity_remaining}}
                                    left!
                                </h4>
                                <h4 v-else-if="item.quantity_remaining == 0">Sold out!</h4>
                                <v-spacer></v-spacer>
                                <v-btn color="green"
                                       dark>{{item.price | currency}}</v-btn>
                            </v-card-actions>

                        </v-card>
                    </template>
                    <template v-slot:selection="{ item }">
                        <v-card dark
                                color="primary"
                                class="ma-4"
                                min-width="220"
                                :disabled="item.quantity != null && !item.quantity">
                            <v-card-title align="center"
                                          justify="center">
                                {{ item.name | subname}}
                            </v-card-title>
                            <v-card-text>
                                {{ item.name | subname(true)}}&nbsp;
                            </v-card-text>
                            <v-card-actions>&nbsp;
                                <h4 text
                                    v-if="item.quantity_remaining">Only
                                    {{item.quantity_remaining}}
                                    left!
                                </h4>
                                <h4 v-else-if="item.quantity_remaining == 0">Sold out!</h4>
                                <v-spacer></v-spacer>
                                <v-btn color="green"
                                       dark>{{item.price | currency}}</v-btn>
                            </v-card-actions>

                        </v-card>
                    </template>
                </v-select>
            </div>
            <v-sheet v-if="selectedBadge != null"
                     color="grey lighten-4"
                     tile>
                <v-card>
                    <v-card-title class="title">Selected:
                        {{ badges[selectedBadge] ? badges[selectedBadge].name : "Nothing yet!" }} {{isProbablyDowngrading ? "Warning: Possible downgrade!" : ""}}
                    </v-card-title>
                    <v-card-text class="text--primary">
                        <badgePerksRender :description="badges[selectedBadge] ? badges[selectedBadge].description : '' "
                                          :rewardlist="rewardlist"></badgePerksRender>
                    </v-card-text>
                </v-card>
            </v-sheet>
        </v-form>

        <v-btn color="primary"
               :disabled="!validGenInfo"
               @click="step = 2">Continue</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 2"
                    :complete="step > 2"
                    step="2">Additional Contact Information</v-stepper-step>
    <v-stepper-content step="2">
        <h3>Notify email</h3>
        <v-row>
            <v-col cols="12"
                   sm="6"
                   md="6">
                <v-text-field label="Alternate Email address to send confirmation to"
                              v-model="notify_email"
                              :rules="RulesEmail"></v-text-field>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="6">
                <v-checkbox dense
                            hide-details
                            v-model="can_transfer">
                    <template v-slot:label>
                        <small>Allow badge transfer to the owner of this email.</small>
                    </template>
                </v-checkbox>
            </v-col>
        </v-row>

        <h3>In case of Emergency</h3>
        <v-row>

            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Emergency Contact Name"
                              v-model="ice_name"></v-text-field>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Relationship"
                              v-model="ice_relationship"></v-text-field>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Email address"
                              v-model="ice_email_address"
                              :rules="RulesEmail"></v-text-field>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Phone Number"
                              v-model="ice_phone_number"
                              :rules="RulesPhone"></v-text-field>
            </v-col>
        </v-row>
        <v-btn color="primary"
               @click="step = 3">Continue</v-btn>
        <v-btn text
               @click="step = 1">Back</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 3"
                    :complete="step > 3"
                    step="3">Additional Information</v-stepper-step>

    <v-stepper-content step="3">
        <v-form ref="fAdditionalInfo"
                v-model="validAdditionalInfo">
            <formQuestions v-model="form_responses"
                           :questions="badgeQuestions"
                           no-data-text="Nothing else needed at the moment!" />
        </v-form>

        <v-btn color="primary"
               :disabled="!validAdditionalInfo"
               @click="step = 4">Continue</v-btn>
        <v-btn text
               @click="step = 2">Back</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 4"
                    :complete="step > 4"
                    step="4">Choose your Add-ons <small v-if="addonsSelected.length">{{addonsSelected.length}} Selected</small></v-stepper-step>

    <v-stepper-content step="4">
        <v-expansion-panels v-model="addonDisplayState"
                            multiple
                            v-if="badgeAddons.length">
            <v-expansion-panel v-for="addon in badgeAddons"
                               v-bind:key="addon.id">
                <v-expansion-panel-header>
                    <v-checkbox hide-details
                                multiple
                                :value="addon['id']"
                                v-model="addonsSelected"
                                :disabled="badgeAddonPriorSelected(addon['id']) || addon.quantity_remaining == 0">
                        <template slot="label">
                            <h3 class="black--text">{{addon.name}}</h3>
                        </template>
                    </v-checkbox>
                    <template slot="actions">
                        <h4 text
                            v-if="addon.quantity_remaining">Only
                            {{addon.quantity_remaining}}
                            left!
                        </h4>
                        <h4 v-else-if="addon.quantity_remaining == 0">Sold out!</h4>
                        <v-btn class="ml-5"
                               color="green"
                               dark>{{addon.price | currency}}</v-btn>
                        <v-icon class="px-3"
                                color="primary">$expand</v-icon>
                    </template>
                </v-expansion-panel-header>
                <v-expansion-panel-content>
                    <badgePerksRender :description="addon.description"
                                      :rewardlist="addon.rewards"></badgePerksRender>
                </v-expansion-panel-content>
            </v-expansion-panel>

        </v-expansion-panels>
        <div v-else>
            <h3>No addons are currently available for the selected badge type. Check back later when they become available!</h3>
        </div>
        <v-btn color="primary"
               @click="addBadgeToCart">{{ isUpdatingItem ? "Update in " :  "Add to "}}
            Cart</v-btn>
        <v-btn text
               @click="step = 3">Back</v-btn>
    </v-stepper-content>

</v-stepper>
</template>

<script>
import {
    mapState,
    mapActions
} from 'vuex';

import formQuestions from '@/components/formQuestions.vue';
import badgePerksRender from '@/components/badgePerksRender.vue';

export default {
    data() {
        return {
            step: 1,
            reachedStep: 1,
            cartIx: -1,
            id: -1, // Attendee's ID, not the badgeType
            uuid: '',
            editBadgePriorBadgeId: -1,
            editBadgePriorAddons: [],

            validGenInfo: false,
            real_name: '',
            fandom_name: '',
            name_on_badge: 'Real Name Only',
            name_on_badgeType: ['Fandom Name Large, Real Name Small', 'Real Name Large, Fandom Name Small', 'Real Name Only', 'Fandom Name Only'],
            date_of_birth: null,
            bdayActivePicker: 'YEAR',
            selectedBadge: null,
            context_code: 'A',
            badge_type_id: -1,
            menuBDay: false,

            notify_email: '',
            can_transfer: false,
            ice_name: '',
            ice_relationship: '',
            ice_email_address: '',
            ice_phone_number: '',

            validAdditionalInfo: false,
            form_responses: {},
            addonsSelected: [],

            RulesRequired: [
                (v) => !!v || 'Required',
            ],
            RulesName: [
                (v) => !!v || 'Name is required',
                (v) => (v && v.length <= 500) || 'Name must be less than 500 characters',
            ],
            RulesNameFandom: [

                (v) => (v == '' || (v && v.length <= 255)) || 'Name must be less than 255 characters',
            ],
            RulesNameDisplay: [
                (v) => ((this.fandom_name.length < 1) || (this.fandom_name.length > 0 && v != '')) || 'Please select a display type',
            ],
            RulesEmail: [
                (v) => !v || /.+@.+\..+/.test(v) || 'E-mail must be valid',
            ],
            RulesPhone: [
                (v) => !v || v.length > 6 || 'Phone number too short',
                /* v =>  !v || /^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/.test(v) || 'Phone number should be valid' */
            ],

            addonDisplayState: [],
        };
    },
    computed: {
        ...mapState({
            products: (state) => state.products.all,
            questions: (state) => state.products.questions,
            addonsAvailable: (state) => state.products.addons,
        }),
        rewardlist() {
            // return this.$options.filters.split_carriagereturn(this.badges[this.selectedBadge].rewards);
            return this.badges[this.selectedBadge] ? this.badges[this.selectedBadge].rewards : '';
        },
        badges() {
            // Crude clone
            let badges = JSON.parse(JSON.stringify(this.products));
            // First, do we have a date_of_birth?
            const bday = new Date(this.date_of_birth);
            if (this.date_of_birth && bday) {
                badges = badges.filter((badge) => {
                    if (!(
                            (badge['min-birthdate'] != null && bday < new Date(badge['min-birthdate'])) ||
                            (badge['max-birthdate'] != null && bday > new Date(badge['max-birthdate']))
                        )) {
                        return badge;
                    }
                });
            }

            // Are we editing a badge?
            if (this.id > -1) {
                const oldBadge = badges.find((badge) => badge.id == this.editBadgePriorBadgeId);
                if (oldBadge != undefined) {
                    const oldPrice = parseFloat(oldBadge.price);
                    // Determine price difference
                    badges.forEach((badge) => {
                        badge.originalprice = badge.price;
                        badge.price = Math.max(parseFloat(badge.price) - oldPrice, 0).toFixed(2);
                    });
                }
            }

            badges.sort((a, b) => a.order - b.order);
            return badges;
        },
        compiledBadge() {
            // Special because of how the select dropdown works
            return {

                cartIx: this.cartIx,
                id: this.id,
                uuid: this.uuid,
                editBadgePriorBadgeId: this.editBadgePriorBadgeId,
                editBadgePriorAddons: this.editBadgePriorAddons,

                real_name: this.real_name,
                fandom_name: this.fandom_name,
                name_on_badge: this.name_on_badge,
                date_of_birth: this.date_of_birth,
                context_code: this.context_code,
                badge_type_id: this.badge_type_id,

                notify_email: this.notify_email,
                ice_name: this.ice_name,
                ice_relationship: this.ice_relationship,
                ice_email_address: this.ice_email_address,
                ice_phone_number: this.ice_phone_number,
                form_responses: this.form_responses,
                addons: this.addonsSelected.map(id => {
                    return {
                        'addon_id': id
                    }
                }),

            };
        },
        isUpdatingItem() {
            return (this.cartIx != null && this.cartIx > -1) || (this.id != null && this.id > -1);
        },
        isProbablyDowngrading() {
            if (!this.isUpdatingItem) {
                return false;
            }

            const oldBadge = this.badges.find((badge) => badge.id == this.editBadgePriorBadgeId);
            const selectedBadge = this.badges[this.selectedBadge];
            return typeof oldBadge !== 'undefined' &&
                typeof selectedBadge !== 'undefined' &&
                parseFloat(oldBadge.originalprice) > parseFloat(selectedBadge.originalprice);
        },
        badgeQuestions() {
            // Todo: Filter by badge context
            const badgeId = typeof this.badges[this.selectedBadge] === 'undefined' ? '' : this.badges[this.selectedBadge].id.toString();
            if (!(badgeId in this.questions)) return {};
            // Filter out the ones that don't apply to this badge
            const result = this.questions[badgeId];
            // Apply display logic
            result.forEach((question) => {

            });
            // Sort it out
            result.sort((a, b) => a.order - b.order);
            return result;
        },
        badgeAddons() {
            // Todo: Filter by badge context
            const badgeId = typeof this.badges[this.selectedBadge] === 'undefined' ? '' : this.badges[this.selectedBadge].id.toString();
            // Do we have questions at all for this badge?
            if (!(badgeId in this.addonsAvailable)) return {};
            // Filter out the ones that don't apply to this badge
            let result = this.addonsAvailable[badgeId];

            // First, do we have a date_of_birth?
            const bday = new Date(this.date_of_birth);
            if (this.date_of_birth && bday) {
                result = result.filter((badge) => {
                    if (!(
                            (badge['min-birthdate'] != null && bday < new Date(badge['min-birthdate'])) ||
                            (badge['max-birthdate'] != null && bday > new Date(badge['max-birthdate']))
                        )) {
                        return badge;
                    }
                });
            }
            /// /Apply logic to required
            // result.forEach(function(question){
            //  question.isRequired = question.required == '*' || question.required.includes(badgeId)
            // })

            // Sort it out
            result.sort((a, b) => a.order - b.order);
            return result;
        },
    },
    watch: {
        step(newStep) {
            this.reachedStep = Math.max(this.reachedStep, newStep);
            this.autoSaveBadge();
        },
        menuBDay(val) {
            // Whenever opening the picker, always reset it back to start with the Year
            val && setTimeout(() => (this.bdayActivePicker = 'YEAR'));
        },
        date_of_birth() {
            this.checkBadge();
        },
        selectedBadge(val) {
            this.badge_type_id = typeof this.badges[val] === 'undefined' ? this.badge_type_id : this.badges[val].id;
        },
        compiledBadge() {
            this.autoSaveBadge();
        },
        '$route.name': function(name) {
            // The only way this changes is... if they click the Add Badge from the main menu while still here
            // Still, in case of weirdness...
            if (name == 'addbadge') {
                this.resetBadge();
            }
        },
        $route( /* to, from */ ) {
            // react to route changes...
            this.refreshContext();
        },
        '$store.state.products.selectedEventId': function(event_id) {
            this.refreshContext();
        },
    },
    methods: {
        ...mapActions('cart', [
            'addProductToCart',
            'setLatestContactInfo',
        ]),
        saveBDay(date) {
            this.$refs.menuBDay.save(date);
            this.date_of_birth = this.date_of_birth;
        },
        refreshContext() {
            this.$store.commit('products/setBadgeContextSelected', 'A');
            //refresh the context data
            this.$store.dispatch('products/getAllProducts').then(this.loadBadge());
            this.$store.dispatch('products/getAllQuestions');
            this.$store.dispatch('products/getAllAddons');
        },
        loadBadge() {
            let cartItem;
            this.cartIx = parseInt(this.$route.params.cartIx);
            const idString = this.$route.params.editIx;
            console.log('load a badge')
            let badge_type_id = -1;
            if (this.cartIx > -1) {
                // Load up the badge from the cart
                cartItem = this.$store.getters['cart/getProductInCart'](this.cartIx);
                this.reachedStep = 4;
            } else if (idString != undefined) {
                // Load up the badge from the owned badges
                cartItem = this.$store.getters['mydata/getBadgeAsCart'](idString);
                this.editBadgePriorBadgeId = cartItem.badge_type_id;
                this.reachedStep = 4;
            } else if (this.$route.params.cartIx == undefined) {
                // It's a new badge or they're back here from a refresh/navigation
                cartItem = this.$store.getters['cart/getCurrentlyEditingItem'];

                // Should only be needed if we didn't have a selectedBadge?
                // this.selectedBadge = this.badges.findIndex(badge => badge.id == cartItem.badge_type_id);
            }

            //If nothing loaded,  early exit
            if (cartItem == undefined) return;

            // Pull out the BadgeId and selected addons
            badge_type_id = cartItem.badge_type_id || 0;
            let addons = cartItem.addons || [];
            // delete cartItem.badge_type_id;
            Object.assign(this, cartItem);
            // Special props
            const _this = this;

            this.checkBadge();
            setTimeout(() => {
                const newIndex = _this.badges.findIndex((badge) => badge.id == badge_type_id);
                if (newIndex > -1) {
                    _this.selectedBadge = newIndex;
                }
                //Also select any selected addons
                _this.addonsSelected = addons.map(addon => addon['addon_id']);

            }, 200);
        },
        resetBadge() {
            Object.assign(this.$data, this.$options.data.apply(this));
            this.$store.commit('cart/setCurrentlyEditingItem', this.compiledBadge);
            this.step = 1;
        },
        autoSaveBadge() {
            const cartItem = this.compiledBadge;
            cartItem.reachedStep = this.reachedStep;
            cartItem.step = parseInt(this.step);
            cartItem.selectedBadge = this.selectedBadge;
            this.$store.commit('cart/setCurrentlyEditingItem', cartItem);
        },
        checkBadge() {
            // Ensure only applicable badges are selected!
            if (this.badges.length > 0) {
                const bid = this.badge_type_id;
                let badge = this.badges.findIndex((badge) => badge.id == bid);
                if (badge == -1) badge = 0;
                this.selectedBadge = badge;
            }

            // Ensure only applicable badge addons are selected!
            const {
                badgeAddons
            } = this;
            if (badgeAddons.length > 0) {
                if (typeof this.addonsSelected.filter === 'function') {
                    this.addonsSelected = this.addonsSelected.filter((aid) => undefined != badgeAddons[aid]);
                }
            }
        },
        addBadgeToCart() {
            this.addProductToCart(this.compiledBadge);
            // TODO: Resolve a promise from the above so we can update the history to point to the edit-badge version of this
            this.resetBadge();
            // Also reset the saved info so we don't accidentally resume editing
            this.autoSaveBadge();
            // Go to the cart
            this.$router.push('/cart');
        },
        badgeIndex(item) {
            return this.badges.indexOf(item);
        },
        quantityZero(item) {
            return item.quantity == 0;
        },
        badgeAddonPriorSelected(addonid) {
            return this.editBadgePriorAddons.indexOf(addonid) != -1;
        },
    },
    components: {
        formQuestions,
        badgePerksRender,
    },
    created() {
        this.refreshContext();
    },
};
</script>
