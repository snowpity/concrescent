<template>
<v-card>
    <v-btn>Show All</v-btn>
    <v-treeview :items="OrgChart"
                item-key="tid"
                :open.sync="OrgChartOpened"
                dense
                open-on-click>

        <template v-slot:prepend="{ item, open }">
            <v-icon v-if="item.type=='department'">
                {{ open ? 'mdi-account-group' : 'mdi-account-group-outline' }}
            </v-icon>
            <v-icon v-else-if="item.type=='position'">
                {{ open ? 'mdi-account-supervisor-circle' : 'mdi-account-supervisor-circle-outline' }}
            </v-icon>
            <v-icon v-else-if="item.is_exec == 1">
                mdi-crown
            </v-icon>
            <v-icon v-else>
                mdi-account-circle
            </v-icon>
        </template>
        <template v-slot:label="{ item }">
            <b v-if="item.type!='staff'">
                {{item.name}}
            </b>
            <v-container v-else>

                <v-row>
                    <v-col>
                        {{item.real_name}}
                    </v-col>
                    <v-col>
                        {{item.fandom_name}}
                    </v-col>
                    <v-col>
                        {{item.application_status}}
                    </v-col>
                </v-row>

            </v-container>
        </template>
        <template v-slot:append="{ item }">
            <v-btn color="primary"
                   @click.stop='selectedItem = item'
                   icon
                   dark>
                <v-icon>
                    mdi-information
                </v-icon>
            </v-btn>
        </template>
    </v-treeview>
    <v-dialog v-model="detailsDialog"
              scrollable>
        <v-card>
            <v-card-title>Details of {{selectedItem.type}}</v-card-title>
            <v-divider></v-divider>
            <v-card-text>
                <v-container v-if="selectedItem.type=='department'">
                    <v-row>
                        <h3>Name:</h3><br />
                        <span>{{selectedItem.name}}</span>
                    </v-row>
                    <v-row>
                        <h3>Primary Email:</h3><br />
                        <span>{{selectedItem.email_primary}}</span>
                    </v-row>
                    <v-row>
                        <h3>Secondary Email:</h3><br />
                        <span>{{selectedItem.email_secondary}}</span>
                    </v-row>
                    <v-row>
                        <h3>Description:</h3><br />
                        <span>{{selectedItem.description}}</span>
                    </v-row>
                </v-container>
                <v-container v-else-if="selectedItem.type=='position'">
                    <v-row>
                        <h3>Name:</h3><br />
                        <span>{{selectedItem.name}}</span>
                    </v-row>
                    <v-row>
                        <h3>Desired head count:</h3><br />
                        <span>{{selectedItem.desired_count}}</span>
                    </v-row>
                    <v-row>
                        <h3>Description:</h3><br />
                        <span>{{selectedItem.description}}</span>
                    </v-row>
                </v-container>
                <v-container v-else>
                    <v-row>
                        <h3>Real Name:</h3><br />
                        <span>{{selectedItem.real_name}}</span>
                    </v-row>
                    <v-row>
                        <h3>Fandom Name:</h3><br />
                        <span>{{selectedItem.fandom_name}}</span>
                    </v-row>
                    <v-row>
                        <h3>Display on badge:</h3><br />
                        <span>{{selectedItem.name_on_badge}}</span>
                    </v-row>
                    <v-row>
                        <h3>Application status:</h3><br />
                        <span>{{selectedItem.application_status}}</span>
                    </v-row>
                    <v-row>
                        <h3>Badge ID:</h3><br />
                        <span>S{{selectedItem.display_id}}</span>
                    </v-row>
                    <v-row>
                        <h3>Contact Email:</h3><br />
                        <span>{{selectedItem.email_address}}</span>
                    </v-row>
                    <v-row>
                        <h3>Contact Phone:</h3><br />
                        <span>{{selectedItem.phone_number}}</span>
                    </v-row>
                </v-container>
            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions>
                <v-spacer />
                <v-btn color="primary"
                       @click="detailsDialog = false">
                    Close
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog v-model="loading"
              width="200"
              height="200"
              close-delay="1200"
              content-class="elevation-0"
              persistent>
        <v-card-text class="text-center overflow-hidden">
            <v-progress-circular :size="150"
                                 class="mb-0"
                                 indeterminate />
        </v-card-text>
    </v-dialog>
</v-card>
</template>
<script>
import {
    mapActions
} from 'vuex';
import admin from '../../api/admin';
import {
    debounce
} from '@/plugins/debounce';

function openChildren(item, subitem) {
    var results = [];
    var children = subitem ? (item.hasOwnProperty('children') ? item.children : []) : item;
    if (subitem) {
        if (children.length > 0) {
            results.push(item.tid);
        }
    }
    children.forEach((item) => {
        results.push(...openChildren(item, true));
    });

    return results;
}


export default {
    components: {

    },
    props: [
        'subTabIx'
    ],
    data: () => ({

        OrgChart: [],
        OrgChartOpened: [],
        selectedItem: {},

        detailsDialog: false,
        loading: false,

    }),
    computed: {
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        OrgChartFullOpenIDs() {
            if (this.OrgChart.length > 0) {
                return openChildren(this.OrgChart);
            }
            return [];
        }
    },
    methods: {
        checkPermission() {
            console.log('Hey! Listen!');
            this.getOrgChart();
        },
        getOrgChart: function() {
            this.loading = true;
            admin.genericGetList(this.authToken, "Staff/OrgChart", null, (results, total) => {
                this.OrgChart = results;
                this.loading = false;

                //Todo: Pay attention if they want it all open by default?
                this.OrgChartOpened = this.OrgChartFullOpenIDs;
            })
        },
    },
    watch: {
        $route() {
            this.$nextTick(this.checkPermission);
        },
        selectedItem(item) {
            if (item != null)
                this.detailsDialog = true;
        }
    },
    created() {
        this.checkPermission();
        //this.doSearch();
    }
};
</script>
