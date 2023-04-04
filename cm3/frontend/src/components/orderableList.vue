<template>
<v-data-table :options.sync="tableOptions"
              :server-items-length="totalResults"
              :loading="loading"
              :headers="headers"
              multi-sort
              :items="tableResults"
              :item-key="internalKey"
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
    <template v-slot:[`item.actions`]="{ item }">
        <i v-if="!isSorting">
            <v-btn class="ml-2">
                <v-icon>mdi-arrow-up</v-icon>
            </v-btn>
            <v-btn class="mr-2">
                <v-icon>mdi-arrow-down</v-icon>
            </v-btn>
        </i>

        <v-btn v-for="action in actions"
               :key="action.name"
               @click="doEmit(action.name, item)">{{action.text}}</v-btn>
    </template>
    <template v-slot:[`footer.prepend`]>
        <v-btn v-for="action in footerActions"
               :key="action.name"
               :color="action.color"
               @click="doEmit(action.name)"
               class="ma-2">{{action.text}}</v-btn>
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
        'search': {
            type: String,
            default () {
                return '';
            }
        },
        'apiMoveCommand': {
            type: String
        },
        'actions': {
            type: Array
        },
        'internalKey': {
            type: String,
            default () {
                return 'uuid';
            }
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
        'AddHeaders': {
            type: Array
        },
        'RemoveHeaders': {
            type: Array
        },
        'footerActions': {
            type: Array
        },
        'isEditingItem': {
            type: Boolean
        },
        'showExpand': {
            type: Boolean
        },
    },
    data() {
        return {

            searchText: this.search || '',
            loading: false,
            tableOptions: {},
            tableResults: [],
            totalResults: 0,
        };
    },
    computed: {
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        headers() {
            var result = [this.headerKey, {
                text: 'Actions',
                value: 'actions',
            }];
            var rmv = this.RemoveHeaders || [];
            var inc = this.AddHeaders || [];
            var that = this;
            result = result.filter(item => !rmv.includes(item.value)).concat(inc);
            //Ensure the "Actions" header is last
            var actionsIx = result.findIndex(item => item.value == 'actions');
            if (actionsIx > -1)
                result.push(result.splice(actionsIx, 1)[0]);
            return result;
        },
        isSorting() {
            return this.tableOptions.sortBy.length > 0;
        },
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
        search: function(newSearch) {
            this.searchText = newSearch;
        },
        searchText: debounce(function(newSearch) {
            this.doSearch();
            this.$emit('update:search', newSearch);
        }, 500),
        isEditingItem: debounce(function(newEditing) {
            if (!newEditing)
                this.doSearch();
        }, 200),
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
