<template>
<v-container fluid>
    <v-row>
        <v-col cols="6"
               sm="8"
               md="6">
            <v-text-field label="Username"
                          v-model="model.username"
                          :rules="RulesRequired">
            </v-text-field>
        </v-col>
        <v-col cols="6"
               sm="8"
               md="6">
            <v-text-field label="Password"
                          hint="(Unchanged)"
                          :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                          :type="showPassword ? 'text' : 'password'"
                          @click:append="showPassword = !showPassword"
                          v-model="model.password">
            </v-text-field>
        </v-col>
        <v-col cols="12"
               sm="6"
               md="3">
            <v-checkbox dense
                        hide-details
                        v-model="model.active">
                <template v-slot:label>
                    Active
                </template>
            </v-checkbox>
        </v-col>

        <v-col cols="12"
               sm="6"
               md="3">
            <v-checkbox dense
                        hide-details
                        v-model="model.adminOnly">
                <template v-slot:label>
                    Admin Only
                </template>
            </v-checkbox>
        </v-col>
    </v-row>
    <v-row>
        <v-col>
            <v-textarea label="Preferences"
                        v-model="model.preferences" />
        </v-col>
    </v-row>
    <v-row>
        <v-col cols="12">
            <v-select v-model="model.permissions.EventPerms"
                      :items="perms.EventPerms"
                      chips
                      label="Event Permissions"
                      :readonly="readonly_perms"
                      multiple></v-select>
        </v-col>
        <v-col>
            <v-list>
                <v-list-item v-for="context in groupPermList"
                             :key="context.id">
                    <v-list-item-avatar>
                        <v-icon>mdi-{{context.menu_icon}}</v-icon>
                    </v-list-item-avatar>

                    <v-list-item-content>
                        <v-list-item-title>

                            <v-select v-model="model.permissions.GroupPerms[context.id]"
                                      placeholder="No permissions"
                                      :items="perms.GroupPerms"
                                      chips
                                      multiple
                                      :readonly="readonly_perms"
                                      :label="context.name" />
                        </v-list-item-title>
                    </v-list-item-content>
                </v-list-item>
            </v-list>
        </v-col>
    </v-row>

</v-container>
</template>

<script>
import admin from '../api/admin';
import {
    mapGetters
} from 'vuex'

function nullIfEmptyOrZero(inValue) {
    if (inValue == 0 || inValue == '' || inValue == null) return null;
    return inValue;
}

function undefinedIfEmptyOrZero(inValue) {
    if (inValue == 0 || inValue == '' || inValue == null) return undefined;
    return inValue;
}
export default {
    components: {},
    props: {
        'value': {
            type: Object
        },
        'readonly_perms': {
            type: Boolean
        }
    },
    data() {
        return {
            showPassword: false,
            skipEmitOnce: false,
            validbadgeTypeInfo: false,
            model: {
                contact_id: this.value?.contact_id,
                username: this.value?.username || "",
                password: this.value?.password,
                active: this.value?.active == 1,
                adminOnly: this.value?.adminOnly == 1,
                preferences: this.value?.preferences || "",
                permissions: this.value?.permissions || {
                    EventPerms: [],
                    GroupPerms: []
                },
            },
            perms: {
                EventPerms: [],
                GroupPerms: []
            },

            RulesRequired: [
                (v) => !!v || 'Required',
            ],
        };
    },
    computed: {
        ...mapGetters('mydata', {
            'isLoggedIn': 'getIsLoggedIn',
            'authToken': 'getAuthToken',
        }),
        ...mapGetters('products', {
            'badgeContexts': 'badgeContexts',
        }),
        result() {
            return {
                contact_id: this.model.contact_id || undefined,
                username: this.model.username || "",
                password: undefinedIfEmptyOrZero(this.model.password),
                active: this.model.active ? 1 : 0,
                adminOnly: this.model.adminOnly ? 1 : 0,
                preferences: nullIfEmptyOrZero(this.model.preferences),
                permissions: this.model.permissions,
            }
        },
        groupPermList() {
            return this.badgeContexts
                .filter((context) => context.id > 0);
        }
    },
    methods: {

        saveStartDate(date) {
            this.$refs.menuStartDate.save(date);
            this.model.start_date = this.model.start_date;
        },
        saveEndDate(date) {
            this.$refs.menuEndDate.save(date);
            this.model.end_date = this.model.end_date;
        },
    },
    watch: {
        result(newData) {
            if (this.skipEmitOnce == true) {
                this.skipEmitOnce = false;
                return;
            }
            this.$emit('input', newData);
        },
        value(newValue) {
            //Splat the input into the form
            this.skipEmitOnce = true;
            this.model = {
                ...newValue
            };
            this.result.quantity + 1;
        }
    },
    created() {
        var that = this;
        admin.genericGet(this.authToken, 'AdminUser/GetPerms', null, function(perms) {

            that.perms = perms;
        }, function() {
            //Whoops
        })
    }
};
</script>
