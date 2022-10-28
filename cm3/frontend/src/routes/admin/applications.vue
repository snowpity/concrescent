<template>
<v-tabs-items :value="subTabIx"
              touchless>
    <v-tab-item key="0">
        <badgeSearchList v-if="context_id>0"
                         :apiPath="'Application/' + context_code +'/Submission'"
                         :context_code="context_code"
                         :headerFirst="{
                                 text: currentContext.application_name1,
                                 value: 'real_name',
                             }"
                         :headerSecond="{
                                 text: currentContext.application_name2,
                                 value: 'fandom_name',
                             }"
                         :AddHeaders="listAddHeaders"
                         :RemoveHeaders="listRemoveHeaders"
                         :isEditingItem="sEdit"
                         :actions="listActions"
                         @edit="editSubmission" />

        <v-dialog v-model="sEdit"
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
                               @click="sEdit = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <v-toolbar-title>Edit Submission</v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-toolbar-items>
                            <v-menu offset-y
                                    open-on-hover>
                                <template v-slot:activator="{ on, attrs }">
                                    <v-btn :color="sModified ? 'green' : 'primary'"
                                           dark
                                           v-bind="attrs"
                                           v-on="on">
                                        <v-icon>mdi-content-save</v-icon>
                                    </v-btn>
                                </template>
                                <v-list>
                                    <v-list-item @click="saveSubmission(true)">
                                        <v-list-item-title>
                                            Save and send status email
                                        </v-list-item-title>
                                    </v-list-item>
                                    <v-list-item @click="saveSubmission(false)">
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
                    <editBadgeAdmin v-model="sSelected"
                                    @save="saveSubmission" />
                </v-card-text>
            </v-card>
        </v-dialog>

        <v-dialog v-model="sSaved">

            <v-card>
                <v-card-title class="headline">Saved</v-card-title>
                <v-card-text>
                    Successfully saved.

                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary"
                           @click="sSaved = false">Ok</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-tab-item>

    <v-tab-item key="1">
        <badgeSearchList v-if="context_id>0"
                         :apiPath="'Application/' + context_code +'/Submission'"
                         :context_code="context_code"
                         :headerFirst="{
                       text: currentContext.application_name1,
                       value: 'real_name',
                   }"
                         :headerSecond="{
                       text: currentContext.application_name2,
                       value: 'fandom_name',
                   }"
                         :AddHeaders="listAddHeaders"
                         :RemoveHeaders="listRemoveHeaders"
                         :isEditingItem="bEdit"
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
    <v-tab-item key="2">
        <orderableList :apiPath="'Application/' + context_code +'/BadgeType'"
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
    <v-tab-item key="3">
        <formQuestionEditList :context_code="context_code" />
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
    mapActions,
    mapGetters
} from 'vuex';
import admin from '../../api/admin';
import {
    debounce
} from '@/plugins/debounce';
import badgeSearchList from '@/components/badgeSearchList.vue';
import orderableList from '@/components/orderableList.vue';
import badgeTypeForm from '@/components/badgeTypeForm.vue';
import formQuestionEditList from '@/components/formQuestionEditList.vue';
import editBadgeAdmin from '@/components/editBadgeAdmin.vue';

export default {
    components: {
        badgeSearchList,
        orderableList,
        badgeTypeForm,
        formQuestionEditList,
        editBadgeAdmin,
    },
    props: [
        'subTabIx'
    ],
    data: () => ({
        listRemoveHeaders: [
            'time_checked_in',
            'time_printed'
        ],
        listAddHeaders: [],
        sSelected: {},
        sEdit: false,
        sModified: false,
        sSaved: false,
        sSavedDetails: {},
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
        ...mapGetters('products', {
            currentContext: 'selectedbadgecontext',
        }),
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        context_code: function() {
            return this.$route.params.context_code;
        },
        context_id: function() {
            return this.currentContext.id;
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
        editSubmission: function(selectedSubmission) {
            console.log('edit submission from grid', selectedSubmission);
            let that = this;
            that.loading = true;
            admin.genericGet(this.authToken, 'Application/' + this.context_code + '/Submission/' + selectedSubmission.id, null, function(editSubmission) {
                that.sSelected = editSubmission;
                that.loading = false;
                that.sEdit = true;
                that.$nextTick(() => {
                    that.sModified = false;
                })

            }, function() {
                that.loading = false;
            })
        },
        saveSubmission: function(sendStatus) {
            console.log('saving submission', this.sSelected);
            let that = this;
            that.loading = true;
            admin.genericPost(this.authToken, 'Application/' + this.context_code + '/Submission/' + this.sSelected.id + "?sendupdate=" + (sendStatus ? "true" : "false"), this.sSelected, function(SavedDetails) {
                that.sSelected = {};
                that.loading = false;
                that.sEdit = false;
                that.sSaved = true;
                that.sSavedDetails = SavedDetails;
                that.$nextTick(() => {
                    that.sModified = false;
                })

            }, function() {
                that.loading = false;
            })
        },
        editBadge: function(selectedBadge) {
            console.log('edit badge selected from grid', selectedBadge);
            let that = this;
            that.loading = true;
            admin.genericGet(this.authToken, 'Application/' + this.context_code + '/Submission/' + selectedBadge.id, null, function(editBadge) {
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
            admin.genericPost(this.authToken, 'Application/' + this.context_code + '/Submission/' + this.bSelected.id + "?sendupdate=" + (sendStatus ? "true" : "false"), this.bSelected, function(SavedDetails) {
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
            admin.genericGet(this.authToken, 'Application/' + this.context_code + '/BadgeType/' + selectedBadgeType.id, null, function(editBt) {

                that.btSelected = editBt;
                that.loading = false;
            }, function() {
                that.loading = false;
            })
        },
        saveBadgeType: function() {
            var url = 'Application/' + this.context_code + '/BadgeType';
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
            admin.genericGet(this.authToken, 'Application/' + this.context_code + '/Department/' + selectedDepartment.id, null, function(editBt) {

                that.dSelected = editBt;
                that.dDialog = true;
                that.loading = false;
            }, function() {
                that.loading = false;
            })
        },
        saveDepartment: function() {
            var url = 'Application/' + this.context_code + '/Department';
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
        async $route() {
            console.log('Context?', this.context_code)
            await this.$store.dispatch('products/selectContext', this.context_code);
            this.$nextTick(this.checkPermission);
            this.btDialog = this.btDialog;
        },
        bSelected(newBadgeData) {
            this.bModified = true;
        },
    },
    async created() {
        console.log('Context!', this.context_code)
        await this.$store.dispatch('products/selectContext', this.context_code);
        this.checkPermission();
        //this.doSearch();
    }
};
</script>
