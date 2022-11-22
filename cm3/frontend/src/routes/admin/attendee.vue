<template>
<v-tabs-items :value="subTabIx"
              touchless>
    <v-tab-item key="0">
        <badgeSearchList apiPath="Attendee/Badge"
                         context="A"
                         :AddHeaders="listAddHeaders"
                         :RemoveHeaders="listRemoveHeaders"
                         :isEditingItem="bEdit || bPrint"
                         :actions="listActions"
                         @edit="editBadge" />

        <v-dialog v-model="bEdit"
                  fullscreen
                  scrollable
                  hide-overlay>
            <v-card tile>
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
                    <editBadgeAdmin v-model="bSelected" />
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-tab-item>
    <v-tab-item key="1">
        <orderableList apiPath="Attendee/BadgeType"
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
    <v-tab-item key="2">
        <formQuestionEditList context_code="A" />
    </v-tab-item>
    <v-tab-item key="3">

        <simpleList apiPath="Attendee/PromoCode"
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
                <v-card-text>
                    <promoCodeForm v-model="pSelected"
                                   :badge_types="contextBadges" />
                </v-card-text>
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
import formQuestionEditList from '@/components/formQuestionEditList.vue';
import editBadgeAdmin from '@/components/editBadgeAdmin.vue';

export default {
    components: {
        badgeSearchList,
        orderableList,
        simpleList,
        badgeTypeForm,
        promoCodeForm,
        formQuestionEditList,
        editBadgeAdmin
    },
    props: [
        'subTabIx'
    ],
    data: () => ({
        listRemoveHeaders: [
            'application_status',
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
            text: 'Total Available',
            value: 'quantity'
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
        loading: false,
        bModified: false,

    }),
    computed: {
        ...mapGetters('mydata', {
            getAuthToken: 'getAuthToken',
        }),

        ...mapGetters('products', {
            contextBadges: 'contextBadges',
        }),
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
        checkPermission() {
            console.log('Hey! Listen!');
            this.$store.dispatch('products/selectContext', 'A');
        },
        editBadge: function(selectedBadge) {
            console.log(selectedBadge);
            let that = this;
            that.loading = false;
            admin.genericGet(this.authToken, 'Attendee/Badge/' + selectedBadge.id, null, function(editBadge) {
                console.log('loaded badge', editBadge)
                that.bSelected = editBadge;
                that.loading = false;
                that.bEdit = true;
            }, function() {
                that.loading = false;
            })
        },
        saveBadge: function(sendStatus) {
            console.log('saving badge', this.bSelected);
            let that = this;
            that.loading = true;
            admin.genericPost(this.authToken, 'Attendee/Badge/' + this.bSelected.id + "?sendupdate=" + (sendStatus ? "true" : "false"), this.bSelected, function(editBadge) {
                that.bSelected = {};
                that.loading = false;
                that.bEdit = false;
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
            admin.genericGet(this.authToken, 'Attendee/BadgeType/' + selectedBadgeType.id, null, function(editBt) {

                that.btSelected = editBt;
                that.loading = false;
            }, function() {
                that.loading = false;
            })
        },
        saveBadgeType: function() {
            var url = 'Attendee/BadgeType';
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
        editPromoCode: function(selectedPromoCode) {
            console.log(selectedPromoCode);
            let that = this;
            that.loading = false;
            admin.genericGet(this.authToken, 'Attendee/PromoCode/' + selectedPromoCode.id, null, function(editPromoCode) {
                console.log('loaded PromoCode', editPromoCode)
                that.pSelected = editPromoCode;
                that.loading = false;
                that.pEdit = true;
            }, function() {
                that.loading = false;
            })
        },
        savePromoCode: function() {
            var url = 'Attendee/PromoCode';
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
