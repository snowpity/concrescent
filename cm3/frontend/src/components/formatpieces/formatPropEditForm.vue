<template>
<v-card>
    <v-expansion-panels accordion>

        <v-expansion-panel key="1">
            <v-expansion-panel-header>Main properties</v-expansion-panel-header>
            <v-expansion-panel-content>

                <v-list>
                    <v-list-item>

                        <v-list-item-content>
                            <v-text-field v-model="model.name"
                                          label="Name" />
                        </v-list-item-content>

                        <v-list-item-action>
                        </v-list-item-action>
                    </v-list-item>
                    <v-list-item>

                        <v-list-item-content>
                            <v-text-field v-model="model.customSize"
                                          label="Format size"
                                          hint="width * height" />
                        </v-list-item-content>

                        <v-list-item-action>
                        </v-list-item-action>
                    </v-list-item>
                </v-list>
            </v-expansion-panel-content>
        </v-expansion-panel>

        <v-expansion-panel key="2">
            <v-expansion-panel-header>Elements order</v-expansion-panel-header>
            <v-expansion-panel-content>

                <v-list>
                    <v-list-item v-for="l in layout"
                                 :key="l.ix">
                        <v-list-item-action>

                            <v-icon @click="layoutUp(l.ix)">
                                mdi-arrow-up-drop-circle-outline
                            </v-icon>

                            <v-icon @click="layoutDown(l.ix)">
                                mdi-arrow-down-drop-circle-outline
                            </v-icon>
                        </v-list-item-action>
                        <v-list-item-title style="height:48px"
                                           @click="layoutSelect(l.ix)">
                            <scaleToParent>
                                <fieldRender :format="l.item" />
                            </scaleToParent>
                        </v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-expansion-panel-content>


        </v-expansion-panel>

        <v-expansion-panel key="3">

            <v-expansion-panel-header>
                Badges</v-expansion-panel-header>
            <v-expansion-panel-content>

                <v-list v-if="badgeContexts">
                    <v-list-item v-for="context in badgeContexts"
                                 :key="context.id">
                        <v-list-item-avatar>
                            <v-icon>mdi-{{context.menu_icon}}</v-icon>
                        </v-list-item-avatar>

                        <v-list-item-content v-if="model.badgeMap">
                            <v-list-item-title>

                                <v-select v-model="model.badgeMap[context.context_code]"
                                          placeholder="No permissions"
                                          :items="availableBadges(context.context_code)"
                                          item-text="name"
                                          item-value="id"
                                          chips
                                          multiple
                                          :label="context.name" />
                            </v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>
                </v-list>
            </v-expansion-panel-content>
        </v-expansion-panel>

    </v-expansion-panels>
</v-card>
</template>

<script>
import {
    mapGetters,
    mapState
} from 'vuex'
import scaleToParent from '@/components/formatpieces/scaleToParent.vue';
import fieldRender from '@/components/formatpieces/fieldRender.vue';
const minmax = (num, min, max) => Math.min(Math.max(num, min), max)
export default {
    components: {
        scaleToParent,
        fieldRender
    },
    props: {
        'value': {
            type: Object
        },
    },
    data() {
        return {
            dialog: false,
            model: {
                badgeMap: {},
                ...this.value
            },
            badgeMap: {},
            skipEmitOnce: false,
        };
    },
    methods: {
        styleToggleIsOn(styleName, onValue) {
            return this.model.style[styleName] == onValue
        },
        layoutUp(ix) {
            console.log('upping layout', ix)
            if (ix < this.model.layout.length - 1)
                this.model.layout.splice(ix, 2, this.model.layout[ix + 1], this.model.layout[ix]);
        },
        layoutDown(ix) {
            console.log('Downing layout', ix)
            if (ix > 0)
                this.model.layout.splice(ix - 1, 2, this.model.layout[ix], this.model.layout[ix - 1]);
        },
        layoutSelect(ix) {
            console.log('Selecting layout', ix)
            this.$emit('selectLayout', ix);
        },
        availableBadges(context_code) {
            if (this.badges == undefined) return [];
            let a = this.badges[context_code];
            if (a == undefined)
                return [];
            return a;
        },
    },
    watch: {
        model: {
            handler: function(newData) {
                if (this.skipEmitOnce == true) {
                    // console.log('zskip emit')
                    this.skipEmitOnce = false;
                    return;
                }
                // console.log('zemitting layout', newData);
                this.$emit('input', newData);
            },
            deep: true
        },
        value: {
            handler: function(newformat) {
                //console.log('zgot new layout', newformat);
                this.skipEmitOnce = true;
                this.model = {
                    ...newformat,
                };
            },
            deep: true
        },
    },
    computed: {
        layout() {
            return (this.model.layout || []).map((t, ix) => {
                return {
                    item: t,
                    ix: ix
                }
            }).reverse();
        },
        ...mapGetters('products', {
            'badgeContexts': 'badgeContexts',
        }),
        ...mapState('products', {
            'badges': (state) => state.badges,

        })
    },
    mounted() {
        //Ensure all context badgess are loaded
        this.badgeContexts.forEach((item, i) => {
            this.$store.dispatch('products/getContextBadges', item.context_code);
        });

    },

};
</script>

<style scoped>
</style>
