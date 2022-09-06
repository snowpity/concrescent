<template>
<v-container fluid>
    <v-row>
        <v-col cols="4"
               sm="8"
               md="4">
            <v-select label="Parent Department"
                      :items="currentDepartments"
                      item-text="name"
                      item-value="id"
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

        <v-col cols="12">
            <v-textarea label="Public Description"
                        v-model="model.description" />
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
            Positions
            <v-list>
                <v-list-group v-for="(item,ix) in model.positions"
                              :key="ix"
                              v-model="item.expand"
                              :prepend-icon="item.is_exec ? 'mdi-crown' : ' '"
                              no-action>
                    <template v-slot:activator>
                        <v-list-item-content>
                            <v-list-item-title v-text="item.name"></v-list-item-title>
                            Staffing level {{item.assigned_count}}/{{item.desired_count}}
                        </v-list-item-content>
                    </template>
                    <v-row>
                        <v-col cols="12"
                               sm="8"
                               md="4">
                            <v-text-field label="Position Name"
                                          v-model="item.name">
                            </v-text-field>
                        </v-col>
                        <v-col cols="4"
                               sm="2"
                               md="2">
                            <v-checkbox dense
                                        hide-details
                                        v-model="item.active">
                                <template v-slot:label>
                                    Active
                                </template>
                            </v-checkbox>
                        </v-col>
                        <v-col cols="8"
                               sm="2"
                               md="2">
                            <v-checkbox dense
                                        hide-details
                                        v-model="item.is_exec">
                                <template v-slot:label>
                                    Is executive
                                </template>
                            </v-checkbox>
                        </v-col>
                        <v-col cols="12"
                               sm="8"
                               md="4">
                            <v-text-field label="Desired Count"
                                          type="number"
                                          min='0'
                                          v-model="item.desired_count"
                                          persistent-hint
                                          :hint="'Currently assigned: ' + item.assigned_count">
                            </v-text-field>
                        </v-col>
                        <v-col cols="12">
                            <v-textarea label="Public Description"
                                        v-model="item.description" />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea label="Notes"
                                        v-model="item.notes" />
                        </v-col>
                    </v-row>
                    <v-btn @click="removePosition(ix)"
                           color="red"
                           :disabled="item.assigned_count>0">Remove</v-btn><i v-if="item.assigned_count>0">Un-assign all staff to enable deletion</i>
                </v-list-group>
            </v-list>
            <v-btn @click="addPosition">Add</v-btn>
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
                id: this.value?.id,
                parent_id: this.value?.parent_id || null,
                active: this.value?.active == 1 | this.value?.active == undefined,
                display_order: this.value?.display_order || 1,
                name: this.value?.name || "",
                email_primary: this.value?.email_primary || "",
                email_secondary: this.value?.email_secondary || "",
                description: this.value?.description || "",
                notes: this.value?.notes || "",
                positions: this.value?.positions || [],
            },
            currentDepartments: [],

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
                id: undefinedIfEmptyOrZero(this.model.id),
                parent_id: nullIfEmptyOrZero(this.model.parent_id),
                active: this.model.active ? 1 : 0,
                display_order: this.model.display_order || 1,
                name: this.model.name || "",
                email_primary: this.model.email_primary || "",
                email_secondary: this.model.email_secondary || "",
                description: nullIfEmptyOrZero(this.model.description),
                notes: nullIfEmptyOrZero(this.model.notes),
                positions: this.model.positions || [],

            }
        },
    },
    methods: {

        addPosition() {
            this.model.positions.push({
                name: 'Staff',
                active: true,
                is_exec: false,
                desired_count: 0,
                assigned_count: 0,
                description: '',
                notes: '',
                expand: true
            });
        },
        removePosition(ix) {
            this.$delete(this.model.positions, ix);
        },
        refreshCurrentDepartments() {
            //TODO: This should be handled by the store...
            var that = this;
            admin.genericGet(this.authToken, 'Staff/Department', null, function(departments) {

                that.currentDepartments = departments.filter(department => department.id != that.model.id);
            }, function() {
                //Whoops
            })
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
            if (this.model.id != newValue.id)
                this.refreshCurrentDepartments()

            this.model = {
                ...newValue
            };

        }
    },
    created() {
        this.refreshCurrentDepartments();
    }
};
</script>
