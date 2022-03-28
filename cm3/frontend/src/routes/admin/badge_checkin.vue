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
                <v-data-table :options.sync="tableOptions"
                              :server-items-length="totalResults"
                              :loading="loading"
                              :headers="headers"
                              multi-sort
                              :items="tableResults"
                              item-key="uuid"
                              class="elevation-1 fill-height"
                              :search="searchText">
                    <template v-slot:top="">
                        <v-text-field v-model="searchText"
                                      label="Search"
                                      class="mx-4"></v-text-field>
                    </template>
                    <template v-slot:[`item.id`]="{ item }">
                        {{item.context_code}}{{item.display_id}}
                    </template>
                    <template v-slot:[`item.uuid`]="{ item }">
                        <v-btn @click="selectedBadge = item">Select</v-btn>
                    </template>
                </v-data-table>
            </v-stepper-content>

            <v-stepper-content step="2">
                <v-row>
                    <v-col cols="12">
                        <v-card outline
                                class="mb-12 elevation-10">
                            <badgeSampleRender :badge="selectedBadge" />
                            <v-card-actions>
                                {{selectedBadge['badge_type_name']}}

                                <v-spacer></v-spacer>
                                <v-btn icon>
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
                        height="200px">PayPal Here</v-card>

                <v-row>
                    <v-col>
                        <v-card-actions>
                            <v-btn text
                                   @click="selectedBadge = {}">
                                Cancel
                            </v-btn>
                            <v-spacer></v-spacer>
                            <v-btn color="primary"
                                   @click="checkinStage = 4">
                                Paid
                            </v-btn>
                        </v-card-actions>
                    </v-col>
                </v-row>
            </v-stepper-content>
            <v-stepper-content step="4">
                <v-card class="mb-12"
                        color="grey lighten-1"
                        height="200px">Finish</v-card>

                <v-row>
                    <v-col>
                        <v-card-actions>
                            <v-btn text
                                   @click="selectedBadge = {}">
                                Cancel
                            </v-btn>
                            <v-spacer></v-spacer>
                            <v-btn color="primary"
                                   @click="selectedBadge = {}">
                                Finish
                            </v-btn>
                        </v-card-actions>
                    </v-col>
                </v-row>
            </v-stepper-content>
        </v-stepper-items>
    </v-stepper>
</v-container>
</template>
<script>
import {
    mapActions
} from 'vuex';
import admin from '../../api/admin';
import {
    debounce
} from '@/plugins/debounce';
import badgeSampleRender from '@/components/badgeSampleRender.vue';

export default {
    components: {
        badgeSampleRender,
    },
    data: () => ({
        checkinStage: 1,
        searchText: "",
        loading: false,
        tableOptions: {},
        tableResults: [],
        totalResults: 0,
        selectedBadge: {},
        editingBadge: false,
        SmartHealthData: "",
        rules: {
            required: value => !!value || 'Required.',
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
                    value: 'time_printed',
                },
                {
                    text: 'Select',
                    value: 'uuid',
                },
            ];
        },
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        }
    },
    methods: {
        checkPermission: () => {
            console.log('Hey! Listen!');
        },
        doSearch: function() {
            this.loading = true;
            const pageOptions = [
                'sortBy',
                'sortDesc',
                'page',
                'itemsPerPage'
            ].reduce((a, e) => (a[e] = this.tableOptions[e], a), {});;
            admin.badgeSearch(this.authToken, this.searchText, pageOptions, (results, total) => {
                this.tableResults = results;
                this.totalResults = total;
                this.loading = false;
            })
        },
        loadSelectedBadge: function() {
            if (this.selectedBadge.id == undefined) return;
            admin.badgeFetch(this.authToken, this.selectedBadge.context_code, this.selectedBadge.id, (results) => {
                this.selectedBadge = results;
            })
        },
    },
    watch: {
        $route() {
            this.$nextTick(this.checkPermission);
        },
        searchText: debounce(function(newSearch) {
            this.doSearch();
        }, 500),
        tableOptions: {
            handler() {
                this.doSearch()
            },
            deep: true,
        },
        selectedBadge: function(sb) {
            if (this.checkinStage < 2 && sb.id != undefined) {
                this.checkinStage = 2;
                this.loadSelectedBadge();
            } else if (this.checkinStage > 1 && sb.id == undefined) {
                this.checkinStage = 1;
            }
        },
        checkinStage: function(stage) {
            if (stage == 3) {
                //If they're paid, just go straight to Finish
                if (this.selectedBadge.payment_status == "Completed") {
                    this.checkinStage = 4;
                }
            }
        }
    },
    created() {
        this.checkPermission();
        //this.doSearch();
    }
};
</script>
