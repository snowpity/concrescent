<template>
<v-autocomplete v-model="model"
                :items="searchResults"
                :loading="loading"
                :search-input.sync="searchText"
                hide-details
                hide-selected
                cache-items
                :item-value="valueKey"
                :label="label"
                solo>
    <template v-slot:no-data>
        <v-list-item>
            <v-list-item-title>
                Start typing...
            </v-list-item-title>
        </v-list-item>
    </template>
    <template v-slot:selection="{ item }">
        <v-list-item-title v-text="item[valueDisplay]"></v-list-item-title>
        <v-list-item-subtitle v-if="valueSubDisplay"
                              v-text="item[valueSubDisplay]"></v-list-item-subtitle>
    </template>
    <template v-slot:item="{ item }">
        <v-list-item-avatar color="indigo"
                            class="text-h5 font-weight-light white--text">
            {{ item[valueKey] }}
        </v-list-item-avatar>
        <v-list-item-content>
            <v-list-item-title v-text="item[valueDisplay]"></v-list-item-title>
            <v-list-item-subtitle v-if="valueSubDisplay"
                                  v-text="item[valueSubDisplay]"></v-list-item-subtitle>
        </v-list-item-content>
        <v-list-item-action>
            <v-btn v-for="action in actions"
                   :key="action.name"
                   @click="doEmit(action.name, item)">{{action.text}}</v-btn>
        </v-list-item-action>
    </template>
    <template v-slot:[`footer.prepend`]>
        <v-btn v-for="action in footerActions"
               :key="action.name"
               :color="action.color"
               @click="doEmit(action.name)"
               class="ma-2">{{action.text}}</v-btn>
    </template>
</v-autocomplete>
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
        'valueKey': {
            type: String,
            default: 'id'
        },
        'valueDisplay': {
            type: String,
            default: 'name'
        },
        'valueSubDisplay': {
            type: String,
            default: null
        },
        'label': {
            type: String,
            default: 'Select an item...'
        },
        'actions': {
            type: Array
        },
        'isEditingItem': {
            type: Boolean
        }
    },
    data: () => ({
        model: null,
        searchText: "",
        loading: false,
        tableOptions: {},
        searchResults: [],
        totalResults: 0,
        defHeaders: [{
            text: 'ID',
            align: 'start',
            value: 'id',
        }, {
            text: 'Actions',
            value: 'actions',
        }, ]
    }),
    computed: {
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        headers() {
            var result = this.defHeaders || [];
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
            // const pageOptions = [
            //     'sortBy',
            //     'sortDesc',
            //     'page',
            //     'itemsPerPage'
            // ].reduce((a, e) => (a[e] = this.tableOptions[e], a), {});;
            const pageOptions = {
                'sortBy': '',
                'sortDesc': '',
                'page': 1,
                'itemsPerPage': 10
            };
            admin.genericGetList(this.authToken, this.apiPath, {
                "find": this.searchText,
                ...pageOptions
            }, (results, total) => {
                this.searchResults = results;
                this.loading = false;
            })
        },
        doEmit: function(eventName, item) {
            this.$emit(eventName, item);
        }
    },
    watch: {
        model(newData) {
            if (this.skipEmitOnce == true) {
                this.skipEmitOnce = false;
                return;
            }
            console.log('emitting dropdown value', newData);
            this.$emit('input', newData);
        },

        searchText: debounce(function(newSearch) {
            newSearch && newSearch !== this.model && this.doSearch();
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
        }
    }
};
</script>
