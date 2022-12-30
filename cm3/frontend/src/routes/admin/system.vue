<template>
<v-tabs-items :value="subTabIx"
              touchless>
    <v-tab-item value="0">
        <simpleList apiPath="System/ErrorLog"
                    context_code="S"
                    :AddHeaders="listAddHeaders"
                    :RemoveHeaders="listRemoveHeaders"
                    :isEditingItem="bEdit || bPrint"
                    :actions="listActions"
                    @view="viewErrorLog" />

        <v-dialog v-model="eView"
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
                               @click="eView = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <v-toolbar-title>View Error Log Entry</v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-toolbar-items>
                            <v-btn color="primary"
                                   dark>
                                <v-icon>mdi-export</v-icon>
                            </v-btn>
                        </v-toolbar-items>
                    </v-toolbar>
                </v-card-title>
                <v-card-text class="pa-0">
                    <v-container fluid>
                        <v-row>
                            <v-col cols="2"
                                   sm="2"
                                   md="2">
                                <v-text-field label="Log ID"
                                              v-model="eSelected.id">
                                </v-text-field>
                            </v-col>
                            <v-col cols="2"
                                   sm="2"
                                   md="2">
                                <v-text-field label="Timestamp"
                                              v-model="eSelected.timestamp">
                                </v-text-field>
                            </v-col>
                            <v-col cols="2"
                                   sm="2"
                                   md="2">
                                <v-text-field label="Level"
                                              v-model="eSelected.level">
                                </v-text-field>
                            </v-col>
                            <v-col cols="2"
                                   sm="2"
                                   md="2">
                                <v-text-field label="Channel"
                                              v-model="eSelected.channel">
                                </v-text-field>
                            </v-col>
                            <v-col cols="2"
                                   sm="2"
                                   md="2">
                                <v-text-field label="Contact Name"
                                              v-model="eSelected.contact_id">
                                </v-text-field>
                            </v-col>
                            <v-col cols="2"
                                   sm="2"
                                   md="2">
                                <v-text-field label="IP"
                                              v-model="eSelected.remote_addr">
                                </v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6"
                                   sm="12"
                                   md="6">
                                <v-text-field label="Path"
                                              v-model="eSelected.request_uri">
                                </v-text-field>
                            </v-col>
                            <v-col cols="6"
                                   sm="12"
                                   md="6">
                                <v-textarea label="Message"
                                            v-model="eSelected.message"
                                            auto-grow
                                            dense
                                            rows="1">
                                </v-textarea>
                            </v-col>
                        </v-row>
                    </v-container>
                    <h3>Data</h3>
                    <JsonEditorVue v-model="eSelected.dataJSON"
                                   readOnly />
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
import badgeSearchList from '@/components/badgeSearchList.vue';
import simpleList from '@/components/simpleList.vue';
import badgeTypeForm from '@/components/badgeTypeForm.vue';
import formQuestionEditList from '@/components/formQuestionEditList.vue';
import treeList from '@/components/treeList.vue';
import editBadgeAdmin from '@/components/editBadgeAdmin.vue';
import editDepartment from '@/components/editDepartment.vue';

export default {
    components: {
        //badgeSearchList,
        simpleList,
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
            text: 'User',
            value: 'real_name'
        }, {
            text: 'IP',
            value: 'remote_addr'
        }, {
            text: 'URL',
            value: 'request_uri'
        }, {
            text: 'Message',
            value: 'message'
        }],
        eSelected: {},
        eView: false,
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
                name: "view",
                text: "View"
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
        viewErrorLog: function(errorRow) {
            console.log('view error selected from grid', errorRow);
            let that = this;
            that.loading = true;
            admin.genericGet(this.authToken, 'System/ErrorLog/' + errorRow.id, null, function(logData) {
                logData.dataJSON = JSON.parse(logData.data);
                that.eSelected = logData;
                that.loading = false;
                that.eView = true;

            }, function() {
                that.loading = false;
            })
        },
        editBadge: function(selectedBadge) {
            console.log('edit badge selected from grid', selectedBadge);
            let that = this;
            that.loading = true;
            admin.genericGet(this.authToken, 'Staff/Badge/' + selectedBadge.id, null, function(editBadge) {
                that.bSelected = editBadge;
                that.loading = false;
                that.bEdit = true;
                that.$nextTick(() => {
                    that.bModified = false;
                })

            }, function() {
                that.loading = false;
            })
        },
        saveBadge: function(sendStatus) {
            console.log('saving badge', this.bSelected);
            let that = this;
            that.loading = true;
            admin.genericPost(this.authToken, 'Staff/Badge/' + this.bSelected.id + "?sendupdate=" + (sendStatus ? "true" : "false"), this.bSelected, function(SavedDetails) {
                that.bSelected = {};
                that.loading = false;
                that.bEdit = false;
                that.bSaved = true;
                that.bSavedDetails = SavedDetails;
                that.$nextTick(() => {
                    that.bModified = false;
                })

            }, function() {
                that.loading = false;
            })
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
    }
};
</script>
