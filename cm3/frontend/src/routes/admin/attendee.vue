<template>
<v-container fluid
             fill-height>

    <v-row class="fill-height">
        <v-col>

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
            </v-tabs-items>
        </v-col>
    </v-row>
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
import badgeSearchList from '@/components/badgeSearchList.vue';
import orderableList from '@/components/orderableList.vue';
import badgeTypeForm from '@/components/badgeTypeForm.vue';
import formQuestionEditList from '@/components/formQuestionEditList.vue';

export default {
    components: {
        badgeSearchList,
        orderableList,
        badgeTypeForm,
        formQuestionEditList
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
        }],
        btSelected: {},
        btDialog: false,
        btLoading: false,

    }),
    computed: {
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        listActions: function() {
            var result = [];
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
            this.btSelected = selectedBadge;
        },
        createBadgeType: function() {
            this.btDialog = true;
            this.btSelected = {};
        },
        editBadgeType: function(selectedBadgeType) {
            this.btLoading = true;
            this.btDialog = true;
            var that = this;
            admin.genericGet(this.authToken, 'Attendee/BadgeType/' + selectedBadgeType.id, null, function(editBt) {

                that.btSelected = editBt;
                that.btLoading = false;
            }, function() {
                that.btLoading = false;
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
                that.btLoading = false;
                that.btDialog = false;
            }, function() {
                that.btLoading = false;
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
