<template>
<v-container fluid
             fill-height>
    <v-layout align-center
              justify-center>

        <v-card class="px-4">
            <v-card-text>
                <v-container>
                    <v-row>
                        <v-col cols="12"
                               md="8">
                            <v-switch v-model="kioskMode"
                                      :label="`This ${kioskMode?'is':'is not'} a Kiosk.`"></v-switch>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12"
                               md="6">
                            <v-switch v-model="remotePrinting"
                                      :label="`Printing ${remotePrinting?'to remote':'locally'}.`"></v-switch>
                        </v-col>

                        <v-col cols="12"
                               md="6">
                            <v-text-field v-model="preferredRemotePrinter"
                                          label="Remote printer name"></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>

                        <v-col cols="12"
                               md="6">
                            <v-switch v-model="serviceRemoteJobs"
                                      :label="`${serviceRemoteJobs?'Servicing':'Not servicing'} remote jobs.`"></v-switch>
                        </v-col>

                        <v-col cols="12"
                               md="6">
                            <v-text-field v-model="servicePrintJobsAs"
                                          label="Local printer's name"></v-text-field>
                        </v-col>
                    </v-row>
                </v-container>
            </v-card-text>
            <v-card-actions>

            </v-card-actions>
        </v-card>
    </v-layout>
</v-container>
</template>

<script>
// @ is an alias to /src
// import HelloWorld from '@/components/HelloWorld.vue'

import {
    mapGetters,
    mapActions
} from 'vuex'
export default {
    data: function() {
        return {}
    },
    computed: {
        'kioskMode': {
            set(newMode) {
                this.$store.commit('station/setKioskMode', newMode)
            },
            get() {
                return this.$store.state.station.kioskMode
            }
        },
        'remotePrinting': {
            set(newval) {
                this.$store.commit('station/setremotePrinting', newval)
            },
            get() {
                return this.$store.state.station.remotePrinting
            }
        },
        'preferredRemotePrinter': {
            set(newval) {
                this.$store.commit('station/setPreferredRemotePrinter', newval)
            },
            get() {
                return this.$store.state.station.preferredRemotePrinter
            }
        },
        'serviceRemoteJobs': {
            set(newval) {
                this.$store.commit('station/setServiceRemoteJobs', newval)
            },
            get() {
                return this.$store.state.station.serviceRemoteJobs
            }
        },
        'servicePrintJobsAs': {
            set(newval) {
                this.$store.commit('station/setServicePrintJobsAs', newval)
            },
            get() {
                return this.$store.state.station.servicePrintJobsAs
            }
        },
        'printConfig': {
            set(newval) {
                this.$store.commit('station/setPrintConfig', newval)
            },
            get() {
                return this.$store.state.station.printConfig
            }
        },
    },
    watch: {},
    components: {
        //    HelloWorld
    },
};
</script>
