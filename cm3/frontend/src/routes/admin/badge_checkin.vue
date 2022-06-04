<template>
<v-container fluid
             class="align-center justify-center"
             fill-height>
    <v-stepper v-model="checkinStage">
        <v-stepper-header>
            <v-stepper-step :complete="checkinStage > 1"
                            step="1">
                Find Badge
            </v-stepper-step>

            <v-divider></v-divider>

            <v-stepper-step :complete="checkinStage > 2"
                            step="2">
                Verify badge holder
            </v-stepper-step>

            <v-divider></v-divider>

            <v-stepper-step :complete="checkinStage > 3"
                            step="3">
                Pay
            </v-stepper-step>
            <v-divider></v-divider>

            <v-stepper-step step="4">
                Finish Check-in
            </v-stepper-step>
        </v-stepper-header>

        <v-stepper-items>
            <v-stepper-content step="1">
                <badgeSearchList apiPath="Badge/CheckIn"
                                 :actions="[{name:'select',text:'Select'}]"
                                 @select="selectBadge" />
            </v-stepper-content>

            <v-stepper-content step="2">
                <v-row>
                    <v-col cols="12">
                        <v-card outline
                                class="mb-12 elevation-10">
                            <badgeSampleRender :badge="selectedBadge" />
                            <v-card-actions>
                                {{selectedBadge['badge_type_name']}}

                                <v-spacer></v-spacer>{{selectedBadge.context_code}}{{selectedBadge.display_id}}
                                <v-btn icon
                                       @click="editingBadge = true">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-col>

                    <v-col cols="6">
                        <v-text-field label="Date of Birth"
                                      :readonly="!editingBadge"
                                      :value="selectedBadge.date_of_birth"></v-text-field>
                    </v-col>
                    <v-col cols="6">
                        <v-text-field label="SmartHealth QR Data"
                                      v-model="SmartHealthData"></v-text-field>
                    </v-col>
                    <v-col>
                        <v-textarea label="Notes"
                                    rows="3"
                                    readonly
                                    :value="selectedBadge.notes"></v-textarea>
                    </v-col>
                </v-row>
                <v-row>
                    <v-col>
                        <v-card-actions>
                            <v-btn text
                                   @click="selectedBadge = {}">
                                Cancel
                            </v-btn>
                            <v-spacer></v-spacer>
                            <v-btn color="primary"
                                   @click="checkinStage = 3">
                                Verified
                            </v-btn>
                        </v-card-actions>
                    </v-col>
                </v-row>
            </v-stepper-content>

            <v-stepper-content step="3">
                <v-card class="mb-12"
                        color="grey lighten-1"
                        height="200px">Payment required:
                    <h2>{{edit_selectedBadgePayment.total | currency}}</h2>
                </v-card>

                <v-row>
                    <v-col>
                        <v-card-actions>
                            <v-btn text
                                   @click="selectedBadge = {}">
                                Cancel
                            </v-btn>
                            <v-spacer></v-spacer>
                            <v-btn color="primary"
                                   :disabled="paying"
                                   :loading="paying"
                                   @click="ConfirmPayment">
                                Paid
                            </v-btn>
                        </v-card-actions>
                    </v-col>
                </v-row>
            </v-stepper-content>
            <v-stepper-content step="4">
                <badgeFullRender :badge="selectedBadge" />

                <v-btn color="green"
                       :disabled="printing"
                       :loading="printing"
                       @click="ExecutePrint">
                    {{selectedBadge.time_printed != null ? "(Re)" : ""}}Print Badge
                </v-btn>

                <v-row>
                    <v-col>
                        <v-card-actions>
                            <v-btn text
                                   @click="selectedBadge = {}">
                                Cancel
                            </v-btn>
                            <v-spacer></v-spacer>
                            <v-btn color="primary"
                                   :disabled="finishing"
                                   :loading="finishing"
                                   @click="FinishCheckIn">
                                Finish
                            </v-btn>
                        </v-card-actions>
                    </v-col>
                </v-row>
            </v-stepper-content>
        </v-stepper-items>
    </v-stepper>
    <v-dialog v-model="editingBadge"
              persistent
              max-width="600px">
        <v-card>
            <v-card-title>
                <span class="text-h5">Edit Badge</span>
            </v-card-title>
            <v-card-text>
                <v-container>
                    <v-row>
                        <v-col cols="12"
                               md="6">
                            <v-text-field v-model="edit_real_name"
                                          :counter="500"
                                          :rules="rules.Name"
                                          label="Real Name"
                                          required></v-text-field>
                        </v-col>

                        <v-col cols="12"
                               md="6">
                            <v-text-field v-model="edit_fandom_name"
                                          :counter="255"
                                          :rules="rules.NameFandom"
                                          label="Fandom Name (Optional)"></v-text-field>
                        </v-col>
                        <v-col cols="12">
                            <v-select v-if="edit_fandom_name"
                                      v-model="edit_name_on_badge"
                                      :items="edit_name_on_badgeType"
                                      label="Display on badge"></v-select>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12"
                               md="6">
                            <v-menu ref="menuBDay"
                                    v-model="edit_menuBDay"
                                    :close-on-content-click="false"
                                    transition="scale-transition"
                                    offset-y
                                    min-width="290px">
                                <template v-slot:activator="{ on }">
                                    <v-text-field v-model="edit_date_of_birth"
                                                  type="date"
                                                  label="Date of Birth"
                                                  v-on="on"
                                                  :rules="rules.Required"></v-text-field>
                                </template>
                                <v-date-picker ref="pickerBDay"
                                               v-model="edit_date_of_birth"
                                               :max="new Date().toISOString().substr(0, 10)"
                                               min="1920-01-01"
                                               @change="saveBDay"
                                               :active-picker.sync="edit_bdayActivePicker"></v-date-picker>
                            </v-menu>
                        </v-col>
                    </v-row>
                    <v-row v-if="selectedBadge.context_code=='A'">
                        <v-select v-model="edit_selectedBadge"
                                  :items="badges"
                                  label="Select Badge"
                                  :item-value="edit_badgeIndex"
                                  :item-disabled="quantityZero"
                                  :class="{warning:isProbablyDowngrading}">
                            <template v-slot:item="{item}">
                                {{ item.name }}
                                <v-spacer></v-spacer>
                                <b v-if="item.quantity_remaining"> {{item.quantity_remaining}} Left </b>
                                <b v-else-if="item.quantity_remaining == 0">Sold out!</b> &nbsp;
                                <v-btn color="green"
                                       dark>{{item.price | currency}}</v-btn>
                            </template>
                            <template v-slot:selection="{ item }">
                                {{ item.name }}
                                <v-spacer></v-spacer>
                                <b v-if="item.quantity_remaining"> {{item.quantity_remaining}} Left </b>
                                <b v-else-if="item.quantity_remaining == 0">Sold out!</b> &nbsp;
                                <v-btn color="green"
                                       dark>{{item.price | currency}}</v-btn>
                            </template>
                        </v-select>
                    </v-row>
                    <v-col>
                        <v-textarea label="Notes"
                                    rows="3"
                                    :value="edit_notes"></v-textarea>

                    </v-col>
                </v-container>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="blue darken-1"
                       text
                       @click="editingBadge = false">
                    Cancel
                </v-btn>
                <v-btn color="blue darken-1"
                       :disabled="savingEditedBadge"
                       :loading="savingEditedBadge"
                       @click="updateSelectedBadge">
                    Save
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog v-model="alreadyCheckedInDialog"
              persistent
              max-width="290">
        <v-card>
            <v-card-title class="text-h5">
                Already Checked-In!
            </v-card-title>
            <v-card-text>This badge is already checked in, continue?</v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary"
                       @click="selectedBadge = {}">
                    Cancel
                </v-btn>
                <v-btn color="warning darken-1"
                       @click="alreadyCheckedInDialog = false">
                    Continue Checkin
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</v-container>
</template>
<script>
import {
    mapState,
    mapGetters,
    mapActions
} from 'vuex';
import admin from '../../api/admin';
import {
    debounce
} from '@/plugins/debounce';
import badgeSampleRender from '@/components/badgeSampleRender.vue';
import badgeFullRender from '@/components/badgeFullRender.vue';
import badgeSearchList from '@/components/badgeSearchList.vue';

export default {
    components: {
        badgeSampleRender,
        badgeFullRender,
        badgeSearchList
    },
    data: () => ({
        checkinStage: 1,
        selectedBadge: {},
        alreadyCheckedInDialog: false,
        editingBadge: false,
        edit_real_name: '',
        edit_fandom_name: '',
        edit_name_on_badge: 'Real Name Only',
        edit_name_on_badgeType: ['Fandom Name Large, Real Name Small', 'Real Name Large, Fandom Name Small', 'Real Name Only', 'Fandom Name Only'],
        edit_date_of_birth: null,
        edit_bdayActivePicker: 'YEAR',
        edit_selectedBadge: null,
        edit_selectedBadgePayment: {},
        edit_notes: "",
        edit_menuBDay: false,
        savingEditedBadge: false,
        paying: false,
        loadpaying: false,
        printing: false,
        finishing: false,

        SmartHealthData: "",
        rules: {
            Required: [
                (v) => !!v || 'Required',
            ],
            Name: [
                (v) => !!v || 'Name is required',
                (v) => (v && v.length <= 500) || 'Name must be less than 500 characters',
            ],
            NameFandom: [
                (v) => (v == '' || (v && v.length <= 255)) || 'Name must be less than 255 characters',
            ],
        },
    }),
    computed: {
        headers: () => {

            return [{
                    text: 'ID',
                    align: 'start',
                    sortable: false,
                    value: 'id',
                },
                {
                    text: 'Real Name',
                    value: 'real_name',
                },
                {
                    text: 'Fandom Name',
                    value: 'fandom_name',
                },
                {
                    text: 'Badge Type',
                    value: 'badge_type_name',
                },
                {
                    text: 'Application Status',
                    value: 'application_status',
                },
                {
                    text: 'Payment Status',
                    value: 'payment_status',
                },
                {
                    text: 'Printed',
                    value: 'time_printed',
                },
                {
                    text: 'Checked-In',
                    value: 'time_checked_in',
                },
                {
                    text: 'Select',
                    value: 'uuid',
                },
            ];
        },
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },

        ...mapGetters('products', {
            badgeContexts: 'badgeContexts',
            currentContext: 'selectedbadgecontext',
            products: 'contextBadges',
            questions: 'contextQuestions',
            addonsAvailable: 'contextAddons',
        }),
        isProbablyDowngrading() {
            if (!this.editingBadge) {
                return false;
            }

            const oldBadge = this.badges.find((badge) => badge.id == this.selectedBadge.badge_type_id);
            const selectedBadge = this.badges[this.edit_selectedBadge];
            return typeof oldBadge !== 'undefined' &&
                typeof selectedBadge !== 'undefined' &&
                parseFloat(oldBadge.originalprice) > parseFloat(selectedBadge.originalprice);
        },
        badges() {
            // Crude clone
            if (this.products == undefined) return [];
            let badges = JSON.parse(JSON.stringify(this.products));
            // First, do we have a date_of_birth?
            const bday = new Date(this.edit_date_of_birth);
            if (this.edit_date_of_birth && bday) {
                badges = badges.filter((badge) => {
                    if (!(
                            (badge['min_birthdate'] != null && bday < new Date(badge['min_birthdate'])) ||
                            (badge['max_birthdate'] != null && bday > new Date(badge['max_birthdate']))
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
    },
    methods: {
        checkPermission: () => {
            console.log('Hey! Listen!');
        },
        doSearch: function() {
            console.log("Need to refresh the badge list table");
        },
        selectBadge: function(item) {
            this.selectedBadge = item;
        },
        loadSelectedBadge: async function() {
            if (this.selectedBadge.id == undefined) return;
            await this.$store.dispatch('products/selectContext', this.selectedBadge.context_code);
            admin.badgeCheckinFetch(this.authToken, this.selectedBadge.context_code, this.selectedBadge.id, (results) => {
                this.selectedBadge = results;
            })
        },
        updateSelectedBadge: function() {
            if (!this.editingBadge) return;
            this.savingEditedBadge = true;
            admin.badgeCheckinSave(this.authToken, {
                context_code: this.selectedBadge.context_code,
                id: this.selectedBadge.id,
                real_name: this.edit_real_name,
                fandom_name: this.edit_fandom_name,
                name_on_badge: this.edit_name_on_badge,
                date_of_birth: this.edit_date_of_birth,
                badge_type_id: this.edit_selectedBadge > -1 ? this.badges[this.edit_selectedBadge].id : this.selectedBadge.badge_type_id,
                notes: this.edit_notes
            }, (result) => {
                this.savingEditedBadge = false;
                this.editingBadge = false;
                this.selectedBadge = result;
            }, (failure) => {
                this.savingEditedBadge = false;
            });

        },
        RefreshPayment: function() {
            if (this.selectedBadge.id == undefined) return;
            this.loadpaying = true;
            admin.badgeCheckinGetPayment(this.authToken, this.selectedBadge.context_code, this.selectedBadge.id, (result) => {
                this.edit_selectedBadgePayment = result;
                this.loadpaying = false;

            }, (error) => {
                this.loadpaying = false;
            })
        },
        ConfirmPayment: function() {
            if (this.selectedBadge.id == undefined) return;
            this.paying = true;
            admin.badgeCheckinConfirmPayment(this.authToken, this.selectedBadge.context_code, this.selectedBadge.id, {
                payment_system: 'Cash'
            }, (results) => {
                this.checkinStage = 4;
                this.paying = false;

            }, (error) => {
                this.paying = false;
            })
        },
        ExecutePrint: function() {
            if (this.selectedBadge.id == undefined) return;
            this.printing = true;
        },
        FinishCheckIn: function() {
            if (this.selectedBadge.id == undefined) return;
            this.finishing = true;
            admin.badgeCheckinFinish(this.authToken, this.selectedBadge.context_code, this.selectedBadge.id, (results) => {
                this.selectedBadge = {};
                this.finishing = false;
                this.doSearch();
            }, (error) => {
                this.finishing = false;
            })
        },


        edit_badgeIndex(item) {
            return this.badges.indexOf(item);
        },
        quantityZero(item) {
            return item.quantity == 0;
        },
        saveBDay(date) {
            this.$refs.menuBDay.save(date);
            this.edit_date_of_birth = this.edit_date_of_birth;
        },
    },
    watch: {
        $route() {
            this.$nextTick(this.checkPermission);
        },
        selectedBadge: function(sb) {
            if (this.checkinStage < 2 && sb.id != undefined) {
                this.checkinStage = 2;
                this.loadSelectedBadge();
                if (this.selectedBadge.time_checked_in != null)
                    this.alreadyCheckedInDialog = true;
            } else if (this.checkinStage > 1 && sb.id == undefined) {
                this.checkinStage = 1;
                this.alreadyCheckedInDialog = false;
                this.printing = false;
                this.doSearch();
            }
        },
        editingBadge: function(isEditing) {
            if (isEditing) {
                //Load up the current data into the edit form
                this.edit_real_name = this.selectedBadge.real_name;
                this.edit_fandom_name = this.selectedBadge.fandom_name;
                this.edit_name_on_badge = this.selectedBadge.name_on_badge;
                this.edit_date_of_birth = this.selectedBadge.date_of_birth;
                this.notes = this.selectedBadge.notes;
                this.edit_bdayActivePicker = 'YEAR';
                this.edit_selectedBadge = this.badges.findIndex((badge) => badge.id == this.selectedBadge.badge_type_id);
            }
        },
        checkinStage: function(stage) {
            if (stage == 3) {
                //If they're paid, just go straight to Finish
                if (this.selectedBadge.payment_status == "Completed") {
                    this.checkinStage = 4;
                } else {
                    //Fetch the current payment for this badge
                    this.RefreshPayment();
                }
            }
        },
        menuBDay(val) {
            // Whenever opening the picker, always reset it back to start with the Year
            val && setTimeout(() => (this.edit_bdayActivePicker = 'YEAR'));
        },
    },
    created() {
        this.checkPermission();
        //this.doSearch();
    }
};
</script>
