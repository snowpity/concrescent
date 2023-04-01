<template>
<v-container fluid
             fill-height>
    <v-layout align-center
              justify-center>

        <v-card class="px-4">
            <v-card-text>
                <v-checkbox label="Is this a kiosk?"
                            v-model="kioskMode" />
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
        }
    },
    watch: {},
    components: {
        //    HelloWorld
    },
};
</script>
