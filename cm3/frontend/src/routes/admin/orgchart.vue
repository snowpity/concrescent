<template>
<v-tabs-items :value="subTabIx"
              touchless>

    <v-tab-item key="0">
        <v-container>
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
                    <v-btn v-if="item.type=='staff'">
                        view
                    </v-btn>

                </template>
            </v-treeview>
        </v-container>
    </v-tab-item>
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
</v-tabs-items>
<!-- <v-container fluid
             fill-height>

    <v-row>
        <v-col align-self="start">
        </v-col>
    </v-row>
</v-container> -->
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
    },
    created() {
        this.checkPermission();
        //this.doSearch();
    }
};
</script>
