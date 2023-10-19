<template>
<v-tabs-items :value="subTabIx"
              touchless>
    <v-tab-item value="0">
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
                         @edit="editSubmission">
            <template v-slot:[`item.id`]="{ item }">
                <v-tooltip right>
                    <template v-slot:activator="{ on, attrs }">
                        <span v-bind="attrs"
                              v-on="on">
                            [{{item.context_code}}{{item.display_id}}]</span>
                    </template>
                    {{item.id}}
                </v-tooltip>
            </template>
        </badgeSearchList>
    </v-tab-item>

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

    <v-tab-item value="1">
        <badgeSearchList v-if="context_id>0"
                         :apiPath="'Application/' + context_code +'/Applicant'"
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
    <v-tab-item value="2">
        <orderableList :apiPath="'Application/' + context_code +'/BadgeType'"
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

                    <badgeTypeForm v-model="btSelected"
                                   isGroup />
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
    <v-tab-item value="3">
        <formQuestionEditList :context_code="context_code" />
    </v-tab-item>
    <v-tab-item value="4">

        <simpleList v-if="context_id>0"
                    :apiPath="'Application/' + context_code +'/PromoCode'"
                    :isEditingItem="pEdit"
                    :AddHeaders="pAddHeaders"
                    :actions="btActions"
                    :footerActions="btFooterActions"
                    @edit="editPromoCode"
                    @create="createPromoCode">

            <template v-slot:[`item.discount`]="{ item }">
                {{item.is_percentage ? "":"$"}}
                {{item.discount}}
                {{item.is_percentage ? "%":""}}
            </template>
        </simpleList>
        <v-dialog v-model="pEdit"
                  scrollable>

            <v-card>
                <v-card-title class="headline">Edit Promo Code</v-card-title>
                <v-divider></v-divider>
                <v-card-text>
                    <promoCodeForm v-model="pSelected"
                                   :badge_types="contextBadges" />
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="default"
                           @click="pEdit = false">Cancel</v-btn>
                    <v-btn color="primary"
                           @click="savePromoCode">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-tab-item>

    <v-tab-item value="5">

        <simpleList :apiPath="'Application/' + context_code +'/Addon'"
                    :isEditingItem="aEdit"
                    :AddHeaders="aAddHeaders"
                    :actions="btActions"
                    :footerActions="btFooterActions"
                    show-expand
                    @edit="editAddon"
                    @create="createAddon">

            <template v-slot:[`item.discount`]="{ item }">
                {{item.is_percentage ? "":"$"}}
                {{item.discount}}
                {{item.is_percentage ? "%":""}}
            </template>

            <template v-slot:expanded-item="{ headers, item }">
                <td :colspan="headers.length">
                    <v-container flex>
                        <simpleList :apiPath="'Application/' + context_code +'/Addon/'+ item.id + '/Purchase'"
                                    :headerKey="{
                                        text: 'ID',
                                        align: 'start',
                                        value: 'application_id',
                                    }"
                                    :AddHeaders="asAddHeaders"
                                    :actions="asActions"
                                    @edit="editSubmissionFromAddon">
                            <template v-slot:[`item.application_id`]="{ item }">
                                <v-tooltip right>
                                    <template v-slot:activator="{ on, attrs }">
                                        <span v-bind="attrs"
                                              v-on="on">
                                            [{{context_code}}{{item.display_id}}]</span>
                                    </template>
                                    {{item.application_id}}
                                </v-tooltip>
                            </template>
                        </simpleList>
                    </v-container>
                </td>
            </template>
        </simpleList>
        <v-dialog v-model="aEdit"
                  scrollable>

            <v-card>
                <v-card-title class="headline">Edit Addon</v-card-title>
                <v-divider></v-divider>
                <v-card-text>
                    <addonTypeForm v-model="aSelected"
                                   :badge_types="contextBadges" />
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="default"
                           @click="aEdit = false">Cancel</v-btn>
                    <v-btn color="primary"
                           @click="saveAddon">Save</v-btn>
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
    mapActions,
    mapGetters
} from 'vuex';
import admin from '../../api/admin';
import {
    debounce
} from '@/plugins/debounce';
import badgeSearchList from '@/components/badgeSearchList.vue';
import orderableList from '@/components/orderableList.vue';
import simpleList from '@/components/simpleList.vue';
import badgeTypeForm from '@/components/badgeTypeForm.vue';
import promoCodeForm from '@/components/promoCodeForm.vue';
import addonTypeForm from '@/components/addonTypeForm.vue';
import formQuestionEditList from '@/components/formQuestionEditList.vue';
import editBadgeAdmin from '@/components/editBadgeAdmin.vue';

export default {
    components: {
        badgeSearchList,
        orderableList,
        simpleList,
        badgeTypeForm,
        promoCodeForm,
        addonTypeForm,
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
            text: 'Name',
            value: 'name'
        }, {
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
        pAddHeaders: [{
            text: 'Code',
            value: 'code'
        }, {
            text: 'Dates Available',
            value: 'dates_available'
        }, {
            text: 'Total Available',
            value: 'quantity'
        }, {
            text: 'Discount',
            value: 'discount'
        }, {
            text: 'Active',
            value: 'active'
        }],
        pSelected: {},
        pEdit: false,

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
        aAddHeaders: [{
            text: 'Name',
            value: 'name'
        }, {
            text: 'Dates Available',
            value: 'dates_available'
        }, {
            text: 'Total Available',
            value: 'quantity'
        }, {
            text: 'Price',
            value: 'price'
        }, {
            text: 'Active',
            value: 'active'
        }],
        aSelected: {},
        aEdit: false,

        asAddHeaders: [{
            text: 'Real Name',
            value: 'real_name',
        }, {
            text: 'Fandom Name',
            value: 'fandom_name',
        }, {
            text: 'Payment Status',
            value: 'payment_status',
        }, ],

        loading: false,
        createError: '',
    }),
    computed: {
        ...mapGetters('products', {
            currentContext: 'selectedbadgecontext',
            contextBadges: 'contextBadges',
        }),
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        pageTitle: function() {
            return 'Group Applications - ' + this.currentContext.name;
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
        asActions: function() {
            var result = [];
            result.push({
                name: 'edit',
                text: 'Edit submission',
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
        checkPermission() {
            console.log('Hey! Listen!');

            this.$emit('updateSubTitle', this.pageTitle);
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
            admin.genericGet(this.authToken, 'Application/' + this.context_code + '/Submission/' + selectedBadge.application_id, null, function(editBadge) {
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
        editPromoCode: function(selectedPromoCode) {
            console.log(selectedPromoCode);
            let that = this;
            that.loading = false;
            admin.genericGet(this.authToken, 'Application/' + this.context_code + '/PromoCode/' + selectedPromoCode.id, null, function(editPromoCode) {
                console.log('loaded PromoCode', editPromoCode)
                that.pSelected = editPromoCode;
                that.loading = false;
                that.pEdit = true;
            }, function() {
                that.loading = false;
            })
        },
        savePromoCode: function() {
            var url = 'Application/' + this.context_code + '/PromoCode';
            if (this.pSelected.id != undefined)
                url = url + '/' + this.pSelected.id;
            console.log("Saving Promo Code", this.pSelected)
            var that = this;
            admin.genericPost(this.authToken, url, this.pSelected, function(editPC) {

                that.pSelected = editPC;
                that.loading = false;
                that.pEdit = false;
            }, function() {
                that.loading = false;
            })
        },
        createPromoCode: function() {
            this.pEdit = true;
            this.pSelected = {};
        },

        editAddon: function(selectedAddon) {
            console.log(selectedAddon);
            let that = this;
            that.loading = false;
            admin.genericGet(this.authToken, 'Application/' + this.context_code + '/Addon/' + selectedAddon.id, null, function(editAddon) {
                console.log('loaded Addon', editAddon)
                that.aSelected = editAddon;
                that.loading = false;
                that.aEdit = true;
            }, function() {
                that.loading = false;
            })
        },
        saveAddon: function() {
            var url = 'Application/' + this.context_code + '/Addon';
            if (this.aSelected.id != undefined)
                url = url + '/' + this.aSelected.id;
            console.log("Saving Addon", this.aSelected)
            var that = this;
            admin.genericPost(this.authToken, url, this.aSelected, function(editA) {

                that.aSelected = editA;
                that.loading = false;
                that.aEdit = false;
            }, function() {
                that.loading = false;
            })
        },
        createAddon: function() {
            this.aEdit = true;
            this.aSelected = {};
        },
        editSubmissionFromAddon: function(selectedSubmission) {
            console.log('edit submission from addon grid', selectedSubmission);
            let that = this;
            that.loading = true;
            admin.genericGet(this.authToken, 'Application/' + this.context_code + '/Submission/' + selectedSubmission.application_id, null, function(editSubmission) {
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
        this.loading = true;
        //Wait until we have context info
        await new Promise((resolve, reject) => {
            var triesLeft = 10;
            const interval = setInterval(async () => {
                if (this.$store.getters['products/gotBadgeContexts']) {
                    console.log('contexts are loaded for application')
                    resolve();
                    clearInterval(interval);
                } else if (triesLeft <= 1) {
                    resolve();
                    clearInterval(interval);
                }
                triesLeft--;
            }, 200);
        })

        await this.$store.dispatch('products/selectContext', this.context_code);
        this.loading = false;
        this.checkPermission();
        //this.doSearch();
        this.$emit('updateSubTabs', [{
                key: '0',
                text: 'Submissions',
                title: 'Submissions'
            },
            {
                key: '1',
                text: 'Badges',
                title: 'Badges'
            },
            {
                key: '2',
                text: 'Types',
                title: 'Types'
            },
            {
                key: '3',
                text: 'Questions',
                title: 'Questions'
            },
            {
                key: '4',
                text: 'Promos',
                title: 'Promos'
            },
            {
                key: '5',
                text: 'Addons',
                title: 'Addons'
            }

        ]);
    }
};
</script>
