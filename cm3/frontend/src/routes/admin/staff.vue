<template>
<v-tabs-items :value="subTabIx"
              touchless>
    <v-tab-item key="0">
        <badgeSearchList apiPath="Staff/Badge"
                         context="S"
                         :AddHeaders="listAddHeaders"
                         :RemoveHeaders="listRemoveHeaders"
                         :isEditingItem="bEdit || bPrint"
                         :actions="listActions"
                         @edit="editBadge" />

        <v-dialog v-model="bEdit"
                  fullscreen
                  scrollable
                  hide-overlay
                  persistent>
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
                                    <v-btn color="primary"
                                           dark
                                           v-bind="attrs"
                                           v-on="on">
                                        <v-icon>mdi-content-save</v-icon>
                                    </v-btn>
                                </template>
                                <v-list>
                                    <v-list-item>
                                        <v-list-item-title>
                                            Save and send status email
                                        </v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="bEdit = false">
                                        <v-list-item-title>
                                            Save directly
                                        </v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </v-menu>
                        </v-toolbar-items>
                    </v-toolbar>
                </v-card-title>
                <v-card-text class="pa-0">
                    <editBadgeAdmin v-model="bSelected" />
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-tab-item>
    <v-tab-item key="1">
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
    <v-tab-item key="2">
        <formQuestionEditList context_code="S" />
    </v-tab-item>
    <v-tab-item key="3">
        <treeList apiPath="Staff/Department"
                  :AddHeaders="dAddHeaders"
                  :actions="btActions"
                  :footerActions="btFooterActions"
                  :isEditingItem="dDialog"
                  @edit="editBadgeType"
                  @create="createBadgeType" />
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

export default {
    components: {
        badgeSearchList,
        orderableList,
        badgeTypeForm,
        formQuestionEditList,
        treeList,
        editBadgeAdmin
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
        loading: false,
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
            result.push({
                name: "print",
                text: "Print"
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
        }
    },
    methods: {
        checkPermission: () => {
            console.log('Hey! Listen!');
        },
        editBadge: function(selectedBadge) {
            console.log(selectedBadge);
            let that = this;
            that.loading = false;
            admin.genericGet(this.authToken, 'Staff/Badge/' + selectedBadge.id, null, function(editBadge) {
                console.log('loaded badge', editBadge)
                that.bSelected = editBadge;
                that.loading = false;
                that.bEdit = true;
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
        }
    },
    watch: {
        $route() {
            this.$nextTick(this.checkPermission);
        },
    },
    created() {
        this.checkPermission();
        //this.doSearch();
    }
};
</script>
