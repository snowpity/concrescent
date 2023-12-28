<template>
<v-data-table :options.sync="tableOptions"
              :server-items-length="totalResults"
              :loading="loading"
              :headers="headers"
              multi-sort
              :items="tableResults"
              :item-key="internalKey"
              class="elevation-1 fill-height"
              :show-expand='showExpand'
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
        <v-spacer/>
        <v-dialog
            v-model="isExporting"
            scrollable
            max-width="500"
            persistent
        >
            <template v-slot:activator="{ on, attrs }">
            <v-btn v-if="showExport"
                color="blue lighten-2"
                dark
                v-bind="attrs"
                v-on="on"
                class="ma-2"
            >
                Export
            </v-btn>
            </template>

            <v-card>
                <v-card-title class="headline">Export List</v-card-title>
                <v-divider></v-divider>
                <v-card-text>
                <v-container>
                    <v-row>
                        <v-col
                        cols="12"
                        sm="8">
                        <p>Format</p>

                        <v-btn-toggle v-model="optionExportFormat">
                            <v-btn value="json">
                            JSON
                            </v-btn>
                            <v-btn value="csv">
                            CSV
                            </v-btn>
                            <v-btn value="xls">
                            XLS (HTML)
                            </v-btn>
                            </v-btn-toggle>
                        </v-col>
                        <v-col
                        cols="12"
                        sm="4">
                        <p>Internal Header names</p>

                        <v-switch
                            v-model="optionExportRawHeaders"
                            ></v-switch>
                        </v-col>
                </v-row>
                </v-container>
                </v-card-text>

                <v-divider></v-divider>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="default"
                            @click="isExporting = false">Cancel</v-btn>
                    <v-btn color="primary"
                            :loading="loading"
                            @click="doExport">Export!</v-btn>
                </v-card-actions>

            </v-card>
        </v-dialog>
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
import exportFromJSON from 'export-from-json';
export default {
    components: {},
    props: {
        'apiPath': {
            type: String
        },
        'apiAddParams': {
            type: Object,
            default () {
                return {};
            }
        },
        'search': {
            type: String,
            default () {
                return '';
            }
        },
        'actions': {
            type: Array
        },
        'internalKey': {
            type: String,
            default () {
                return 'id';
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
        'showExport':{
            type: Boolean
        },
    },
    data() {
        return {

            searchText: this.search || '',
            loading: false,
            isExporting:false,
            tableOptions: {},
            tableResults: [],
            totalResults: 0,
            optionExportFormat:'csv',
            optionExportRawHeaders: false,
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
        pageOptionsForGet: function(){
            const pageOptions = [
                'sortBy',
                'sortDesc',
                'page',
                'itemsPerPage'
            ].reduce((a, e) => (a[e] = this.tableOptions[e], a),  {...this.apiAddParams});
            if (this.searchText) pageOptions['find'] = this.searchText;
            if (this.context_code) pageOptions['context_code'] = this.context_code;
            //If exporting, force pagination to all
            if(this.isExporting) {
                pageOptions['itemsPerPage'] = -1;
                pageOptions['page'] = 1;
            }
            return pageOptions;
        },
        isSorting() {
            return this.tableOptions.sortBy.length > 0;
        },
    },
    methods: {

        doSearch: function() {
            this.loading = true;
            admin.genericGetList(this.authToken, this.apiPath, this.pageOptionsForGet, (results, total) => {
                this.tableResults = results;
                this.totalResults = total;
                this.loading = false;
            })
        },
        doExport: function() {
            this.loading = true;
            console.log('doSearch pageOptions', this.pageOptionsForGet);
            admin.genericGetList(this.authToken, this.apiPath, this.pageOptionsForGet, (results, total) => {
                this.loading = false;
                
                const fileName = 'Export';
                const exportType =  this.optionExportFormat;
                if(!this.optionExportRawHeaders){
                    results = this.makeHeadersPretty(results);
                }
                exportFromJSON({
                    data:results,
                    fileName,
                    exportType,
                    withBOM:true
                    });
            })            
        },
        doEmit: function(eventName, item) {
            this.$emit(eventName, item);
        },
        makeHeadersPretty(input) {
            if (typeof input !== 'object') return input;
            if (Array.isArray(input)) return input.map(this.makeHeadersPretty,this);
            var that = this;
            return Object.keys(input).reduce(function (newObj, key) {
                let val = input[key];
                let newVal = (typeof val === 'object') && val !== null ? that.makeHeadersPretty.call(that,val) : val;
                //find new key
                var newKeyObj = that.headers.find((header) => {
                    if (header==key) return true;
                    if(header.value == key) return true;
                })
                if(typeof newKeyObj == 'string') newKeyObj = {value:newKeyObj, text:newKeyObj};
                if(typeof newKeyObj == 'undefined') newKeyObj = {value:key, text:key};
                newObj[newKeyObj.text] = newVal;
                return newObj;
            }, {});
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
