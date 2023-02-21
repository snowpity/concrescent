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
        },
    },
    data() {
        return {
            searchText: "",
            loading: false,
            showConfig: false,
            tableOptions: {},
            tableResults: [],
            totalResults: 0,
            questions: [],
            displayedQuestions: []
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
            if (this.displayedQuestions.length) pageOptions['questions'] = this.displayedQuestions.map(x => x.id).join(',');
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
        },
        doRefreshQuestions: function() {
            console.log('bsl refreshing questions', this.context_code);
            admin.genericGetList(this.authToken, 'Form/Question/' + this.context_code, null, (results, total) => {
                this.questions = results;

                var initialQuestions = this.questions.filter(q => q.listed);
                //TODO: Apply personal preferences
                this.displayedQuestions = initialQuestions;
            })
        }
    },
    watch: {

        searchText: debounce(function(newSearch) {
            this.doSearch();
        }, 500),
        displayedQuestions: debounce(function(newSearch) {
            this.doSearch();
        }, 500),
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
