<template>
<v-data-table :options.sync="tableOptions"
              :server-items-length="totalResults"
              :loading="loading"
              :headers="headers"
              multi-sort
              :items="tableResults"
              item-key="uuid"
              class="elevation-1 fill-height"
              :search="searchText">

    <template v-slot:top="">
        <v-text-field v-model="searchText"
                      label="Search"
                      clearable
                      append-outer-icon="mdi-refresh"
                      @click:append-outer="doSearch"
                      class="mx-4"></v-text-field>
    </template>
    <template v-slot:[`item.id`]="{ item }">
        {{item.context_code}}{{item.display_id}}
    </template>
    <template v-slot:[`item.time_printed`]="{ item }">
        <v-tooltip left>
            <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs"
                        v-on="on"
                        v-show="item.time_printed != null">mdi-printer-check</v-icon>
            </template>
            <span>{{item.time_printed}}</span>
        </v-tooltip>
    </template>
    <template v-slot:[`item.time_checked_in`]="{ item }">
        <v-tooltip left>
            <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs"
                        v-on="on"
                        v-show="item.time_checked_in != null">mdi-account-check</v-icon>
            </template>
            <span>{{item.time_checked_in}}</span>
        </v-tooltip>

    </template>
    <template v-slot:[`item.uuid`]="{ item }">
        <v-btn @click="selectedBadge = item">Review</v-btn>
    </template>
</v-data-table>
</template>

<script>
import admin from '../api/admin';
import {
    debounce
} from '@/plugins/debounce';
export default {
    components: {},
    props: ['listPath', 'context', 'actions', 'listAddHeaders', 'listRemoveHeaders'],
    data: () => ({

        searchText: "",
        loading: false,
        tableOptions: {},
        tableResults: [],
        totalResults: 0,
        defHeaders: [{
                text: 'ID',
                align: 'start',
                value: 'id',
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
            {
                text: 'Printed',
                value: 'time_printed',
            },
            {
                text: 'Checked-In',
                value: 'time_checked_in',
            },
            {
                text: 'Actions',
                value: 'uuid',
            },
        ]
    }),
    computed: {
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        headers() {
            var result = this.defHeaders || [];
            var rmv = this.listRemoveHeaders || [];
            var inc = this.listAddHeaders || [];
            var that = this;
            result = result.filter(item => !rmv.includes(item.value)).concat(inc);
            //Ensure the "Actions" header is last
            var actionsIx = result.findIndex(item => item.value == 'uuid');
            if (actionsIx > -1)
                result.push(result.splice(actionsIx, 1)[0]);
            return result;
        }
    },
    methods: {

        doSearch: function() {
            this.loading = true;
            const pageOptions = [
                'sortBy',
                'sortDesc',
                'page',
                'itemsPerPage'
            ].reduce((a, e) => (a[e] = this.tableOptions[e], a), {});;
            admin.genericGetList(this.authToken, 'Attendee/Badge', {
                "find": this.searchText,
                ...pageOptions
            }, (results, total) => {
                this.tableResults = results;
                this.totalResults = total;
                this.loading = false;
            })
        }
    },
    watch: {

        searchText: debounce(function(newSearch) {
            this.doSearch();
        }, 500),
        tableOptions: {
            handler() {
                this.doSearch()
            },
            deep: true,
        }
    }
};
</script>
