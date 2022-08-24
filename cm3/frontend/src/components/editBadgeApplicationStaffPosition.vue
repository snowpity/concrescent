<template>
<v-container fluid>
    <v-row>
        <v-col cols="12"
               sm="6"
               md="3">
            <treeList apiPath="Staff/Department"
                      v-model="department_id_selected"
                      @data="cacheDepartment" />
        </v-col>
        <v-col cols="12"
               sm="6"
               md="3">
            <v-select label="Available positions"
                      :loading="available_positions_loading"
                      :items="available_positions"
                      v-model="position_id_selected"
                      item-value="id"
                      item-text="name"
                      append-outer-icon="mdi-arrow-right"
                      @click:append-outer="addPosition" />
            Description: {{positionSelected.description}}
        </v-col>
        <v-col cols="12"
               sm="6"
               md="6">
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
                        <v-btn>Remove</v-btn>
                    </v-expansion-panel-content>
                </v-expansion-panel>
            </v-expansion-panels>
        </v-col>
    </v-row>
</v-container>
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
            this.skipEmitOnce = true;
            this.assigned_positions = newValue;
        },
        department_id_selected(department_id) {
            this.refresh_positions();
        },
    },
};
</script>
