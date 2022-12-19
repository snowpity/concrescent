<template>
<v-stepper v-model="staff_step">
    <v-stepper-header>
        <v-stepper-step :complete="department_id_selected != null"
                        step="1">
            Select Department
        </v-stepper-step>

        <v-divider></v-divider>

        <v-stepper-step :complete="position_id_selected != null"
                        step="2">
            Select Position
        </v-stepper-step>

        <v-divider></v-divider>

        <v-stepper-step step="3">
            Assigned Positions
        </v-stepper-step>
    </v-stepper-header>

    <v-stepper-items>
        <v-stepper-content step="1">

            <treeList apiPath="Staff/Department"
                      v-model="department_id_selected"
                      @data="cacheDepartment" />

            <v-btn color="primary"
                   :disabled="department_id_selected == null"
                   @click="staff_step = 2">
                Continue
            </v-btn>

        </v-stepper-content>

        <v-stepper-content step="2">

            <v-select label="Available positions"
                      :loading="available_positions_loading"
                      :items="available_positions"
                      v-model="position_id_selected"
                      item-value="id"
                      item-text="name"
                      append-text="Add"
                      @click:append-outer="addPosition" />
            <v-row>
                <v-col>
                    Description: {{positionSelected.description}}
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <v-btn text
                           @click="staff_step = 1">
                        Back
                    </v-btn>
                    <v-btn color="primary"
                           :disabled="position_id_selected == null"
                           @click="addPosition">
                        Continue
                    </v-btn>
                </v-col>
            </v-row>
        </v-stepper-content>

        <v-stepper-content step="3">

            List of assigned positions
            <v-expansion-panels>
                <v-expansion-panel v-for="(item,i) in assigned_positions"
                                   :key="i">
                    <v-expansion-panel-header>
                        {{item.department_text}}: {{item.position_text}}
                        <div class="text-right">
                            <v-icon v-if="item.is_exec">mdi-crown</v-icon>
                            <v-icon>mdi-account-alert-outline</v-icon>
                        </div>
                    </v-expansion-panel-header>
                    <v-expansion-panel-content>
                        {{item.position_id}}
                        <v-btn @click="removePosition(i)">Remove</v-btn>
                    </v-expansion-panel-content>
                </v-expansion-panel>
            </v-expansion-panels>

            <v-btn text
                   @click="staff_step = 1">
                Add another position
            </v-btn>

        </v-stepper-content>
    </v-stepper-items>
</v-stepper>
</template>

<script>
import admin from '../api/admin';
import {
    mapGetters
} from 'vuex'
import treeList from '@/components/treeList.vue';
export default {
    components: {
        treeList
    },
    props: ['value'],
    data() {
        return {
            skipEmitOnce: false,
            staff_step: this.value.length > 0 ? 3 : 1,
            department_id_selected: null,
            department_selected: {},
            available_positions_loading: false,
            available_positions: [],
            position_id_selected: undefined,
            assigned_positions: this.value,
        };
    },
    computed: {

        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        ...mapGetters('mydata', {
            'getContactInfo': 'getContactInfo',
            'isLoggedIn': 'getIsLoggedIn',
        }),
        result() {
            if (this.assigned_positions == undefined) return [];
            return this.assigned_positions;
            // .map(function(item) {
            //     return {
            //         position_id: item.position_id,
            //         onboard_completed: item.onboard_completed,
            //         onboard_meta: item.onboard_meta
            //     }
            // });
        },
        positionSelected() {
            return this.available_positions.find(item => item.id == this.position_id_selected) || {
                id: undefined,
                active: false,
                is_exec: false,
                name: 'Loading...',
                description: '',
                desired_count: 0
            };
        }
    },
    methods: {
        cacheDepartment: function(depData) {
            this.department_selected = depData;
        },
        refresh_positions: async function() {
            this.available_positions_loading = true;
            admin.genericGetList(this.authToken, 'Staff/Department/' + this.department_id_selected + '/Position', {

            }, (results, total) => {
                this.available_positions = results;
                this.available_positions_loading = false;
            })
        },
        addPosition: function() {
            if (!this.assigned_positions.some(item => item.position_id == this.position_id_selected)) {

                this.assigned_positions.push({
                    department_id: this.department_id_selected,
                    department_text: this.department_selected.name,
                    position_id: this.position_id_selected,
                    position_text: this.positionSelected.name,
                    onboard_completed: false,
                    onboard_meta: "",
                    is_exec: this.positionSelected.is_exec

                })
                this.staff_step = 3;
            }
        },
        removePosition: function(ix) {
            this.assigned_positions.splice(ix);
            if (this.assigned_positions.length < 1) {
                this.staff_step = 1;
            }
        }
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
            //TODO: Retrieve position meta and add to the assigned_positions array if it doesn't exist
            console.log('got new value', newValue);
            //set the form state
            this.staff_step = newValue.length > 0 ? 3 : 1;

            this.skipEmitOnce = true;
            this.assigned_positions = newValue;
        },
        department_id_selected(department_id) {
            this.refresh_positions();
        },
    },
};
</script>
