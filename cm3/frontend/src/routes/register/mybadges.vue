<template>
<v-container :class="{'pa-2':true,'printing':!printingBadge}"
             fluid>
    <v-row>
        <p>
            <i v-if="!ownedbadgecount">You have no badges. Click on the link in your confirmation email, or Add one.</i>
        </p>
    </v-row>
    <v-row>
        <v-col cols="12"
               md="6"
               lg="4"
               xl="3"
               v-for="(badge, idx) in ownedbadges"
               :key="badge['uuid']">
            <v-card>
                <badgeSampleRender :badge="badge" />
                <v-card-actions>
                    {{badge['badge_type_name']}}

                    <v-spacer></v-spacer>
                    <v-btn icon
                           @click.stop="displayBadge = idx">
                        <v-icon>mdi-information</v-icon>
                    </v-btn>
                    <v-btn icon
                           :to="{name:'editbadge', params: {editIx: idx}}">
                        <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                </v-card-actions>

                <v-card-actions v-for="addon in badge.addons"
                                :key="addon['addon_id']">
                    <v-icon>mdi-plus</v-icon>
                    <div class="text-truncate">
                        {{addon['name']}}
                    </div>
                </v-card-actions>
            </v-card>
        </v-col>
    </v-row>
    <v-row>
        <v-col>
            <v-btn color="primary"
                   :to="{name:'addbadge', params: {cartIx: 0}}"
                   left
                   absolute>Add a badge</v-btn>
        </v-col>
        <v-spacer></v-spacer>
    </v-row>

    <v-dialog v-model="displayBadgeModal"
              max-width="600"
              persistent
              scrollable
              :hide-overlay="printingBadge"
              :fullscreen="printingBadge">
        <v-card :class="{'printing':printingBadge}">
            <v-card-title class="d-print-none">
                <v-btn color="red lighten-1"
                       @click="removeBadge">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn color="blue darken-1"
                       @click="printBadgeInfo">
                    <v-icon>mdi-printer</v-icon>
                </v-btn>
                <v-btn color="success"
                       @click="displayBadge = -1">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>
            <v-card-text>
                <p class="headline">Badge Info</p>
                <p class="text-center">
                    <v-btn height=266
                           width=266
                           elevation=4>
                        <qr-code :text="displayBadgeData ? displayBadgeData['qr_data'] : ''"></qr-code>
                    </v-btn>
                </p>
                <v-row>
                    <v-spacer></v-spacer>
                    <v-card-title>{{displayBadgeData | badgeDisplayName}}</v-card-title>
                    <v-spacer></v-spacer>
                </v-row>
                <v-row>
                    <v-spacer></v-spacer>
                    <h3>{{displayBadgeData | badgeDisplayName(true)}}&zwj;</h3>
                    <v-spacer></v-spacer>
                </v-row>
                <v-card-title class="title">{{displayBadgeData && displayBadgeData['badge-type-name']}}</v-card-title>
                <badgePerksRender :description="displayBadgeProduct ? displayBadgeProduct.description : null "
                                  :rewardlist="displayBadgeProduct ? displayBadgeProduct.rewards : null"></badgePerksRender>
                <v-card-title>Addons purchased:</v-card-title>

                <v-card v-for="addon in (displayBadgeProduct ? displayBadgeData.addons : null)"
                        v-bind:key="addon['id']">
                    <v-card-title>
                        <h3 class="black--text">{{getAddonByID(displayBadgeData.badge_type_id, addon['addon_id']).name}}</h3>
                    </v-card-title>
                    <v-card-text>
                        <badgePerksRender :description="getAddonByID(displayBadgeData.badge_type_id, addon['addon_id']).description"
                                          :rewardlist="getAddonByID(displayBadgeData.badge_type_id, addon['addon_id']).rewards"></badgePerksRender>
                    </v-card-text>
                </v-card>
                <p v-if="displayBadgeProduct && displayBadgeData.addons != undefined && displayBadgeData.addons.length == 0">
                    No addons purchased
                </p>
            </v-card-text>
        </v-card>
    </v-dialog>
    <v-snackbar v-model="displayImportResult"
                :timeout="16000">
        {{ importResult }}
        <v-btn color="primary"
               text
               @click="clearBadgeRetrievalResult">
            Close
        </v-btn>
    </v-snackbar>
</v-container>
</template>


<script>
import {
    mapGetters,
    mapState,
    mapActions
} from 'vuex';

import VueQRCodeComponent from 'vue-qrcode-component';
import badgePerksRender from '@/components/badgePerksRender.vue';
import badgeSampleRender from '@/components/badgeSampleRender.vue';

export default {
    components: {
        'qr-code': VueQRCodeComponent,
        badgePerksRender,
        badgeSampleRender,
    },
    data: () => ({
        promocodeDialog: false,
        promoAppliedDialog: false,
        displayBadge: -1,
        printingBadge: false,
    }),
    computed: {
        ...mapState({
            ownedbadges: (state) => state.mydata.ownedbadges,
            badges: (state) => state.products.badges,
            questions: (state) => state.products.questions,
            addons: (state) => state.products.addons,
            importResult: (state) => state.mydata.BadgeRetrievalResult,
        }),
        ownedbadgecount() {
            return Object.keys(this.ownedbadges).length;
        },
        displayBadgeModal: {
            get() {
                return this.displayBadge != -1;
            },
            set(show) {
                this.displayBadge = show ? 0 : -1;
            }
        },
        displayBadgeData() {
            if (!this.displayBadgeModal) return null;
            return this.ownedbadges[this.displayBadge];
        },
        displayBadgeProduct() {
            if (!this.displayBadgeModal) return null;
            let badgeId = this.displayBadgeData.badge_type_id;
            if (this.badges[this.displayBadgeData.context_code] == undefined) {

                return null;
            }
            let result = this.badges[this.displayBadgeData.context_code].find((item) => {
                return item.id == badgeId
            });
            return result;
        },
        displayImportResult() {
            return this.importResult.length > 0;
        },
    },
    methods: {
        ...mapActions('mydata', [
            'retrieveBadges',
            'retrieveSpecificBadge',
            'retrieveTransactionBadges',
            'clearBadgeRetrievalResult',
        ]),
        ...mapActions('cart', [
            'loadCart',
        ]),
        ...mapActions('products', [
            'getContextBadges',
            'getContextQuestions',
            'getContextAddons',
        ]),
        removeBadge() {

        },
        printBadgeInfo() {
            this.printingBadge = true;
            if (this.printingBadge) {
                (function(app) {
                    setTimeout(() => {
                        window.print();
                        setTimeout(() => {
                            app.printingBadge = false;
                            // Also, spin up a function to zoom back out
                            let viewport = document.querySelector('meta[name="viewport"]');
                            let original = viewport.getAttribute('content');
                            let force_scale = `${original  }, maximum-scale=0.99`;
                            viewport.setAttribute('content', force_scale);
                            setTimeout(() => {
                                viewport.setAttribute('content', original);
                            }, 100);
                        }, 1000);
                    }, 30);
                }(this));
            }

        },
        getAddonByID(badge_type_id, id) {
            if (undefined == this.addons[badge_type_id])
                return {
                    name: '!!Unloaded:' + id,
                    description: "",
                    rewards: {}
                };
            return this.addons[badge_type_id].find(addon => addon.id == id);
        }
    },
    watch: {
        displayBadge: function(newBadgeId) {
            if (newBadgeId > -1) {
                //Make sure we have context for it
                this.getContextBadges(this.displayBadgeData.context_code);
                this.getContextQuestions(this.displayBadgeData.context_code);
                this.getContextAddons(this.displayBadgeData.context_code);
            }
        }
    },
    created() {
        let {
            query
        } = this.$route;
        if (query.gid != undefined) {
            this.retrieveTransactionBadges(query);
            // Presumably they're here from a Review Order link or the checkout summary page
            // Which *probably* means it was successful, so... clear the cart!
            this.loadCart(null);
            this.$router.replace({
                ...this.$router.currentRoute,
                query: {}
            });
        }
        if (query.refresh) {
            this.retrieveBadges();
        }
        if (query.context_code) {
            //A specific badge was clicked, load it up
            this.retrieveSpecificBadge(query);
        }
    }
};
</script>
