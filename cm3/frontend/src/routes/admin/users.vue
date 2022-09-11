<template>
<v-tabs-items :value="subTabIx"
              touchless>
    <v-tab-item key="0">
        <simpleList apiPath="AdminUser"
                    :AddHeaders="listAddHeaders"
                    :RemoveHeaders="listRemoveHeaders"
                    :isEditingItem="uEdit"
                    :actions="listActions"
                    :footerActions="listFooterActions"
                    @edit="editUser"
                    @create="createUser" />

        <v-dialog v-model="uEdit">
            <v-card tile>

                <v-toolbar dark
                           flat
                           color="primary">
                    <v-btn icon
                           dark
                           @click="uEdit = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <v-toolbar-title>Edit User</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-toolbar-items>
                        <v-btn dark
                               text
                               @click="saveUser">
                            Save
                        </v-btn>
                    </v-toolbar-items>
                </v-toolbar>

                <editAdminUser v-model="uSelected" />
            </v-card>
        </v-dialog>

        <v-dialog v-model="uCreate">
            <v-card tile>

                <v-toolbar dark
                           flat
                           color="primary">
                    <v-btn icon
                           dark
                           @click="uCreate = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <v-toolbar-title>Create User</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-toolbar-items>
                        <v-btn dark
                               text
                               @click="saveUser">
                            Save
                        </v-btn>
                    </v-toolbar-items>
                </v-toolbar>
                <simpleDropdown apiPath="Contact"
                                valueDisplay="real_name"
                                valueSubDisplay="email_address"
                                label="Search contacts"
                                v-model="uNew_contact_id" />
                <editAdminUser v-model="uSelected" />
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
import simpleDropdown from '@/components/simpleDropdown.vue';
import editAdminUser from '@/components/editAdminUser.vue';

export default {
    components: {
        simpleList,
        simpleDropdown,
        editAdminUser
    },
    props: [
        'subTabIx'
    ],
    data: () => ({
        listRemoveHeaders: [
            'id'
        ],
        listAddHeaders: [{
            text: 'ID',
            value: 'contact_id'
        }, {
            text: 'Username',
            value: 'username'
        }, {
            text: 'Active',
            value: 'active'
        }],
        uSelected: {},
        uEdit: false,
        uCreate: false,
        uNew_contact_id: null,
        loading: false,

    }),
    computed: {
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        listActions: function() {
            var result = [];
            result.push({
                name: 'edit',
                text: 'Edit',
                icon: 'edit-pencil'
            });
            return result;
        },
        listFooterActions: function() {
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
        editUser: function(selectedUser) {
            console.log("Edit user", selectedUser);
            let that = this;
            that.loading = false;
            admin.genericGet(this.authToken, 'AdminUser/' + selectedUser.contact_id, null, function(editUser) {
                console.log('loaded user', editUser)
                that.uSelected = editUser;
                that.loading = false;
                that.uEdit = true;
            }, function() {
                that.loading = false;
            })
        },
        createUser: function() {
            this.uCreate = true;
            this.uSelected = {
                active: true
            };
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
        saveUser: function() {
            var url = 'AdminUser';
            var data = {
                ...this.uSelected,
                contact_id: this.uCreate ? this.uNew_contact_id : this.uSelected.contact_id,
            };
            if (this.uEdit)
                url = url + '/' + data.contact_id;
            console.log("Saving user", this.uSelected)
            this.loading = true;
            var that = this;
            admin.genericPost(this.authToken, url, data, function(editBt) {

                that.loading = false;
                that.uCreate = false;
                that.uEdit = false;
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
