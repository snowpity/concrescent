<template>
<v-container>
    <v-text-field v-model="searchText"
                  label="Search"
                  clearable
                  append-outer-icon="mdi-refresh"
                  @click:append-outer="doSearch"
                  class="mx-4"></v-text-field>
    <v-treeview :items="treeItems"
                :search="searchText"
                activatable
                :item-key="valueKey"
                open-on-click>
        <template v-slot:top="">
        </template>
        <template v-slot:append="{ item }">

            <v-btn v-for="action in actions"
                   :key="action.name"
                   @click="doEmit(action.name, item)">{{action.text}}</v-btn>
        </template>
        <template v-slot:[`footer.prepend`]>
        </template>
    </v-treeview>
    <v-btn v-for="action in footerActions"
           :key="action.name"
           :color="action.color"
           @click="doEmit(action.name)"
           class="ma-2">{{action.text}}</v-btn>
</v-container>
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
        'valueParentKey': {
            type: String,
            default: 'parent_id'
        },
        'AddHeaders': {},
        'RemoveHeaders': {},
        'footerActions': {},
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

        searchText: "",
        loading: false,
        tableOptions: {
            "page": 1,
            "itemsPerPage": 10,
            "sortBy": [],
            "sortDesc": [false],
            "groupBy": [],
            "groupDesc": [],
            "mustSort": false,
            "multiSort": true
        },
        tableResults: [],
        totalResults: 0,
        defHeaders: [{
            text: 'ID',
            align: 'start',
            value: 'id',
        }, {
            text: 'Actions',
            value: 'actions',
        }, ],
        pagination: {},
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

        treeItems() {
            //Adapted from https://stackoverflow.com/a/40732240
            const hashTable = Object.create(null);

            const idn = this.valueKey;
            const idnp = this.valueParentKey;
            this.tableResults.forEach(aData => hashTable[aData[idn]] = {
                ...aData,
                children: []
            });
            const dataTree = [];
            this.tableResults.forEach(aData => {
                if (aData[idnp]) hashTable[aData[idnp]].children.push(hashTable[aData[idn]])
                else dataTree.push(hashTable[aData[idn]])
            });
            return dataTree;

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
            ].reduce((a, e) => (a[e] = this.tableOptions[e], a), {});;
            admin.genericGetList(this.authToken, this.apiPath, {
                "find": this.searchText,
                ...pageOptions
            }, (results, total) => {
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
        tableOptions: {
            handler() {
                this.doSearch()
            },
            deep: true,
        }
    },
    created() {
        this.doSearch();
    }
};
</script>
