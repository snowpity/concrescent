<template>
<v-container fluid
             fill-height>
    <v-row class="fill-height">
        <v-col>
            <v-data-table :options.sync="tableOptions"
                          :server-items-length="totalResults"
                          :loading="loading"
                          :headers="headers"
                          multi-sort
                          :items="tableResults"
                          item-key="uuid"
                          class="elevation-1 fill-height"
                          :search="searchText">
                <template v-slot:top>
                    <v-text-field v-model="searchText"
                                  label="Search"
                                  class="mx-4"></v-text-field>
                </template>
            </v-data-table>
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

export default {
    data: () => ({
        searchText: "",
        loading: false,
        tableOptions: {},
        tableResults: [],
        totalResults: 0,
        rules: {
            required: value => !!value || 'Required.',
        },
    }),
    computed: {
        headers: () => {

            return [{
                    text: 'ID',
                    align: 'start',
                    sortable: false,
                    value: 'id',
                },
                {
                    text: 'Context',
                    sortable: false,
                    value: 'context_code',
                },
                {
                    text: 'Display',
                    sortable: false,
                    value: 'display_id',
                },
                {
                    text: 'Real Name',
                    value: 'real_name',
                },
                {
                    text: 'Fandom Name',
                    value: 'fandom_name',
                },
                {
                    text: 'Badge Type',
                    value: 'badge_type_name',
                },
                {
                    text: 'Application Status',
                    value: 'application_status',
                },
                {
                    text: 'Payment Status',
                    value: 'payment_status',
                },
            ];
        },
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        }
    },
    methods: {
        checkPermission: () => {
            console.log('Hey! Listen!');
        },
        doSearch: function() {
            this.loading = true;
            const pageOptions = [
                'sortBy',
                'sortDesc',
                'page',
                'itemsPerPage'
            ].reduce((a, e) => (a[e] = this.tableOptions[e], a), {});;
            admin.badgeSearch(this.authToken, this.searchText, pageOptions, (results, total) => {
                this.tableResults = results;
                this.totalResults = total;
                this.loading = false;
            })
        }
    },
    watch: {
        $route() {
            this.$nextTick(this.checkPermission);
        },
        searchText: debounce(function(newSearch) {
            this.doSearch();
        }, 500),
        tableOptions: {
            handler() {
                this.doSearch()
            },
            deep: true,
        }
    },
    created() {
        this.checkPermission();
        //this.doSearch();
    }
};
</script>
