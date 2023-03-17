<template>
<v-tabs-items :value="subTabIx"
              touchless>
    <v-tab-item value="BadgeFormats">
        <simpleList apiPath="Badge/Format"
                    :AddHeaders="listAddHeaders"
                    :RemoveHeaders="listRemoveHeaders"
                    :isEditingItem="bEdit || bPrint"
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
    <v-tab-item value="1">
        <orderableList apiPath="Staff/BadgeType"
                       :AddHeaders="btAddHeaders"
                       :actions="btActions"
                       :footerActions="btFooterActions"
                       :isEditingItem="btDialog"
                       @edit="editBadgeType"
                       @create="createBadgeType" />

        <v-dialog v-model="btDialog"
                  scrollable
                  persistent>

            <v-card>
                <v-card-title class="headline">Edit Badge Type</v-card-title>
                <v-card-text>

                    <badgeTypeForm v-model="btSelected" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="default"
                           @click="btDialog = false">Cancel</v-btn>
                    <v-btn color="primary"
                           @click="saveBadgeType">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-tab-item>
    <v-tab-item value="2">
        <formQuestionEditList context_code="S" />
    </v-tab-item>
    <v-tab-item value="3">
        <treeList apiPath="Staff/Department"
                  :AddHeaders="dAddHeaders"
                  :actions="btActions"
                  :footerActions="btFooterActions"
                  :isEditingItem="dDialog"
                  @edit="editDepartment"
                  @create="createDepartment" />
        <v-dialog v-model="dDialog"
                  scrollable
                  persistent>

            <v-card>
                <v-card-title class="headline">Edit Department</v-card-title>
                <v-card-text>

                    <editDepartment v-model="dSelected" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="default"
                           @click="dDialog = false">Cancel</v-btn>
                    <v-btn color="primary"
                           @click="saveDepartment">Save</v-btn>
                </v-card-actions>
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
        badgeTypeForm,
        formQuestionEditList,
        treeList,
        //editBadgeAdmin,
        editDepartment
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

        bSelected: {},
        bEdit: false,
        bModified: false,
        bSaved: false,
        bSavedDetails: {},
        bPrint: false,
        btAddHeaders: [{
            text: 'Dates Available',
            value: 'dates_available'
        }, {
            text: 'Price',
            value: 'price'
        }, {
            text: 'Active',
            value: 'active'
        }],
        btSelected: {},
        btDialog: false,

        dAddHeaders: [{
            text: 'Name',
            value: 'name'
        }, {
            text: 'Email',
            value: 'email_primary'
        }, {
            text: 'Active',
            value: 'active'
        }],
        dDialog: false,
        dSelected: {},

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
            let that = this;
            that.loading = true;
            admin.genericPost(this.authToken, 'Badge/Format/' + this.fSelected.id, this.fSelected, function(SavedDetails) {
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
        createBadgeType: function() {
            this.btDialog = true;
            this.btSelected = {};
        },
        editBadgeType: function(selectedBadgeType) {
            this.loading = true;
            this.btDialog = true;
            var that = this;
            admin.genericGet(this.authToken, 'Staff/BadgeType/' + selectedBadgeType.id, null, function(editBt) {

                that.btSelected = editBt;
                that.loading = false;
            }, function() {
                that.loading = false;
            })
        },
        saveBadgeType: function() {
            var url = 'Staff/BadgeType';
            if (this.btSelected.id != undefined)
                url = url + '/' + this.btSelected.id;
            console.log("Saving badge type", this.btSelected)
            var that = this;
            admin.genericPost(this.authToken, url, this.btSelected, function(editBt) {

                that.btSelected = editBt;
                that.loading = false;
                that.btDialog = false;
            }, function() {
                that.loading = false;
            })
        },
        createDepartment: function() {
            this.dDialog = true;
            this.dSelected = {};
        },
        editDepartment: function(selectedDepartment) {
            this.loading = true;
            var that = this;
            admin.genericGet(this.authToken, 'Staff/Department/' + selectedDepartment.id, null, function(editBt) {

                that.dSelected = editBt;
                that.dDialog = true;
                that.loading = false;
            }, function() {
                that.loading = false;
            })
        },
        saveDepartment: function() {
            var url = 'Staff/Department';
            if (this.dSelected.id != undefined)
                url = url + '/' + this.dSelected.id;
            console.log("Saving badge type", this.dSelected)
            var that = this;
            admin.genericPost(this.authToken, url, this.dSelected, function(editBt) {

                //that.dSelected = editBt;
                that.loading = false;
                that.dDialog = false;
            }, function() {
                that.loading = false;
            })
        },
    },
    watch: {
        $route() {
            this.$nextTick(this.checkPermission);
        },
        bSelected(newBadgeData) {
            this.bModified = true;
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
