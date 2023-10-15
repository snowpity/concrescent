<template>
<v-stepper v-model="step"
           :vertical="true">
        <v-stepper-step :editable="reachedStep >= 0"
                        :complete="reachedStep > 0"
                        step="0">Context <small>{{ currentContext.name }} {{isGroupApp ? "Application" : "Badge"}}</small></v-stepper-step>
        <v-stepper-content step="0">
            <v-item-group mandatory 
                      v-model="context_code">
                <v-container>
                <v-row>
                    <v-col
                    v-for="context in badgeContexts"
                    :key="context.context_code"
                    cols="12"
                    md="4"
                    >
                    <v-item :value="context.context_code" v-slot="{ active, toggle }" 
                    :disabled="forbidContextChange">
                        <v-card :elevation="active ? 10 : 1"
                        :dark="active"
                                :color="active ? 'primary' : ''" 
                        @click="toggle">
                            <v-icon class="ma-8"
                            :color="active ? 'white' : ''" 
                                    size=200>mdi-{{ context.menu_icon }}</v-icon>
                            <v-card-title  class="fill-height align-end">{{ context.name }}</v-card-title>

                        </v-card>
                            
                    </v-item>
                    </v-col>
                </v-row>
                </v-container>
            </v-item-group>
            <v-btn color="primary"               
                   @click="step = 1">Continue</v-btn>
        </v-stepper-content>
    <v-stepper-step :editable="reachedStep >= 1"
                    :complete="reachedStep > 1"
                    step="1">{{isGroupApp ? "Application" : "Badge"}} Information <small>{{compiledBadge | badgeDisplayName}} &mdash; {{ badges[selectedBadge_ix] ? badges[selectedBadge_ix].name: "Nothing yet!" | subname }}</small></v-stepper-step>    <v-stepper-content step="1">

        <badgeGenInfo v-model="badgeGenInfoData"
                      :application_name1="currentContext.application_name1"
                      :application_name2="currentContext.application_name2"
                      @valid="setValidGenInfo"
                      :hide_dob="isGroupApp" />
        <badgeTypeSelector v-model="selectedBadge_ix"
                           :badges="badges"
                           @valid="setValidBadgeType"
                           :no-data-text="isGroupApp ? 'Applications currently closed!' : 'No Badges available!'"
                           :editBadgePriorBadgeId="editBadgePriorBadgeId"
                           @click.native="affirmBadgeType" />
        <v-sheet v-if="selectedBadge_ix != null && badges[selectedBadge_ix]"
                 color="grey lighten-4"
                 tile>
            <v-card>
                <v-card-title class="title">Selected:
                    {{ badges[selectedBadge_ix] ? badges[selectedBadge_ix].name : "Nothing yet!" }} {{isProbablyDowngrading ? "Warning: Possible downgrade!" : ""}}
                </v-card-title>
                <v-card-text class="text--primary">
                    Availability: {{badges[selectedBadge_ix].dates_available}}<br>
                    Ages:  {{badges[selectedBadge_ix].age_range}}<br>
                    <badgePerksRender :description="badges[selectedBadge_ix] ? badges[selectedBadge_ix].description : '' "
                                      :rewardlist="rewardlist"></badgePerksRender>
                </v-card-text>
            </v-card>
        </v-sheet>

        <v-btn color="primary"
               :disabled="!(validGenInfo && validBadgeType)"
               @click="step = 2">Continue</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 2"
                    :complete="step > 2"
                    step="2">Choose your Add-ons <small v-if="addonsSelected.length">{{addonsSelected.length}} Selected</small></v-stepper-step>

    <v-stepper-content step="2">
        <v-expansion-panels v-model="addonDisplayState"
                            multiple
                            v-if="badgeAddons.length">
            <h3>Optional addons currently available for the selected {{isGroupApp ? "application" : "badge"}}:</h3>
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
            <h3>No addons are currently available for the selected {{isGroupApp ? "Application" : "Badge"}} type. Check back later if they become available!</h3>
        </div>

        <v-btn color="primary"
               @click="step = 3">Continue</v-btn>
        <v-btn text
               @click="step = 1">Back</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 3"
                    :complete="step > 3"
                    step="3">Contact Information</v-stepper-step>
    <v-stepper-content step="3">

        <v-form ref="fContactInfo"
                v-model="validContactInfo">
            <h3>Badge Owner</h3>
            <v-row>
                <v-col v-if="!isLoggedIn">
                    <router-link to="/login?returnTo=/addbadge">Log in</router-link> or create profile:
                    <profileForm v-model="newAccountData"
                                 @valid="(v) => newAccountDataValid = v"
                                 :rules="RulesNewAccountDataValid" />
                </v-col>
                <v-col v-else>
                    <v-list-item>
                        <b>Logged in as:</b>&nbsp;&nbsp; {{LoggedInName}} &nbsp;&nbsp;
                        <router-link to="/account/logout?returnTo=/addbadge"> Not you?</router-link>
                    </v-list-item>
                </v-col>


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
                            <p>If you've registered before here, you should first verify with a Magic Link. This will let you keep your badge history together.</p>
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
                            <v-card-text>If you have purchased any badges with the contact email <b>{{newAccountData.email_address}}</b>, you should receive an email shortly to log in.</v-card-text>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer />
                            <v-btn color="primary"
                                   @click="closeerror">Ok</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </v-row>
            <h3>Notify email</h3>
            <v-row>
                <v-col cols="12"
                       sm="6"
                       md="6">
                    <v-text-field label="Additional Email address to send confirmation to"
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
        </v-form>
        <v-btn color="primary"
               :disabled="!(validContactInfo || isLoggedIn)"
               :loading="creatingAccount"
               @click="checkCreateAccount">Continue</v-btn>
        <v-btn text
               @click="step = 2">Back</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 4"
                    :complete="step > 4"
                    step="4">Additional Information</v-stepper-step>

    <v-stepper-content step="4">
        <v-form ref="fAdditionalInfo"
                v-model="validAdditionalInfo">
            <formQuestions v-model="form_responses"
                           :questions="badgeQuestions"
                           no-data-text="Nothing else needed at the moment!"
                           @valid="(v) => validAdditionalInfo = v"
                           :rules="RulesValidAdditionalInfo" />
        </v-form>

        <v-btn text
               @click="step = 3">Back</v-btn>
        <v-btn color="primary"
               :disabled="!validAdditionalInfo"
               v-if="hasSubBadges"
               @click="step = 5">Continue</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 5"
                    :complete="step > 5"
                    v-if="hasSubBadges"
                    step="5">Applicant Badges</v-stepper-step>

    <v-stepper-content step="5">
        <h3>Please provide the people who will need a badge for this application.</h3>
        <subBadgeListEditor v-model="subbadges"
                            v-if="hasSubBadges"
                            :base_applicant_count="selectedBadge.base_applicant_count"
                            :max_applicant_count="selectedBadge.max_applicant_count"
                            :price_per_applicant="selectedBadge.price_per_applicant" />
        <v-btn text
               @click="step = 4">Back</v-btn>
    </v-stepper-content>



    <v-footer fixed
              cols="12">
        <v-btn color="red"
               @click="resetBadge">
            <v-icon>mdi-bomb</v-icon>
        </v-btn>
        <v-spacer></v-spacer>
        <v-spacer></v-spacer>

        <v-btn color="primary"
               :disabled="!badgeOk"
               @click="addBadgeToCart">{{ isUpdatingItem ? "Update badge in " :  "Add badge to "}}
            Cart</v-btn>

    </v-footer>

</v-stepper>
</template>

<script>
import {
    mapState,
    mapGetters,
    mapActions
} from 'vuex';

import badgeGenInfo from '@/components/badgeGenInfo.vue';
import formQuestions from '@/components/formQuestions.vue';
import subBadgeListEditor from '@/components/subBadgeListEditor.vue';
import badgeTypeSelector from '@/components/badgeTypeSelector.vue';
import badgePerksRender from '@/components/badgePerksRender.vue';
import profileForm from '@/components/profileForm.vue';

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
            badgeGenInfoData: {
                real_name: '',
                fandom_name: '',
                name_on_badge: 'Real Name Only',
                date_of_birth: "",
            },
            selectedBadge_ix: null,
            validBadgeType: false,
            context_code: 'A',
            badge_type_id: -1,
            menuBDay: false,

            validContactInfo: false,
            newAccountData: {},
            newAccountDataValid: false,
            creatingAccount: false,
            createError: "",
            sendingmagicemail: false,
            sentmagicmail: false,
            notify_email: '',
            can_transfer: false,
            ice_name: '',
            ice_relationship: '',
            ice_email_address: '',
            ice_phone_number: '',

            validAdditionalInfo: false,
            form_responses: {},
            addonsSelected: [],
            subbadges: [],

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
            RulesNewAccountDataValid: [
                (v) => !!this.newAccountDataValid
            ],
            RulesValidAdditionalInfo: [
                (v) => !!this.validAdditionalInfo
            ],

            addonDisplayState: [],
        };
    },
    computed: {
        ...mapGetters('mydata', {
            'isLoggedIn': 'getIsLoggedIn',
            'LoggedInName': 'getLoggedInName',
        }),
        ...mapGetters('products', {
            badgeContexts: 'badgeContexts',
            currentContext: 'selectedbadgecontext',
            products: 'contextBadges',
            questions: 'contextQuestions',
            addonsAvailable: 'contextAddons',
        }),
        rewardlist() {
            // return this.$options.filters.split_carriagereturn(this.selectedBadge.rewards);
            return this.selectedBadge ? this.selectedBadge.rewards : '';
        },
        badges() {
            // Crude clone
            if (this.products == undefined) return [];
            let badges = JSON.parse(JSON.stringify(this.products));
            // First, do we have a date_of_birth?
            const bday = new Date(this.badgeGenInfoData.date_of_birth);
            const shouldCheckBday = !isNaN(bday.getTime());            

            //Disable those that are outside the availability
            var now = new Date();
            badges.forEach((item, i) => {
                var start = new Date(item["start_date"]);
                var end = new Date(item["end_date"]);
                if (end < new Date('2000-01-01'))
                    end.setYear(2099);

                //Check bday
                var bdayvalid = true;
                if(shouldCheckBday){
                    bdayvalid = !(
                        (item['min_birthdate'] != null && bday < new Date(item['min_birthdate'])) ||
                        (item['max_birthdate'] != null && bday > new Date(item['max_birthdate']))
                        );
                }

                //Generate age range
                var age_range = (item['min_age'] != null ? (
                    item['max_age'] != null ? 'Between ' + item['min_age'] + ' and ' + item['max_age'] + ' years'
                    : 'At least ' + item['min_age'] + ' years'
                    ) : (
                    item['max_age'] != null ? 'Younger than ' + item['max_age'] + ' years'
                    : 'All ages'
                    )
                )

                var disabled =
                    (end < now) ||
                    (start > now) ||
                    (item.quantity_remaining === 0) ||
                    !bdayvalid;
                badges[i].disabled = disabled;
                badges[i].age_range = age_range;
                // console.log("Badge disable?", {
                //     start: start,
                //     end: end,
                //     quantity_remaining: item.quantity_remaining,
                //       bdayvalid:bdayvalid,
                //     disabled: disabled,
                //     name: item.name
                // })
            });


            // Are we editing a badge?
            if (this.id > -1) {
                const oldBadge = badges.find((badge) => badge.id == this.editBadgePriorBadgeId);
                if (oldBadge != undefined) {
                    const oldPrice = parseFloat(oldBadge.price);
                    // Determine price difference
                    badges.forEach((badge) => {
                        badge.originalprice = badge.price;
                        badge.price = Math.max(parseFloat(badge.price) - oldPrice, 0).toFixed(2);
                        //If we already had this badge, ensure it's not disabled
                        if (badge.id == oldBadge.id)
                            badge.disabled = false;
                    });
                }
            }

            badges.sort((a, b) => a.order - b.order);
            return badges;
        },
        selectedBadge() {
            return this.badges[this.selectedBadge_ix];
        },
        compiledBadge() {
            // Special because of how the select dropdown works
            return {

                cartIx: this.cartIx,
                id: this.id,
                uuid: this.uuid,
                editBadgePriorBadgeId: this.editBadgePriorBadgeId,
                editBadgePriorAddons: this.editBadgePriorAddons,

                ...this.badgeGenInfoData,
                context_code: this.context_code,
                badge_type_id: this.badge_type_id,

                notify_email: this.notify_email,
                can_transfer: this.can_transfer,
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
                subbadges: this.subbadges,
            };
        },
        badgeOk() {
            return this.validGenInfo &&
                this.validBadgeType &&
                (this.validContactInfo || this.isLoggedIn) &&
                this.validAdditionalInfo &&
                this.reachedStep >= (this.hasSubBadges ? 5 : 4)
        },
        isUpdatingItem() {
            return (this.cartIx != NaN && this.cartIx > -1) || (this.id != null && this.id > -1);
        },
        isProbablyDowngrading() {
            if (!this.isUpdatingItem) {
                return false;
            }

            const oldBadge = this.badges.find((badge) => badge.id == this.editBadgePriorBadgeId);

            return typeof oldBadge !== 'undefined' &&
                typeof selectedBadge_ix !== 'undefined' &&
                parseFloat(oldBadge.originalprice) > parseFloat(this.selectedBadge.originalprice);
        },
        badgeQuestions() {
            // Todo: Filter by badge context
            const badgeId = typeof this.selectedBadge === 'undefined' ? '' : this.selectedBadge.id.toString();
            if (!(badgeId in this.questions)) return {};
            // Filter out the ones that don't apply to this badge
            const result = this.questions[badgeId];

            // Sort it out
            result.sort((a, b) => a.order - b.order);
            return result;
        },
        badgeAddons() {
            // Todo: Filter by badge context
            const badgeId = typeof this.selectedBadge === 'undefined' ? '' : this.selectedBadge.id.toString();
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
        isCreateError: {
            get() {
                return this.createError.length > 0;
            },
            set(newval) {
                this.createError = newval ? "???" : "";
            }
        },
        isGroupApp() {
            if (this.currentContext == undefined) return true;
            return this.currentContext.id > 0;
        },
        hasSubBadges() {
            if (this.selectedBadge != undefined && this.isGroupApp) {
                return this.selectedBadge.max_applicant_count > 0
            }
            return false;
        },
        forbidContextChange() {
            return this.editBadgePriorBadgeId > -1;
        }
    },
    watch: {
        step(newStep) {
            this.reachedStep = Math.max(this.reachedStep, newStep);
            if(this.step == 1){
                this.checkBadge();
            }
            this.autoSaveBadge();
        },
        'badgeGenInfoData.date_of_birth': function() {
            this.checkBadge();
        },
        selectedBadge_ix(val) {
            if (typeof this.badges[val] !== 'undefined')
                this.badge_type_id = this.badges[val].id;
            console.log('Changed badge index', {
                selectedBadge_ix: val,
                badge_type_id: this.badge_type_id
            })
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
            this.loadBadge();
        },
        '$store.state.products.selectedEventId': function(event_id) {
            this.loadBadge();
        },
        context_code(newCode) {
            this.loadBadge(newCode);
        },
    },
    methods: {
        ...mapActions('mydata', {
            'submitCreateAccount': 'createAccount',
            'sendRetrieveBadgeEmail': 'sendRetrieveBadgeEmail',
        }),
        ...mapActions('cart', [
            'addProductToCart',
        ]),
        saveBDay(date) {
            this.$refs.menuBDay.save(date);
            this.date_of_birth = this.date_of_birth;
        },
        checkCreateAccount: function() {
            if (this.isLoggedIn) {
                this.step = 4;
                return;
            }
            this.creatingAccount = true;
            this.submitCreateAccount(this.newAccountData).then((token) => {
                this.creatingAccount = false;
                //They should be logged in now, so move on to the next step
                this.step = 4;
            }).catch((error) => {
                this.createError = error.error.message;
                this.creatingAccount = false;
            })
        },
        SendMagicLink() {
            this.sendingmagicemail = true;
            this.sendRetrieveBadgeEmail({
                email_address: this.newAccountData.email_address,
                returnTo: this.$route.fullPath
            }).then(() => {
                this.sentmagicmail = true;
                this.sendingmagicemail = false;
            });
        },
        closeerror: function() {
            this.createError = "";
            this.sentmagicmail = false;
            this.creatingAccount = false;
        },
        async loadBadge(newCode) {
            let cartItem;
            if (this.$route.query.override) {
                const override = this.$route.query.override;
                console.log('setting override code', override);
                await this.$store.dispatch('products/setOverrideCode', override);
            }
            console.log("params", this.$route.params)
            this.cartIx = parseInt(this.$route.params.cartIx);
            const editIx = this.$route.params.editIx;
            const editAppIx = this.$route.params.editAppIx;
            console.log('load a badge')
            let badge_type_id = -1;
            if (this.cartIx > -1) {
                // Load up the badge from the cart
                console.log('cart item ' + this.cartIx)
                cartItem = this.$store.getters['cart/getProductInCart'](this.cartIx);
                this.reachedStep = 4;
            } else if (editIx != undefined) {
                // Load up the badge from the owned badges
                console.log('owned badge  ' + editIx)
                cartItem = this.$store.getters['mydata/getBadgeAsCart'](editIx);
                this.editBadgePriorBadgeId = cartItem.badge_type_id;
                this.reachedStep = 4;
            } else if (editAppIx != undefined) {
                // Load up the badge from the owned badges
                console.log('application  ' + editAppIx)
                cartItem = this.$store.getters['mydata/getApplicationAsCart'](editAppIx);
                this.editBadgePriorBadgeId = cartItem.badge_type_id;
                this.reachedStep = 4;
            } else if (isNaN(this.cartIx)) {
                // It's a new badge or they're back here from a refresh/navigation
                console.log('refreshed')
                cartItem = this.$store.getters['cart/getCurrentlyEditingItem'];

                // Should only be needed if we didn't have a selectedBadge_ix?
                // this.selectedBadge_ix = this.badges.findIndex(badge => badge.id == cartItem.badge_type_id);
            } else {
                console.log('Something happen?', this.cartIx)
            }
            //Pre-fill the context code
            var context_code = 'A';
            if (cartItem != undefined && cartItem.context_code != undefined) {
                console.log('set context from cart item', cartItem.context_code);
                context_code = cartItem.context_code
            }

            if (this.$route.params.context_code != undefined) {
                console.log('set context from route params', this.$route.params.context_code);
                context_code = this.$route.params.context_code;
            }
            if (this.$route.query.context_code != undefined) {
                console.log('set context from URI query', this.$route.query.context_code);
                context_code = this.$route.query.context_code
            }
            if (newCode != undefined) {
                console.log('set context from dropdown');
                context_code = newCode;
            }
            if (context_code == undefined) {
                console.log('set context from default');
                context_code = "A";
            }

            //If nothing loaded,  early exit
            console.log('current cart data', cartItem)
            if (cartItem != undefined) {
                // Pull out the BadgeId and selected addons
                badge_type_id = cartItem.badge_type_id || 0;
                let addons = cartItem.addons || [];
                // delete cartItem.badge_type_id;
                //Import the general badge info
                cartItem.badgeGenInfoData = {
                    real_name: cartItem.real_name || "",
                    fandom_name: cartItem.fandom_name || "",
                    name_on_badge: cartItem.name_on_badge || "",
                    date_of_birth: cartItem.date_of_birth || "",
                };
                console.log('new data', cartItem);
                Object.assign(this, cartItem);
                // Special props
                const _this = this;

                setTimeout(() => {
                    const newIndex = _this.badges.findIndex((badge) => badge.id == badge_type_id);
                    if (newIndex > -1) {
                        _this.selectedBadge_ix = newIndex;
                    }
                    //Also select any selected addons
                    _this.addonsSelected = addons.map(addon => addon['addon_id']);
                    //Touch the real name so it re-validates
                    this.real_name = this.real_name;

                }, 200);
            }

            this.context_code = context_code;

            //refresh the current context data
            console.log('Selecting context ' + this.context_code);
            try {
                await this.$store.dispatch('products/getEventInfo');
                await this.$store.dispatch('products/selectContext', this.context_code);

            } catch (e) {
                console.log('Selecting context ' + this.context_code + ' failed, waiting for a moment');
                await new Promise(resolve => setTimeout(resolve, 500));
                await this.$store.dispatch('products/getEventInfo');
                await this.$store.dispatch('products/selectContext', this.context_code);
            } finally {

            }
            this.checkBadge();

        },
        resetBadge() {
            Object.assign(this.$data, this.$options.data.apply(this));
            this.$store.commit('cart/setCurrentlyEditingItem', this.compiledBadge);
            this.step = 0;
            this.reachedStep = 0;
        },
        autoSaveBadge() {
            const cartItem = this.compiledBadge;
            cartItem.reachedStep = this.reachedStep;
            cartItem.step = parseInt(this.step);
            cartItem.selectedBadge_ix = this.selectedBadge_ix;
            this.$store.commit('cart/setCurrentlyEditingItem', cartItem);
        },
        checkBadge() {
            // Ensure only applicable badges are selected!
            if (this.badges.length > 0) {
                const bid = this.badge_type_id;
                let badge = this.badges.findIndex((badge) => badge.id == bid);
                if (badge == -1) {
                    console.log("badges are loaded but the type specified wasn't valid?", bid)
                    //Fallback to just selecting the first one
                    this.badge_type_id = this.badges[0].id;
                    console.log("Setting type to first in list", this.badge_type_id)
                    badge = 0;
                }

                this.validBadgeType = true;
                this.selectedBadge_ix = badge;
            } else {
                this.validBadgeType = false;
                this.reachedStep = Math.max(1,this.reachedStep);
                this.step = Math.max(1,this.step);
                console.log('No badges for context, go back to step 1', this.context_code)

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
        affirmBadgeType() {
            if(this.badges[this.selectedBadge_ix]){
                //Has an index selected that exists, ensure the type ID matches
                this.badge_type_id = this.badges[this.selectedBadge_ix].id;
                console.log("affirmed badge type", this.badge_type_id)
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
        badgeAddonPriorSelected(addonid) {
            return this.editBadgePriorAddons.indexOf(addonid) != -1;
        },
        setValidGenInfo(isValid) {
            console.log('setValidGenInfo', isValid);
            this.validGenInfo = isValid;
        },
        setValidBadgeType(isValid) {
            const oldBadge = this.badges.find((badge) => badge.id == this.editBadgePriorBadgeId);

            var alreadyHaveBadge = typeof oldBadge !== 'undefined' &&
                oldBadge.id == this.selectedBadge.id;
            console.log('setValidBadgeType result', {
                isValid,
                alreadyHaveBadge,
                oldBadge: oldBadge ? oldBadge.id : null,
                selectedBadge: this.selectedBadge.id
            })
            this.validBadgeType = isValid || alreadyHaveBadge;
        }
    },
    components: {
        badgeGenInfo,
        badgeTypeSelector,
        formQuestions,
        subBadgeListEditor,
        badgePerksRender,
        profileForm,
    },
    created() {
        this.loadBadge();
    },
};
</script>
