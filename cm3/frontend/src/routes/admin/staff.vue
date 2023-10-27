<template>
<v-tabs-items :value="subTabIx"
              touchless>
    <v-tab-item value="0">
        <badgeSearchList apiPath="Staff/Badge"
                         context_code="S"
                         :AddHeaders="listAddHeaders"
                         :RemoveHeaders="listRemoveHeaders"
                         :isEditingItem="bEdit || bPrint"
                         :actions="listActions"
                         @edit="editBadge" />

        <v-dialog v-model="bEdit"
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
                               @click="bEdit = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <v-toolbar-title>Edit Badge</v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-toolbar-items>
                            <v-menu offset-y
                                    open-on-hover>
                                <template v-slot:activator="{ on, attrs }">
                                    <v-btn :color="bModified ? 'green' : 'primary'"
                                           dark
                                           v-bind="attrs"
                                           v-on="on">
                                        <v-icon>mdi-content-save</v-icon>
                                    </v-btn>
                                </template>
                                <v-list>
                                    <v-list-item @click="saveBadge(true)">
                                        <v-list-item-title>
                                            Save and send status email
                                        </v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="saveBadge(false)">
                                        <v-list-item-title>
                                            Save only
                                        </v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </v-menu>
                        </v-toolbar-items>
                    </v-toolbar>
                </v-card-title>
                <v-card-text class="pa-0">
                    <editBadgeAdmin v-model="bSelected"
                                    @save="saveBadge" />
                </v-card-text>
            </v-card>
        </v-dialog>
        <v-dialog v-model="bSaved">

            <v-card>
                <v-card-title class="headline">Saved</v-card-title>
                <v-card-text>
                    Successfully saved.

                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary"
                           @click="bSaved = false">Ok</v-btn>
                </v-card-actions>
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
                  scrollable>

            <v-card>
                <v-card-title class="headline">Edit Badge Type</v-card-title>
                <v-divider></v-divider>
                <v-card-text>

                    <badgeTypeForm v-model="btSelected" />
                </v-card-text>
                <v-divider></v-divider>
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
                  :actions="dActions"
                  :footerActions="btFooterActions"
                  :isEditingItem="dDialog"
                  @edit="editDepartment"
                  @create="createDepartment"
                  @moveup="moveDepartmentUp"
                  @movedown="moveDepartmentDown" />
        <v-dialog v-model="dDialog"
                  scrollable>

            <v-card>
                <v-card-title class="headline">Edit Department</v-card-title>
                <v-divider></v-divider>
                <v-card-text>

                    <editDepartment v-model="dSelected" />
                </v-card-text>
                <v-divider></v-divider>
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
import orderableList from '@/components/orderableList.vue';
import badgeTypeForm from '@/components/badgeTypeForm.vue';
import formQuestionEditList from '@/components/formQuestionEditList.vue';
import treeList from '@/components/treeList.vue';
import editBadgeAdmin from '@/components/editBadgeAdmin.vue';
import editDepartment from '@/components/editDepartment.vue';

export default {
    components: {
        badgeSearchList,
        orderableList,
        badgeTypeForm,
        formQuestionEditList,
        treeList,
        editBadgeAdmin,
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
            text: 'Secondary Email',
            value: 'notify_email'
        }],
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
            // result.push({
            //     name: "print",
            //     text: "Print"
            // });
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
        dActions: function() {
            var result = [];
            result.push({
                name: "moveup",
                text: "Up"
            },{
                name: "movedown",
                text: "Down"
            },{
                name: 'edit',
                text: 'Edit',
                icon: 'edit-pencil'
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
        moveDepartmentUp: function(selectedDepartment) {
            //Hack! Probably should find a better way to refresh the departments list only
            this.dDialog = true;
            var that = this;
            admin.genericPost(this.authToken, 'Staff/Department/' + selectedDepartment.id + '/Move', { direction: true }, function (results) {
                that.dDialog = false;
            }, function () {
                that.dDialog = false;
            })
        },
        moveDepartmentDown: function(selectedDepartment) {
            this.dDialog = true;
            var that = this;
            admin.genericPost(this.authToken, 'Staff/Department/' + selectedDepartment.id + '/Move', { direction: false }, function (results) {

                that.dDialog = false;
            }, function () {
                that.dDialog = false;
            })
        },
        editDepartment: function(selectedDepartment) {
            this.loading = true;
            var that = this;
            admin.genericGet(this.authToken, 'Staff/Department/' + selectedDepartment.id , null, function(results) {

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
                key: '0',
                text: 'Badges',
                title: 'Badges'
            },
            {
                key: '1',
                text: 'Types',
                title: 'Types'
            },
            {
                key: '2',
                text: 'Questions',
                title: 'Questions'
            },
            {
                key: '3',
                text: 'Departments',
                title: 'Departments'
            },
            {
                key: '4',
                text: 'Notifications',
                title: 'Notifications'
            }

        ]);
    }
};
</script>
