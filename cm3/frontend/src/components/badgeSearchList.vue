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
        <v-btn v-for="action in actions"
               :key="action.name"
               @click="doEmit(action.name, item)">{{action.text}}</v-btn>
    </template>
    <!--courtesy https://gist.github.com/loilo/73c55ed04917ecf5d682ec70a2a1b8e2 -->
    <slot v-for="(_, name) in $slots"
          :name="name"
          :slot="name" />
    <template v-for="(_, name) in $scopedSlots"
              :slot="name"
              slot-scope="slotData">
        <slot :name="name"
              v-bind="slotData" />
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
    props: {
        'apiPath': {
            type: String
        },
        'context_code': {
            type: String
        },
        'actions': {
            type: Array
        },
        'headerKey': {
            type: Object,
            default () {
                return {
                    text: 'ID',
                    align: 'start',
                    value: 'id',
                };
            }
        },
        'headerFirst': {
            type: Object,
            default () {
                return {
                    text: 'Real Name',
                    value: 'real_name',
                };
            }
        },
        'headerSecond': {
            type: Object,
            default () {
                return {
                    text: 'Fandom Name',
                    value: 'fandom_name',
                };
            }
        },
        'AddHeaders': {
            type: Array
        },
        'RemoveHeaders': {
            type: Array
        },
        'isEditingItem': {
            type: Boolean
        }
    },
    data() {
        return {

            searchText: "",
            loading: false,
            tableOptions: {},
            tableResults: [],
            totalResults: 0

        }
    },
    computed: {
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        headers() {
            var result = [
                this.headerKey,
                this.headerFirst,
                this.headerSecond,
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
            ];
            var rmv = this.RemoveHeaders || [];
            var inc = this.AddHeaders || [];
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
            ].reduce((a, e) => (a[e] = this.tableOptions[e], a), {});
            if (this.searchText) pageOptions['find'] = this.searchText;
            if (this.context_code) pageOptions['context_code'] = this.context_code;
            admin.genericGetList(this.authToken, this.apiPath, pageOptions, (results, total) => {
                this.tableResults = results;
                this.totalResults = total;
                this.loading = false;
            })
        },
        doEmit: function(eventName, item) {
            this.$emit(eventName, item);
        }
    },
    watch: {

        searchText: debounce(function(newSearch) {
            this.doSearch();
        }, 500),
        isEditingItem: debounce(function(newEditing) {
            if (!newEditing)
                this.doSearch();
        }, 200),
        context_code: debounce(function(newCode) {
            this.doSearch();
        }, 20),
        tableOptions: {
            handler() {
                this.doSearch()
            },
            deep: true,
        },
        apiPath() {
            this.doSearch();
        }
    }
};
</script>
