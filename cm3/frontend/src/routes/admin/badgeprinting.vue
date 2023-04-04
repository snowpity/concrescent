<template>
<v-tabs-items :value="subTabIx"
              touchless>
    <v-tab-item value="BadgeFormats">
        <simpleList apiPath="Badge/Format"
                    :AddHeaders="listAddHeaders"
                    :RemoveHeaders="listRemoveHeaders"
                    :isEditingItem="fEdit"
                    :actions="listActions"
                    :footerActions="btFooterActions"
                    @edit="editBadgeFormat"
                    @create="createBadgeFormat" />

        <v-dialog v-model="fEdit"
                  fullscreen
                  scrollable
                  hide-overlay>
            <v-card>
                <v-card-title class="pa-0">
                    <v-toolbar dark
                               flat
                               color="primary">
                        <v-btn icon
                               dark
                               @click="fEdit = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <v-toolbar-title>Edit Badge Format</v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-toolbar-items>
                            <v-btn color="primary"
                                   dark
                                   @click="saveBadgeFormat()">
                                <v-icon>mdi-content-save</v-icon>
                            </v-btn>
                        </v-toolbar-items>
                    </v-toolbar>
                </v-card-title>
                <v-card-text class="pa-0">
                    <badgeFormatEditor v-model="fSelected" />
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-tab-item>
    <v-tab-item value="Print">

        <v-stepper non-linear
                   v-model="printStage">
            <v-stepper-header>
                <v-stepper-step step="1"
                                :complete='fSelected.id != undefined'
                                edit-icon="$vuetify.icons.complete"
                                editable>
                    Select format
                </v-stepper-step>

                <v-divider></v-divider>

                <v-stepper-step step="2"
                                edit-icon="$vuetify.icons.complete"
                                :editable='fSelected.id != undefined'>
                    Select badges
                </v-stepper-step>

                <v-divider></v-divider>

                <v-stepper-step step="3"
                                editable>
                    Print
                </v-stepper-step>
            </v-stepper-header>

            <v-stepper-items>
                <v-stepper-content step="1">
                    <simpleList apiPath="Badge/Format"
                                :AddHeaders="listAddHeaders"
                                :RemoveHeaders="listRemoveHeaders"
                                :actions="printActions"
                                @select="selectBadgeFormat" />
                </v-stepper-content>
                <v-stepper-content step="2">
                    <badgeSearchList v-if="fSelected"
                                     :apiPath="'Badge/Format/' + fSelected.id + '/Badges'"
                                     internalKey="uuid"
                                     :AddHeaders="badgeSelectHeaders"
                                     :RemoveHeaders="badgeSelectRemoveHeaders"
                                     :actions="ptActions"
                                     :footerActions="ptFooterActions"
                                     @addOne="enqueueBadgeForPrinting" />
                </v-stepper-content>
            </v-stepper-items>
        </v-stepper>
    </v-tab-item>
    <v-tab-item value="Queue">
        <v-container>
            <v-row>
                <v-col>
                    <v-select v-model="queueStateSearch"
                              :items="queueStates"
                              label="Jobs with Status" />
                </v-col>
            </v-row>
        </v-container>
        <simpleList apiPath="Badge/PrintJob"
                    :apiAddParams="{expandMeta:true, state:queueStateSearch}"
                    :AddHeaders="queueAddHeaders"
                    :RemoveHeaders="queueRemoveHeaders"
                    :isEditingItem="jEdit"
                    :actions="listActions"
                    @edit="editPrintJob" />

        <v-dialog v-model="jEdit"
                  fullscreen
                  scrollable
                  hide-overlay>
            <v-card v-if="jEdit">
                <v-card-title class="pa-0">
                    <v-toolbar dark
                               flat
                               color="primary">
                        <v-btn icon
                               dark
                               @click="jEdit = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <v-toolbar-title>Edit Print Job</v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-toolbar-items>
                            <v-btn color="primary"
                                   dark
                                   @click="savePrintJob()">
                                <v-icon>mdi-content-save</v-icon>
                            </v-btn>
                        </v-toolbar-items>
                    </v-toolbar>
                </v-card-title>
                <v-card-text class="pa-0">
                    <v-select v-model="jSelected.state"
                              :items="jPrintStates"
                              label="Status" />
                    <v-card height="200">
                        <scaleToParent>
                            <v-sheet color="white"
                                     class="mx-auto mt-3"
                                     elevation="4">
                                <badgeFullRender :format="jSelected.format"
                                                 :badge="jSelected.data" />
                            </v-sheet>
                        </scaleToParent>
                    </v-card>
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-tab-item>

    <v-dialog v-model="loading"
              width="200"
              height="200"
              close-delay="1200"
              content-class="elevation-0"
              persistent>
        <v-card-text class="text-center overflow-hidden">
            <v-progress-circular :size="150"
                                 class="mb-0"
                                 indeterminate />
        </v-card-text>
    </v-dialog>
</v-tabs-items>
<!-- <v-container fluid
             fill-height>

    <v-row>
        <v-col align-self="start">
        </v-col>
    </v-row>
</v-container> -->
</template>
<script>
import {
    mapActions
} from 'vuex';
import admin from '../../api/admin';
import {
    debounce
} from '@/plugins/debounce';
import badgeSearchList from '@/components/badgeSearchList.vue';
import simpleList from '@/components/simpleList.vue';
import badgeFormatEditor from '@/components/badgeFormatEditor.vue';
import badgeFullRender from '@/components/badgeFullRender.vue';
import scaleToParent from '@/components/formatpieces/scaleToParent.vue';

export default {
    components: {
        badgeSearchList,
        simpleList,
        badgeFormatEditor,
        badgeFullRender,
        scaleToParent
    },
    props: [
        'subTabIx'
    ],
    data: () => ({
        listRemoveHeaders: [
            'time_checked_in'
        ],
        listAddHeaders: [{
            text: 'Format Name',
            value: 'name'
        }, {
            text: 'Size',
            value: 'customSize'
        }, {
            text: 'Background',
            value: 'bgImageID'
        }],
        fSelected: {},
        fEdit: false,

        printStage: 1,
        badgeSelectHeaders: [

        ],
        badgeSelectRemoveHeaders: [
            'contact_email_address'
        ],
        printTypes: [],
        printQueue: [],


        queueStateSearch: '',
        queueRemoveHeaders: [
            'time_checked_in'
        ],
        queueAddHeaders: [{
            text: 'Format Name',
            value: 'name'
        }, {
            text: 'Station Name',
            value: 'stationName'
        }, {
            text: 'Status',
            value: 'state'
        }],
        jfSelected: {},
        jEdit: false,
        jPrintStates: [
            'Queued',
            'Held',
            'Reserved',
            'InProgress',
            'Completed',
            'Batch',
            'Cancelling',
            'Cancelled'
        ],

        loading: false,
        createError: '',
    }),
    computed: {
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        listActions: function() {
            var result = [];
            //TODO: Detect permissions
            result.push({
                name: "edit",
                text: "Edit"
            });
            return result;
        },
        btActions: function() {
            var result = [];
            result.push({
                name: 'edit',
                text: 'Edit',
                icon: 'edit-pencil'
            });
            return result;
        },
        btFooterActions: function() {
            var result = [];
            result.push({
                name: 'create',
                text: 'Add',
                icon: 'plus'
            });
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
        printActions: function() {
            var result = [];
            //TODO: Detect permissions
            result.push({
                name: "select",
                text: "Select"
            });
            return result;
        },
        ptActions: function() {
            var result = [];
            //TODO: Detect permissions
            result.push({
                name: "addOne",
                text: "Enqueue"
            });
            return result;
        },
        ptFooterActions: function() {
            var result = [];
            result.push({
                name: 'addAll',
                text: 'Add All',
                icon: 'select-all'
            }, {
                name: 'addUnprinted',
                text: 'Add All Unprinted',
                icon: 'selection-multiple'
            });
            return result;
        },
        queueStates: function() {
            return ['', ...this.jPrintStates];
        }
    },
    methods: {
        checkPermission: () => {
            console.log('Hey! Listen!');
        },
        editBadgeFormat: function(selectedFormat) {
            console.log('edit badge selected from grid', selectedFormat);
            let that = this;
            that.loading = true;
            that.fSelected = {};
            admin.genericGet(this.authToken, 'Badge/Format/' + selectedFormat.id, null, function(editFormat) {
                that.fSelected = editFormat;
                that.loading = false;
                that.fEdit = true;
                that.$nextTick(() => {
                    that.fModified = false;
                })

            }, function() {
                that.loading = false;
            })
        },
        saveBadgeFormat: function() {
            console.log('saving badge', this.fSelected);
            var url = 'Badge/Format';
            if (this.fSelected.id != undefined)
                url = url + '/' + this.fSelected.id;
            let that = this;
            that.loading = true;
            admin.genericPost(this.authToken, url, this.fSelected, function(SavedDetails) {
                that.fSelected = {};
                that.loading = false;
                that.fEdit = false;
                that.fSaved = true;
                that.fSavedDetails = SavedDetails;
                that.$nextTick(() => {
                    that.bModified = false;
                })

            }, function() {
                that.loading = false;
            })
        },
        createBadgeFormat: function() {
            this.fEdit = true;
            this.fSelected = {};
        },

        selectBadgeFormat: function(selectedFormat) {
            console.log('selected badge for print from grid', selectedFormat);
            this.loading = true;
            this.fSelected = {};
            console.log('fetching format', selectedFormat)
            admin.genericGet(this.authToken, 'Badge/Format/' + selectedFormat.id, null, (editFormat) => {
                this.fSelected = editFormat;
                this.loading = false;
                this.printStage = 2;

            }, function() {
                this.loading = false;
            })
        },
        enqueueBadgeForPrinting: function(selectedBadge) {
            console.log('enqueue single badge for batch print from grid', selectedBadge);
            this.loading = true;
            admin.genericPost(this.authToken, 'Badge/Format/' + this.fSelected.id + '/Badges/' + selectedBadge.context_code + '/' + selectedBadge.id, {
                    state: 'Batch',
                    meta: {
                        stationName: this.$store.state.station.servicePrintJobsAs || 'Batch'
                    }
                },
                (result) => {
                    this.loading = false;
                }, (err) => {
                    this.loading = false;
                })
        },

        editPrintJob: function(selectedFormat) {
            console.log('edit print job from grid', selectedFormat);
            this.loading = true;
            this.jSelected = {};
            admin.genericGet(this.authToken, 'Badge/PrintJob/' + selectedFormat.id, {
                includeFormat: true
            }, (editPrintJob) => {
                this.jSelected = editPrintJob;
                this.loading = false;
                this.jEdit = true;
            }, () => {
                this.loading = false;
            })
        },

        savePrintJob: function() {
            console.log('saving badge', this.jSelected);
            var url = 'Badge/PrintJob';
            if (this.jSelected.id != undefined)
                url = url + '/' + this.jSelected.id;
            let that = this;
            that.loading = true;
            admin.genericPost(this.authToken, url, this.jSelected, function(SavedDetails) {
                that.jSelected = {};
                that.loading = false;
                that.jEdit = false;
            }, function() {
                that.loading = false;
            })
        },

    },
    watch: {
        $route() {
            this.$nextTick(this.checkPermission);
        },

    },
    created() {
        this.checkPermission();
        //this.doSearch();
        this.$emit('updateSubTabs', [{
                key: 'BadgeFormats',
                text: 'Badge Formats',
                title: 'Badge Formats'
            },
            {
                key: 'Print',
                text: 'Pre-Printing',
                title: 'Badge Pre-Printing'
            },
            {
                key: 'Queue',
                text: 'Printing Queue',
                title: 'Printing Queue'
            },
        ]);
    }
};
</script>
