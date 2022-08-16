<template>
<v-container fluid>
    <v-row>
        <v-col cols="12"
               sm="6"
               md="3">
            <treeList apiPath="Staff/Department"
                      v-model="department_id_selected" />
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
        </v-col>
        <v-col cols="12"
               sm="6"
               md="3">
            List of assigned positions
            <v-expansion-panels>
                <v-expansion-panel v-for="(item,i) in assigned_positions"
                                   :key="i">
                    <v-expansion-panel-header>
                        {{item.position_text}}
                        <v-spacer />
                        <v-icon>mdi-account-alert-outline</v-icon>
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
            department_id_selected: null,
            available_positions_loading: false,
            available_positions: [],
            position_id_selected: undefined,
            assigned_positions: []
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
            return {

            }
        }
    },
    methods: {
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
                    position_id: this.position_id_selected,
                    position_text: this.position_id_selected

                })
            }
        }
    },
    watch: {
        result(newData) {
            //this.$emit('input', newData);
        },
        value: {

        },
        department_id_selected(department_id) {
            this.refresh_positions();
        },
    },
};
</script>
