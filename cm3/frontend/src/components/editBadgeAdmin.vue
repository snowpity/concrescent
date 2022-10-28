<template>
<v-card>
    <v-app-bar style="position: fixed; z-index:2">

        <v-tabs grow
                background-color="indigo"
                v-model="step"
                show-arrows
                center-active
                dark>
            <v-tab>
                Badge Information
            </v-tab>
            <v-tab>
                Addons
            </v-tab>
            <v-tab>
                Contact Information
            </v-tab>
            <v-tab>
                Additional Information
            </v-tab>
            <v-tab>
                Transactions
            </v-tab>
        </v-tabs>
    </v-app-bar>
    <v-toolbar />
    <v-tabs-items v-model="step">
        <v-container>
            <v-tab-item key="1">

                <badgeGenInfo v-model="model.badgeGenInfoData"
                              @valid="setValidGenInfo" />
                <badgeTypeSelector v-model="selectedbadge"
                                   :badges="badges"
                                   no-data-text="No badges currently available!"
                                   :editBadgePriorBadgeId="model.editBadgePriorBadgeId" />

                <v-expansion-panels>
                    <v-expansion-panel v-if="selectedbadge != null">
                        <v-expansion-panel-header>
                            Selected Badge info:
                            {{ badges[selectedbadge] ? badges[selectedbadge].name : "Nothing yet!" }} {{isProbablyDowngrading ? "Warning: Possible downgrade!" : ""}}
                        </v-expansion-panel-header>
                        <v-expansion-panel-content>
                            <badgePerksRender :description="badges[selectedbadge] ? badges[selectedbadge].description : '' "
                                              :rewardlist="rewardlist"></badgePerksRender>
                        </v-expansion-panel-content>
                    </v-expansion-panel>
                </v-expansion-panels>

                <v-row>
                    <v-col cols="12"
                           sm="6"
                           md="4">
                        <v-text-field label="Display ID"
                                      v-model="model.display_id"></v-text-field>
                    </v-col>
                    <v-col cols="12"
                           sm="6"
                           md="4">
                        <v-text-field label="time_printed"
                                      v-model="model.time_printed"></v-text-field>
                    </v-col>
                    <v-col cols="12"
                           sm="6"
                           md="4">
                        <v-text-field label="time_checked_in"
                                      v-model="model.time_checked_in"></v-text-field>
                    </v-col>
                    <v-col cols="12">
                        <v-textarea label="Notes"
                                    v-model="model.notes">
                        </v-textarea>
                    </v-col>


                </v-row>
            </v-tab-item>
            <v-tab-item key="2">
                <v-expansion-panels v-model="addonDisplayState"
                                    multiple
                                    v-if="badgeAddons.length">
                    <h3>Addons currently available for the selected badge:</h3>
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
                    <h3>No addons are currently available for the selected badge type. Check back later if they become available!</h3>
                </div>

            </v-tab-item>

            <v-tab-item key="3">

                <v-form ref="fContactInfo"
                        v-model="validContactInfo">
                    <h3>Badge Owner</h3>
                    <v-row>
                        <v-col v-if="hasEventPerm('Contact_Full')">
                            <profileForm v-model="model.contact"
                                         readonly />
                        </v-col>
                        <v-col v-else>
                            Not available with current permissions
                        </v-col>
                    </v-row>
                    <h3>Notify email</h3>
                    <v-row>
                        <v-col cols="12"
                               sm="6"
                               md="6">
                            <v-text-field label="Additional Email address to send confirmation to"
                                          v-model="model.notify_email"
                                          :rules="RulesEmail"></v-text-field>
                        </v-col>
                        <v-col cols="12"
                               sm="6"
                               md="6">
                            <v-checkbox dense
                                        hide-details
                                        v-model="model.can_transfer">
                                <template v-slot:label>
                                    <small>Allow badge transfer to the owner of this email.</small>
                                </template>
                            </v-checkbox>
                        </v-col>
                    </v-row>

                    <h3>In case of Emergency</h3>
                    <v-row v-if="hasEventPerm('Badge_Ice')">

                        <v-col cols="12"
                               sm="6"
                               md="3">
                            <v-text-field label="Emergency Contact Name"
                                          v-model="model.ice_name"></v-text-field>
                        </v-col>
                        <v-col cols="12"
                               sm="6"
                               md="3">
                            <v-text-field label="Relationship"
                                          v-model="model.ice_relationship"></v-text-field>
                        </v-col>
                        <v-col cols="12"
                               sm="6"
                               md="3">
                            <v-text-field label="Email address"
                                          v-model="model.ice_email_address"
                                          :rules="RulesEmail"></v-text-field>
                        </v-col>
                        <v-col cols="12"
                               sm="6"
                               md="3">
                            <v-text-field label="Phone Number"
                                          v-model="model.ice_phone_number"
                                          :rules="RulesPhone"></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row v-else>
                        Not available with current permissions
                    </v-row>
                </v-form>
            </v-tab-item>


            <v-tab-item key="4">
                <v-form ref="fAdditionalInfo"
                        v-model="validAdditionalInfo">
                    <formQuestions v-model="model.form_responses"
                                   :questions="badgeQuestions"
                                   no-data-text="No questions enabled for this badge type." />
                </v-form>

            </v-tab-item>
            <v-tab-item key="5">
                <paymentItemView v-model="model" />

            </v-tab-item>

        </v-container>
    </v-tabs-items>

    <v-dialog v-model="reviewDialog"
              :fullscreen="$vuetify.breakpoint.xsOnly"
              scrollable>
        <template v-slot:activator="{ on, attrs }">

            <v-btn :color="applicationStatusData.color"
                   fixed
                   bottom
                   right
                   v-if="model.application_status"
                   faba
                   v-bind="attrs"
                   v-on="on">
                Review
                <v-icon>
                    mdi-check-decagram-outline
                </v-icon>
            </v-btn>
        </template>
        <v-card>
            <v-card-title>Application Review</v-card-title>
            <v-divider></v-divider>
            <v-card-text>
                <v-row v-if="model.context_code=='S'">
                    <v-col cols="12">
                        <editBadgeApplicationStaffPosition v-model="model.assigned_positions" />
                    </v-col>
                </v-row>
            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions>
                <v-checkbox v-model="sendUpdate"
                            label="Send update" />
                <v-spacer />
                <v-select v-model="newApplication_status"
                          :items="appStatusNextList"
                          :hint="appStatusNext.Text"
                          item-text="actionText"
                          label="Action to take">
                </v-select>
                <v-btn :color="appStatusNext.color"
                       @click="submitReview(newApplication_status)">
                    Go
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</v-card>
</template>

<script>
import {
    mapState,
    mapGetters,
    mapActions
} from 'vuex';

import badgeGenInfo from '@/components/badgeGenInfo.vue';
import formQuestions from '@/components/formQuestions.vue';
import badgeTypeSelector from '@/components/badgeTypeSelector.vue';
import badgePerksRender from '@/components/badgePerksRender.vue';
import profileForm from '@/components/profileForm.vue';
import paymentItemView from '@/components/paymentItemView.vue';
import editBadgeApplicationStaffPosition from '@/components/editBadgeApplicationStaffPosition.vue';

export default {
    props: ['value'],
    data() {
        return {
            step: 0,
            skipEmitOnce: false,
            reviewDialog: false,
            validGenInfo: false,
            validContactInfo: false,
            validAdditionalInfo: false,
            sendUpdate: true,
            model: {
                cartIx: -1,
                id: -1, // Attendee's ID, not the badgeType
                uuid: '',
                editBadgePriorBadgeId: -1,
                editBadgePriorAddons: [],

                context_code: 'A',
                badge_type_id: -1,

                notify_email: '',
                can_transfer: false,
                ice_name: '',
                ice_relationship: '',
                ice_email_address: '',
                ice_phone_number: '',

                form_responses: {},
                application_status: '',

                assigned_positions: undefined,
            },
            newApplication_status: null,
            addonsSelected: [],
            selectedbadge: null,
            modelString: '',

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

            applicationStatusMap: {
                'InProgress': {
                    value: 'InProgress',
                    color: 'indigo',
                    text: 'Draft',
                    actionText: 'Revert to Draft',
                    nextStatus: [
                        'Submitted',
                        'Cancelled',
                        'Rejected',
                        'PendingAcceptance',
                        'Waitlisted',
                        'Onboarding',
                        'Active',
                        'Terminated',
                    ]
                },
                'Submitted': {
                    value: 'Submitted',
                    color: 'purple accent-2',
                    text: 'Newly submitted',
                    actionText: 'Revert to Submitted',
                    nextStatus: [
                        'Cancelled',
                        'Rejected',
                        'Waitlisted',
                        'PendingAcceptance',
                    ]
                },
                'Cancelled': {
                    value: 'Cancelled',
                    color: 'red',
                    text: 'Applicant self-cancelled',
                    actionText: 'Cancel',
                    nextStatus: [
                        'Submitted',
                    ]
                },
                'Rejected': {
                    value: 'Rejected',
                    color: 'red',
                    text: 'Rejected',
                    actionText: 'Reject',
                    nextStatus: [
                        'Submitted',
                    ]
                },
                'PendingAcceptance': {
                    value: 'PendingAcceptance',
                    color: 'yellow',
                    text: 'Accepted, waiting for them to confirm',
                    actionText: 'Accept',
                    nextStatus: [
                        'Cancelled',
                        'Waitlisted',
                        'Onboarding',
                    ]
                },
                'Waitlisted': {
                    value: 'Waitlisted',
                    color: 'gray',
                    text: 'Waitlisted for consideration',
                    actionText: 'Waitlist',
                    nextStatus: [
                        'Rejected',
                        'PendingAcceptance',
                    ]
                },
                'Onboarding': {
                    value: 'Onboarding',
                    color: 'blue',
                    text: 'Accepted, onboarding in progress',
                    actionText: 'Begin Onboarding',
                    nextStatus: [
                        'Rejected',
                        'Terminated',
                        'Active',
                    ]
                },
                'Active': {
                    value: 'Active',
                    color: 'green',
                    text: 'Accepted, active staff',
                    actionText: 'Mark Active',
                    nextStatus: [
                        'Terminated',
                    ]
                },
                'Terminated': {
                    value: 'Terminated',
                    color: 'black',
                    text: 'No longer welcome here',
                    actionText: 'Terminate',
                    nextStatus: []
                },
            },
        };
    },
    computed: {
        ...mapGetters('mydata', {
            'isLoggedIn': 'getIsLoggedIn',
            'LoggedInName': 'getLoggedInName',
            'hasEventPerm': 'hasEventPerm'
        }),
        ...mapGetters('products', {
            badgeContexts: 'badgeContexts',
            currentContext: 'selectedbadgecontext',
            products: 'contextBadges',
            questions: 'contextQuestions',
            addonsAvailable: 'contextAddons',
        }),
        rewardlist() {
            // return this.$options.filters.split_carriagereturn(this.badges[this.selectedBadge].rewards);
            return this.badges[this.selectedbadge] ? this.badges[this.selectedbadge].rewards : '';
        },
        badges() {
            // Crude clone
            if (this.products == undefined) return [];
            let badges = JSON.parse(JSON.stringify(this.products));
            // // First, do we have a date_of_birth?
            // const bday = new Date(this.badgeGenInfoData.date_of_birth);
            // if (this.badgeGenInfoData.date_of_birth && bday) {
            //     badges = badges.filter((badge) => {
            //         if (!(
            //                 (badge['min_birthdate'] != null && bday < new Date(badge['min_birthdate'])) ||
            //                 (badge['max_birthdate'] != null && bday > new Date(badge['max_birthdate']))
            //             )) {
            //             return badge;
            //         }
            //     });
            // }

            // Are we editing a badge?
            if (this.id > -1) {
                const oldBadge = badges.find((badge) => badge.id == this.model.editBadgePriorBadgeId);
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
                id: this.model.id,
                contact_id: this.model.contact_id,
                display_id: this.model.display_id,
                editBadgePriorBadgeId: this.model.editBadgePriorBadgeId,
                editBadgePriorAddons: this.model.editBadgePriorAddons,

                ...this.model.badgeGenInfoData,
                context_code: this.model.context_code,
                badge_type_id: this.model.badge_type_id,
                application_status: this.model.application_status,
                time_checked_in: this.model.time_checked_in,
                time_printed: this.model.time_printed,

                notify_email: this.model.notify_email,
                ice_name: this.model.ice_name,
                ice_relationship: this.model.ice_relationship,
                ice_email_address: this.model.ice_email_address,
                ice_phone_number: this.model.ice_phone_number,
                form_responses: this.model.form_responses,
                addons: this.addonsSelected.map(id => {
                    return {
                        'addon_id': id
                    }
                }),

                notes: this.model.notes,
                //supplementary data
                assigned_positions: this.model.assigned_positions,

            };
        },
        context_code() {
            return this.model.context_code;
        },
        badgeOk() {
            return this.validGenInfo &&
                this.validContactInfo &&
                this.validAdditionalInfo
        },
        isUpdatingItem() {
            return (this.cartIx != null && this.cartIx > -1) || (this.id != null && this.id > -1);
        },
        isProbablyDowngrading() {
            if (!this.isUpdatingItem) {
                return false;
            }

            const oldBadge = this.badges.find((badge) => badge.id == this.model.editBadgePriorBadgeId);
            const selectedBadge = this.badges[this.selectedbadge];
            return typeof oldBadge !== 'undefined' &&
                typeof selectedBadge !== 'undefined' &&
                parseFloat(oldBadge.originalprice) > parseFloat(selectedBadge.originalprice);
        },
        badgeQuestions() {
            // Todo: Filter by badge context
            const badgeId = typeof this.badges[this.selectedbadge] === 'undefined' ? '' : this.badges[this.selectedbadge].id.toString();
            if (!(badgeId in this.questions)) return {};
            // Filter out the ones that don't apply to this badge
            var result = this.questions[badgeId];

            // Sort it out
            result.sort((a, b) => a.order - b.order);
            return result;
        },
        badgeAddons() {
            // Todo: Filter by badge context
            const badgeId = typeof this.badges[this.selectedbadge] === 'undefined' ? '' : this.badges[this.selectedbadge].id.toString();
            // Do we have questions at all for this badge?
            if (!(badgeId in this.addonsAvailable)) return {};
            // Filter out the ones that don't apply to this badge
            let result = this.addonsAvailable[badgeId];

            // First, do we have a date_of_birth?
            // const bday = new Date(this.date_of_birth);
            // if (this.date_of_birth && bday) {
            //     result = result.filter((badge) => {
            //         if (!(
            //                 (badge['min-birthdate'] != null && bday < new Date(badge['min-birthdate'])) ||
            //                 (badge['max-birthdate'] != null && bday > new Date(badge['max-birthdate']))
            //             )) {
            //             return badge;
            //         }
            //     });
            // }
            /// /Apply logic to required
            // result.forEach(function(question){
            //  question.isRequired = question.required == '*' || question.required.includes(badgeId)
            // })

            // Sort it out
            result.sort((a, b) => a.order - b.order);
            return result;
        },
        applicationStatusList() {
            return Object.values(this.applicationStatusMap);
        },
        applicationStatusData() {
            return this.applicationStatusMap[this.model.application_status] || {};
        },
        appStatusNextList() {
            if (this.applicationStatusData != undefined && this.applicationStatusData.nextStatus != undefined) {

                var result = this.applicationStatusData.nextStatus
                    .map((statusKey) => this.applicationStatusMap[statusKey]);

                result.unshift({
                    value: null,
                    color: 'primary',
                    text: 'Just save',
                    actionText: 'Keep the same status',
                    nextStatus: []
                });
                return result;
            }
            return [];
        },
        appStatusNext() {
            if (this.newApplication_status && this.applicationStatusMap[this.newApplication_status]) {
                return this.applicationStatusMap[this.newApplication_status];
            }
            return {
                value: null,
                color: 'primary',
                text: 'Just save',
                actionText: 'Keep the same status',
                nextStatus: []
            }
        },
    },
    watch: {
        'badgeGenInfoData.date_of_birth': function() {
            this.checkBadge();
        },
        selectedBadge(val) {
            //Check if we can
            var newId = typeof this.badges[val] === 'undefined' ? this.model.badge_type_id : this.badges[val].id;
            if (newId != null) {
                this.model.badge_type_id = newId;
            }

        },
        value(newValue) {
            //Check if we have a certain minimum things needed
            if (newValue.context_code != undefined) {

                this.loadBadge(newValue);
            }
        },
        model: {

            handler(newBadgeData, oldBadgeData) {
                //this.skipEmitOnce = true;
                // console.log('Emitting')
                // this.$emit('input', JSON.parse(JSON.stringify(this.compiledBadge));
                // return;
                var same = JSON.stringify(this.compiledBadge) == this.modelString;
                // console.log('ad badge mod', {
                //     new: JSON.stringify(this.compiledBadge),
                //     old: this.modelString,
                //     same: same
                // })
                if (same) {
                    this.skipEmitOnce = false;
                    //console.log('skipping pre-skip, catching next update')

                } else {

                    this.modelString = JSON.stringify(this.compiledBadge);
                    //console.log('and we want parent to know')
                    this.skipEmitOnce = true;
                    this.$emit('input', JSON.parse(this.modelString));
                }
            },
            deep: true
        },
    },
    methods: {

        saveBDay(date) {
            this.$refs.menuBDay.save(date);
            this.date_of_birth = this.date_of_birth;
        },
        async loadBadge(badgeData) {
            var same = JSON.stringify(badgeData) == this.modelString;
            if (same) return;
            //Are we already loading/emitting?
            // if (this.skipEmitOnce == true) {
            //     console.log('but not actually loading here because skipEmitOnce')
            //     this.skipEmitOnce = false;
            //     return;
            // }

            let cartItem = JSON.parse(JSON.stringify(badgeData));
            console.log('load a badge')
            this.skipEmitOnce = true;
            let badge_type_id = -1;

            //If nothing loaded,  early exit
            if (cartItem != undefined) {
                // Pull out the BadgeId and selected addons
                badge_type_id = cartItem.badge_type_id || 0;
                let addons = cartItem.addons || [];
                // delete cartItem.badge_type_id;
                //Import the general badge info
                cartItem.badgeGenInfoData = {
                    real_name: cartItem.real_name,
                    fandom_name: cartItem.fandom_name,
                    name_on_badge: cartItem.name_on_badge,
                    date_of_birth: cartItem.date_of_birth,
                };
                //Object.assign(this.model, cartItem);
                //this.model = cartItem;
                this.$set(this, 'model', cartItem);
                // Special props

                await this.$store.dispatch('products/selectContext', this.model.context_code);
                this.skipEmitOnce = true;

                this.checkBadge();
                this.addonsSelected = addons.map(addon => addon['addon_id']);
                // setTimeout(async () => {
                //     console.log('yah')
                //     const newIndex = _this.badges.findIndex((badge) => badge.id == badge_type_id);
                //     if (newIndex > -1) {
                //         _this.selectedbadge = newIndex;
                //     }
                //     //Also select any selected addons
                //     _this.model.addonsSelected = addons.map(addon => addon['addon_id']);
                //
                // }, 1200);
            }


        },
        checkBadge() {
            // Ensure only applicable badges are selected!
            if (this.badges.length > 0) {
                const bid = this.model.badge_type_id;
                let badge = this.badges.findIndex((badge) => badge.id == bid);
                if (badge == -1) badge = 0;
                this.selectedbadge = badge;
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
        badgeAddonPriorSelected(addonid) {
            if (this.model.editBadgePriorAddons == undefined) return false;
            return this.model.editBadgePriorAddons.indexOf(addonid) != -1;
        },
        setValidGenInfo(isValid) {
            this.validGenInfo = isValid;
        },
        submitReview: function(newStatus) {
            if (newStatus != null)
                this.model.application_status = newStatus;
            console.log('submitting review', this.model.application_status);
            var that = this;
            this.$nextTick(function() {
                that.$emit('save', that.sendUpdate);
            })


        },
    },
    components: {
        badgeGenInfo,
        badgeTypeSelector,
        formQuestions,
        badgePerksRender,
        profileForm,
        paymentItemView,
        editBadgeApplicationStaffPosition
    },
    created() {
        this.loadBadge(this.value);
    },
};
</script>
