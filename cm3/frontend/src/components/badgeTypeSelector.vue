<template>
<div>
    <div v-if="badges.length == 0">
        <h1>{{no-data-text}}</h1>
    </div>
    <div class="d-none d-sm-block">
        <v-slide-group v-model="selectedBadge"
                       class="pa-4"
                       :class="{warning:isProbablyDowngrading}"
                       show-arrows
                       mandatory
                       center-active>
            <v-icon slot="prev"
                    size="100">mdi-chevron-left</v-icon>
            <v-icon slot="next"
                    size="100">mdi-chevron-right</v-icon>
            <v-slide-item v-for="(product,idx) in badges"
                          :key="product.id"
                          v-slot:default="{ active, toggle }"
                          :value="idx">
                <v-card :dark="active"
                        :color="active ? 'primary' : 'grey lighten-1'"
                        class="ma-4"
                        min-width="220"
                        @click="toggle"
                        :disabled="product.quantity != null && !product.quantity">
                    <v-card-title align="center"
                                  justify="center">
                        {{ product.name | subname}}
                    </v-card-title>
                    <v-card-text>
                        {{ product.name | subname(true)}}&nbsp;
                    </v-card-text>
                    <v-card-actions>&nbsp;
                        <h4 text
                            v-if="product.quantity_remaining">Only
                            {{product.quantity_remaining}}
                            left!
                        </h4>
                        <h4 v-else-if="product.quantity_remaining == 0">Sold out!</h4>
                        <v-spacer></v-spacer>
                        <v-btn color="green"
                               dark>{{product.price | currency}}</v-btn>
                    </v-card-actions>

                </v-card>
            </v-slide-item>
        </v-slide-group>
    </div>
    <div class="d-block d-sm-none">
        <v-select v-model="selectedBadge"
                  :items="badges"
                  label="Select Badge"
                  :item-value="badgeIndex"
                  :item-disabled="quantityZero"
                  :class="{warning:isProbablyDowngrading}">
            <template v-slot:item="{item}">
                <v-card color="grey lighten-1"
                        class="ma-4"
                        min-width="220"
                        :disabled="item.quantity != null && !item.quantity">
                    <v-card-title align="center"
                                  justify="center">
                        {{ item.name | subname}}
                    </v-card-title>
                    <v-card-text>
                        {{ item.name | subname(true)}}&nbsp;
                    </v-card-text>
                    <v-card-actions>&nbsp;
                        <h4 text
                            v-if="item.quantity_remaining">Only
                            {{item.quantity_remaining}}
                            left!
                        </h4>
                        <h4 v-else-if="item.quantity_remaining == 0">Sold out!</h4>
                        <v-spacer></v-spacer>
                        <v-btn color="green"
                               dark>{{item.price | currency}}</v-btn>
                    </v-card-actions>

                </v-card>
            </template>
            <template v-slot:selection="{ item }">
                <v-card dark
                        color="primary"
                        class="ma-4"
                        min-width="220"
                        :disabled="item.quantity != null && !item.quantity">
                    <v-card-title align="center"
                                  justify="center">
                        {{ item.name | subname}}
                    </v-card-title>
                    <v-card-text>
                        {{ item.name | subname(true)}}&nbsp;
                    </v-card-text>
                    <v-card-actions>&nbsp;
                        <h4 text
                            v-if="item.quantity_remaining">Only
                            {{item.quantity_remaining}}
                            left!
                        </h4>
                        <h4 v-else-if="item.quantity_remaining == 0">Sold out!</h4>
                        <v-spacer></v-spacer>
                        <v-btn color="green"
                               dark>{{item.price | currency}}</v-btn>
                    </v-card-actions>

                </v-card>
            </template>
        </v-select>
    </div>
    Prior: {{editBadgePriorBadgeId}}
</div>
</template>

<script>
import badgePerksRender from '@/components/badgePerksRender.vue';
export default {
    components: {},
    props: ['value', 'badges', 'no-data-text', 'readonly', 'editBadgePriorBadgeId'],
    data: () => ({
        selectedBadge: null,
    }),
    computed: {
        isUpdatingItem() {
            return this.editBadgePriorBadgeId != undefined || this.editBadgePriorBadgeId > -1;
        },

        isProbablyDowngrading() {
            if (!this.isUpdatingItem) {
                return false;
            }

            const oldBadge = this.badges.find((badge) => badge.id == this.editBadgePriorBadgeId);
            const selectedBadge = this.badges[this.selectedBadge];
            return typeof oldBadge !== 'undefined' &&
                typeof selectedBadge !== 'undefined' &&
                parseFloat(oldBadge.originalprice) > parseFloat(selectedBadge.originalprice);
        },
    },
    methods: {
        quantityZero(item) {
            return item.quantity == 0;
        },
        badgeIndex(item) {
            return this.badges.indexOf(item);
        },
    },
    watch: {
        selectedBadge(newData) {
            this.$emit('input', newData);
        },
        value(newValue) {
            //Splat the input into the form
            this.selectedBadge = newValue;
        }
    },
    created() {
        this.selectedBadge = this.value;
    }
};
</script>
