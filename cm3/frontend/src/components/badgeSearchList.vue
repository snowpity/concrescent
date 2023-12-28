<template>
<v-data-table :options.sync="tableOptions"
              :server-items-length="totalResults"
              :loading="loading"
              :headers="headers"
              multi-sort
              :dense="dense"
              :items="tableResults"
              :item-key="internalKey"
              class="elevation-1 fill-height"
              :show-expand='showExpand'
              :disabled="isExporting"
              :footer-props="{
                itemsPerPageOptions: [5,10,15,25,50,100,-1]
                }"
              >

    <template v-slot:top="">
        <v-container fluid>
            <v-row style="flex-wrap: nowrap;"
                   no-gutters>
                <v-col cols="1"
                       class="flex-grow-1 flex-shrink-0"
                       style="min-width: 100px; max-width: 100%;">
                    <v-text-field v-model="searchText"
                                  label="Search"
                                  clearable
                                  append-outer-icon="mdi-refresh"
                                  @click:append-outer="doSearch"
                                  class="mx-4"></v-text-field>
                </v-col>
                <v-col cols="1">
                    <v-dialog v-model="showConfig"
                              scrollable>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn dark
                                   v-bind="attrs"
                                   v-on="on">
                                <v-icon>mdi-cog</v-icon>
                            </v-btn>
                        </template>

                        <v-card>
                            <v-card-title class="text-h5 grey lighten-2">
                                List configuration
                            </v-card-title>
                            <v-divider></v-divider>

                            <v-card-text>

                                <v-list subheader
                                        two-line
                                        flat>
                                    <v-subheader>Displayed Form questions</v-subheader>
                                </v-list>
                                <v-expansion-panels accordion>

                                    <v-expansion-panel v-for="item in questions"
                                                       :key="item.id">

                                        <v-expansion-panel-header>
                                            <v-list-item>
                                                <v-list-item-action @click.stop="">
                                                    <v-checkbox :value="item"
                                                                v-model="displayedQuestions"
                                                                color="primary"></v-checkbox>
                                                </v-list-item-action>

                                                <v-list-item-content>
                                                    <v-list-item-title>{{item.title}}</v-list-item-title>
                                                    <v-list-item-subtitle></v-list-item-subtitle>
                                                </v-list-item-content>
                                            </v-list-item>

                                        </v-expansion-panel-header>

                                        <v-expansion-panel-content>
                                            Filter options here
                                            <v-list dense>
                                                <v-list-item>
                                                    <v-list-item-content>Order:</v-list-item-content>
                                                    <v-list-item-content class="align-end">
                                                        {{ item.order }}
                                                    </v-list-item-content>
                                                </v-list-item>
                                            </v-list>
                                        </v-expansion-panel-content>
                                    </v-expansion-panel>
                                </v-expansion-panels>





                            </v-card-text>

                            <v-divider></v-divider>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="primary"
                                       @click="showConfig = false">
                                    Ok
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </v-col>
            </v-row>
        </v-container>
    </template>
    <template v-slot:[`item.id`]="{ item }">
        <v-tooltip right>
            <template v-slot:activator="{ on, attrs }">
                <span v-bind="attrs"
                      v-on="on">
                    {{item.context_code}}{{item.display_id}}</span>
            </template>
            {{item.id}}
        </v-tooltip>
    </template>
    <template v-slot:[`item.application_status`]="{ item }">
        <v-tooltip left>
            <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs"
                        v-on="on"
                        :color="applicationStatusColor[item.application_status]"
                        v-if="applicationStatusIcon[item.application_status] != undefined">mdi-{{applicationStatusIcon[item.application_status]}}</v-icon>
                <div v-else>{{item.application_status}}</div>
            </template>
            <span>{{item.application_status}}</span>
        </v-tooltip>
    </template>
    <template v-slot:[`item.payment_status`]="{ item }">
        <v-tooltip left>
            <template v-slot:activator="{ on, attrs }">
                <v-icon v-bind="attrs"
                        v-on="on"
                        :color="paymentStatusColor[item.payment_status]"
                        v-if="paymentStatusIcon[item.payment_status] != undefined">mdi-{{paymentStatusIcon[item.payment_status]}}</v-icon>
                <div v-else>{{item.payment_status}}</div>
            </template>
            <span>{{item.payment_status}}</span>
        </v-tooltip>
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
               :small="dense"
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
        'context_code': {
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
        'dense': {
            type: Boolean
        },
    },
    data() {
        return {
            searchText: this.search,
            loading: false,
            showConfig: false,
            isExporting:false,
            tableOptions: {},
            tableResults: [],
            totalResults: 0,
            questions: [],
            displayedQuestions: [],

            optionExportFormat:'csv',
            optionExportRawHeaders: false,

            //TEMP: Until Payment_Status is in its own component
            paymentStatusIcon: {
                'NotReady': 'alert',
                'AwaitingApproval': 'clock-alert',
                'NotStarted': 'progress-question',
                'Incomplete': 'alert',
                'Cancelled': 'close-octagon',
                'Rejected': 'alert',
                'Completed': 'check-circle',
                'Refunded': 'close-octagon',
                'RefundedInPart': 'check-circle',
            },
            paymentStatusColor: {
                'NotReady': 'grey',
                'AwaitingApproval': '',
                'NotStarted': 'amber',
                'Incomplete': 'amber',
                'Cancelled': 'red',
                'Rejected': 'red',
                'Completed': 'green',
                'Refunded': 'red',
                'RefundedInPart': 'lime',
            },

            //TEMP: Until application_Status is in its own component
            applicationStatusIcon: {
                'InProgress': 'alert',
                'Submitted': 'clock-alert',
                'Cancelled': 'alert',
                'Rejected': 'close-octagon',
                'Terminated': 'close-octagon',
                'Waitlisted': 'progress-question',
                'Accepted': 'check-circle',
                'Onboarding': 'progress-check',
                'Active': 'check-circle',
                'PendingAcceptance': 'progress-check',
            },
            applicationStatusColor: {
                'InProgress': 'grey',
                'Submitted': '',
                'Cancelled': 'amber',
                'Rejected': 'red',
                'Terminated': 'red',
                'Waitlisted': 'amber',
                'Accepted': 'green',
                'Onboarding': 'green',
                'Active': 'green',
                'PendingAcceptance': 'green',
            },
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
                    text: 'Contact Email',
                    value: 'contact_email_address',
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
            //Add in any displayedQuestions
            this.displayedQuestions.forEach((item, i) => {
                result.push({
                    text: item.title,
                    value: 'form_responses[' + item.id + ']'
                })
            });

            //Ensure the "Actions" header is last
            var actionsIx = result.findIndex(item => item.value == 'uuid');
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
            if (this.displayedQuestions.length) pageOptions['questions'] = this.displayedQuestions.map(x => x.id).join(',');
            if (this.searchText) pageOptions['find'] = this.searchText;
            if (this.context_code) pageOptions['context_code'] = this.context_code;
            //If exporting, force pagination to all
            if(this.isExporting) {
                pageOptions['itemsPerPage'] = -1;
                pageOptions['page'] = 1;
            }
            return pageOptions;
        }
    },
    methods: {

        doSearch: function() {
            this.loading = true;
            console.log('doSearch pageOptions', this.pageOptionsForGet);
            admin.genericGetList(this.authToken, this.apiPath, this.pageOptionsForGet, (results, total) => {
                this.tableResults = results;
                this.totalResults = total;
                this.loading = false;

                //If this looks like a badge scan, and we had exactly one result, emit it
                if (results.length == 1) {
                    let r = results[0];
                    let resultQR = 'CM*' + r.context_code + r.display_id + '*' + r.uuid;
                    console.log('Looking for code', resultQR)
                    if (this.searchText == resultQR) {
                        console.log('QR code match', r)
                        this.$emit('qrmatch', r);
                    }
                }
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
        doRefreshQuestions: function() {
            if (this.context_code == undefined) return;
            console.log('bsl refreshing questions', this.context_code);
            admin.genericGetList(this.authToken, 'Form/Question/' + this.context_code, null, (results, total) => {
                this.questions = results;

                var initialQuestions = this.questions.filter(q => q.listed);
                //TODO: Apply personal preferences
                this.displayedQuestions = initialQuestions;
            })
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
            //this.searchText = newSearch;
        },
        searchText: debounce(function(newSearch) {
            this.doSearch();
            console.log('searchText', newSearch)
            //this.$emit('update:search', newSearch);
        }, 500),
        displayedQuestions: debounce(function(newSearch) {
            this.doSearch();
        }, 2500),
        isEditingItem: debounce(function(newEditing) {
            if (!newEditing)
                this.doSearch();
        }, 200),
        context_code: debounce(function(newCode) {
            this.doRefreshQuestions();
            this.doSearch();
        }, 20),
        tableOptions: {
            handler() {
                this.doSearch()
            },
            deep: true,
        },
        apiPath() {
            this.doRefreshQuestions();
            this.doSearch();
        }
    },
    mounted() {
        this.doRefreshQuestions();
    }
};
</script>
