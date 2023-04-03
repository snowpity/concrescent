<template>
<div>
    <v-tooltip bottom>
        <template v-slot:activator="{ on, attrs }">
            <v-btn icon
                   v-bind="attrs"
                   v-on="on">
                <v-badge :value="queueTotal"
                         :content="queueTotal">
                    <v-icon>mdi-{{printIcon}}</v-icon>
                </v-badge>
            </v-btn>
        </template>
        <v-card>
            Status: {{runState}}
            <v-list dense>
                <v-list-item v-for="(q,i) in queue"
                             :key="i">
                    <v-list-item-icon>{{q.id}}</v-list-item-icon>
                    <v-list-item-content>{{q.format_id}}</v-list-item-content>
                </v-list-item>
            </v-list>
        </v-card>

    </v-tooltip>
    <v-dialog v-model="printPanel"
              eager
              fullscreen
              transition="none">
        <v-card :class="{'printing':printPanel}">
            <badgeFullRender :format="selectedBadgeFormat"
                             :badge="selectedBadge" />
        </v-card>
    </v-dialog>
</div>
</template>

<script>
import admin from '../../api/admin';
import badgeFullRender from '@/components/badgeFullRender.vue';
import {
    debounce
} from '@/plugins/debounce';
import {
    mapState,
    mapGetters,
    mapActions
} from 'vuex';
export default {
    components: {
        badgeFullRender
    },
    props: {},
    data() {
        return {
            queue: [],
            queueTotal: 0,
            //Can be Ready, Polling, Printing, Paused
            runState: 'Ready',
            PollTimer: null,
            PrintTimer: null,
            printPanel: false,
            selectedBadge: {},
            selectedBadgeFormat: {},
            cachedFormats: []
        };
    },
    methods: {
        PollJobs: async function() {
            //this.printPanel = !this.printPanel;
            //Prevent re-entry
            if (this.runState != 'Ready')
                return;
            this.runState = 'Polling'

            admin.genericGetList(this.authToken, 'Badge/PrintJob', {
                    full: true,
                    state: 'Queued',
                    stationName: this.printerName,
                    itemsPerPage: 5
                },
                (queue, queueTotal) => {
                    this.queue = queue;
                    this.queueTotal = queueTotal;
                    this.runState = queueTotal > 0 ? 'Printing' : 'Ready';
                    if (queueTotal) {
                        this.PrintNextJob();
                    }
                }, (err) => {
                    //Shrug?
                    console.log('Print Daemon error', err)
                    this.runState = 'Ready';
                })
        },
        PrintNextJob: function() {
            if (this.cJob == undefined) {
                this.runState = 'Ready';
                this.printPanel = false;
                console.log('Daemon: Done printing')
                return;
            }

            if (this.cBadgeFormat == undefined) {
                this.FetchFormat();
                return;
            }
            this.printPanel = true;
            this.selectedBadge = this.cJob.data;
            this.selectedBadgeFormat = this.cBadgeFormat;

            //Print and close
            setTimeout(() => {
                window.print();

                this.PostPrint();

            }, 130);

        },
        PostPrint: function(completedLocally) {

            admin.genericPost(this.authToken, "Badge/PrintJob/" + this.cJob.id, {
                state: 'Completed',
            }, (printJob) => {
                this.queue.shift();
                this.queueTotal--;
            })
            setTimeout(() => {
                this.PrintNextJob();
            }, this.printConfig.cycleDelay);
        },
        FetchFormat: function() {
            if (this.cBadgeFormat != undefined)
                return;
            if (this.cJob == undefined) return;
            console.log('Daemon: Fetching format', this.cJob.format_id)

            admin.genericGet(this.authToken, 'Badge/Format/' + this.cJob.format_id, null, (format) => {
                console.log('Received format map', format)
                this.cachedFormats.push(format);
                console.log('cachedFormats', this.cachedFormats)
                this.PrintNextJob()
            }, (err) => {
                console.log('Could not load badge format', err)
                this.runState = 'Paused'
            })
        }
    },
    watch: {},
    computed: {
        ...mapState({
            printConfig: (state) => state.station.printConfig,
            printerName: (state) => state.station.servicePrintJobsAs,
        }),
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        printIcon: function() {
            let result = 'printer-pos';
            switch (this.runState) {
                case 'Ready':
                    //nothing
                    break;
                case 'Polling':
                    result += '-refresh';
                    break;
                case 'Printing':
                    result += '-play';
                    break;
                case 'Paused':
                    result += '-pause';
                    break;
            }
            return result;
        },
        cJob: function() {
            let a = this.queue;
            return a[0];
        },
        cBadgeFormat() {
            if (this.cJob == undefined) return undefined;
            return this.cachedFormats.find((i) => i.id == this.cJob.format_id);
        },
        // selectedBadge() {
        //     if (this.cJob == undefined) return {};
        //     return this.cJob.data;
        //
        // },
    },
    mounted() {
        console.log('Running Print Daemon')

        this.PollTimer = setInterval(() => this.PollJobs(), 10000);

    },
    beforeDestroy: function() {
        console.log('Shutting down Print Daemon')
        clearInterval(this.PollTimer);
        this.printPanel = false;

    },

};
</script>

<style scoped>
</style>
