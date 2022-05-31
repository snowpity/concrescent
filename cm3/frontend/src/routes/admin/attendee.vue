<template>
<v-container fluid
             fill-height>

    <v-row class="fill-height">
        <v-col>

            <v-tabs-items :value="subTabIx"
                          touchless>
                <v-tab-item key="0">
                    <badgeSearchList listPath="Attendee/Badge"
                                     context="A"
                                     :listAddHeaders="listAddHeaders"
                                     :listRemoveHeaders="listRemoveHeaders" />
                </v-tab-item>
                <v-tab-item key="1">
                    Badge Types here
                </v-tab-item>
            </v-tabs-items>
        </v-col>
    </v-row>
</v-container>
</template>
<script>
import {
    mapActions
} from 'vuex';
import admin from '../../api/admin';
import {
    debounce
} from '@/plugins/debounce';
import badgeSearchList from '@/components/badgeSearchList.vue';

export default {
    components: {
        badgeSearchList,

    },
    props: [
        'subTabIx'
    ],
    data: () => ({
        listRemoveHeaders: [
            'application_status'
        ],
        listAddHeaders: [{
            text: 'Secondary Email',
            value: 'notify_email'
        }],

    }),
    computed: {
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        }
    },
    methods: {
        checkPermission: () => {
            console.log('Hey! Listen!');
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
