<template>
<v-container fluid>
    <v-row>
        <v-col cols="4"
               sm="8"
               md="4">
            <v-select label="Parent Department"
                      v-model="model.parent_id">
            </v-select>
        </v-col>
        <v-col cols="4"
               sm="8"
               md="4">
            <v-text-field label="Department Name"
                          v-model="model.name">
            </v-text-field>
        </v-col>
        <v-col cols="2"
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
        <v-col cols="12">
            <v-textarea label="Public Description"
                        v-model="model.description" />
        </v-col>
        <v-col cols="12"
               sm="6"
               md="3">
            <v-text-field label="Primary Email"
                          v-model="model.email_primary" />
        </v-col>
        <v-col cols="12"
               sm="6"
               md="3">
            <v-text-field label="Secondary Email"
                          v-model="model.email_secondary" />
        </v-col>

    </v-row>
    <v-row>
        <v-col cols="12">
            <v-textarea label="Notes"
                        v-model="model.notes" />
        </v-col>
    </v-row>
    <v-row>
        <v-col cols="12">
            <v-select v-model="model.permissions.EventPerms"
                      :items="perms.EventPerms"
                      chips
                      label="Positions"
                      multiple></v-select>
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
    props: ['value'],
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
        result() {
            return {
                contact_id: this.model.contact_id || null,
                username: this.model.username || "",
                password: undefinedIfEmptyOrZero(this.model.password),
                active: this.model.active ? 1 : 0,
                adminOnly: this.model.adminOnly ? 1 : 0,
                preferences: nullIfEmptyOrZero(this.model.preferences),
                permissions: this.model.permissions,
            }
        },
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
