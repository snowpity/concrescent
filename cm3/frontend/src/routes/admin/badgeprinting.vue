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

        <v-stepper v-model="printStage">
            <v-stepper-header>
                <v-stepper-step step="1">
                    Select format
                </v-stepper-step>

                <v-divider></v-divider>

                <v-stepper-step step="2">
                    Select badges
                </v-stepper-step>

                <v-divider></v-divider>

                <v-stepper-step step="3">
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
                    <simpleList :apiPath="'Badge/Format/' + fSelected.id + '/Badges'"
                                internalKey="uuid"
                                :AddHeaders="listAddHeaders"
                                :RemoveHeaders="listRemoveHeaders"
                                :actions="ptActions"
                                :footerActions="ptFooterActions"
                                @select="selectBadgeFormat" />
                </v-stepper-content>
            </v-stepper-items>
        </v-stepper>
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
import simpleList from '@/components/simpleList.vue';
import badgeFormatEditor from '@/components/badgeFormatEditor.vue';
import badgeTypeForm from '@/components/badgeTypeForm.vue';
import formQuestionEditList from '@/components/formQuestionEditList.vue';
import treeList from '@/components/treeList.vue';
import editBadgeAdmin from '@/components/editBadgeAdmin.vue';
import editDepartment from '@/components/editDepartment.vue';

export default {
    components: {
        simpleList,
        badgeFormatEditor,
        // badgeTypeForm,
        // formQuestionEditList,
        // treeList,
        //editBadgeAdmin,
        // editDepartment
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
        printTypes: [],
        printQueue: [],

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
    },
    methods: {
        checkPermission: () => {
            console.log('Hey! Listen!');
        },
        editBadgeFormat: function(selectedFormat) {
            console.log('edit badge selected from grid', selectedFormat);
            let that = this;
            that.loading = true;
            that.fSelected = null;
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
            this.fSelected = null;
            console.log('fetching format', selectedFormat)
            admin.genericGet(this.authToken, 'Badge/Format/' + selectedFormat.id, null, (editFormat) => {
                this.fSelected = editFormat;
                this.loading = false;
                this.printStage = 2;

            }, function() {
                this.loading = false;
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
                text: 'Printing',
                title: 'Badge Printing'
            },
        ]);
    }
};
</script>
