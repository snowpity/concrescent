<template>
<v-container fluid
             class="pa-0">
    <v-toolbar :color="'blue'">
        <v-app-bar-nav-icon></v-app-bar-nav-icon>
        <v-toolbar-title>{{model.name}}</v-toolbar-title>
        <v-spacer></v-spacer>

        <template v-if="!fieldIsSelected">
            <v-btn color="primary"
                   @click="preview = !preview">
                <v-icon>mdi-file-find</v-icon>
            </v-btn>
            <v-menu offset-y>
                <template v-slot:activator="{ on, attrs }">
                    <v-btn color="primary"
                           v-bind="attrs"
                           v-on="on">
                        <v-icon>mdi-plus</v-icon>
                    </v-btn>
                </template>
                <v-list>
                    <v-list-item v-for="(fieldType, index) in fieldTypes"
                                 :key="index"
                                 @click="addField(fieldType.name)">
                        <v-list-item-title>{{ fieldType.title }}</v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>
        </template>
        <template v-else>
            <fieldEditToolbar :format.sync="model.layout[fieldSelectedIx]" />

            <v-btn color="primary"
                   @click="delField(fieldSelectedIx)">
                <v-icon>mdi-delete</v-icon>
            </v-btn>
        </template>
    </v-toolbar>
    <v-sheet color="white"
             class="mx-auto mt-3"
             elevation="4"
             :style="sStyle">
        <fieldPositioner v-for="(item,ix) in model.layout"
                         :key="ix"
                         :format.sync="model.layout[ix]"
                         :value="badge"
                         :readOnly="preview"
                         :edit="ix == fieldSelectedIx"
                         :order="ix"
                         @click="toggleSelected(ix)"
                         @move="selectField(ix)" />
    </v-sheet>
</v-container>
</template>

<script >
import Vue from "vue";
import interact from "interactjs";
import fieldPositioner from '@/components/formatpieces/fieldPositioner.vue';
import fieldEditToolbar from '@/components/formatpieces/fieldEditToolbar.vue';
export default Vue.extend({
    components: {
        fieldPositioner,
        fieldEditToolbar
    },
    props: ['value'],
    data: function() {
        var v = this.value || {};
        return {
            preview: false,
            model: {
                name: v.name || 'New Badge Format',
                customSize: v.customSize || '5in*3in',
                bgImageID: v.bgImageID,
                layoutPosition: null,
                layout: v.layout || []
            },
            fieldTypes: [{
                    name: 'debug',
                    title: 'Debug field'
                },
                {
                    name: 'simpletext',
                    title: 'Template Text'
                },
                {
                    name: 'text',
                    title: 'Markdown text'
                },
                {
                    name: 'image',
                    title: 'Image'
                },
                {
                    name: 'unknown',
                    title: 'Unknown'
                },
            ],
            fieldSelected: null,
            fieldSelectedIx: -1,
            fieldSelectedFromMove: false,
            badgeData: {
                "id": 4,
                "uuid": "2057c94b-a295-11ec-9a18-0025904e67c1",
                "display_id": 3,
                "contact_id": 1,
                "real_name": "John Hancock",
                "fandom_name": "[[Signature]]",
                "name_on_badge": "Fandom Name Large, Real Name Small",
                "date_of_birth": "1935-07-07",
                "notify_email": null,
                "time_printed": null,
                "time_checked_in": null,
                "ice_name": null,
                "ice_relationship": null,
                "ice_email_address": null,
                "ice_phone_number": null,
                "context_code": "A",
                "application_status": "",
                "badge_type_id": 4,
                "payment_status": "Completed",
                "payment_promo_price": "0.00",
                "payment_badge_price": "75.00",
                "badge_type_name": "Ponyville Pony (Adult Weekend)",
                "badge_type_payable_onsite": 0,
                "notes": null,
                "payment_id": 36,
                "addons": [],
                "only_name": "",
                "large_name": "[[Signature]]",
                "small_name": "John Hancock",
                "display_name": "[[Signature]] (John Hancock)",
                "badge_id_display": "A3",
                "qr_data": "CM*A3*2057c94b-a295-11ec-9a18-0025904e67c1",
                "retrieve_url": "https://tsaukpaetra.com/concrescent/cm3/frontend/dist/#/myBadges?context_code=A&id=4&uuid=2057c94b-a295-11ec-9a18-0025904e67c1",
                "cart_url": "https://tsaukpaetra.com/concrescent/cm3/frontend/dist/#/cart?id=36",
                "form_responses": []
            }
        };
    },
    computed: {
        sSizeArray() {
            //TODO: Retrieve default size somewhere else and inject it here?
            return (this.model.customSize || '5in*3in').split('*');
        },
        sWidth() {
            if (this.sSizeArray.length > 0)
                return this.sSizeArray[0];
            return '5in';
        },
        sHeight() {
            if (this.sSizeArray.length > 1)
                return this.sSizeArray[1];
            return '3in';
        },
        sStyle() {
            var v = {
                height: this.sHeight,
                width: this.sWidth,
                position: 'relative'
            };
            return v;
        },
        fieldIsSelected() {
            return this.fieldSelectedIx > -1;
        },
        badge() {
            if (this.preview) {

                console.log('previewing, badge data', this.badgeData)
                return this.badgeData;
            } else {
                console.log('not previewing');
                return null;
            }
        }
    },
    methods: {
        addField(fieldType) {
            console.log('Adding new field', fieldType)
            this.model.layout.push({
                type: fieldType,
                text: 'New Item'
            })
        },
        delField(ix) {
            console.log('Deleting field', ix)
            this.model.layout.splice(ix, 1);
            this.fieldSelectedIx = -1;
        },
        toggleSelected(ix) {
            if (this.fieldSelectedIx == ix && !this.fieldSelectedFromMove) {
                console.log('deselecting field', ix)
                this.fieldSelectedIx = -1;
                return;
            }
            this.fieldSelectedFromMove = false;
            console.log('selecting field', ix)
            this.fieldSelectedIx = ix;
        },
        selectField(ix) {
            this.fieldSelectedFromMove = true;
            if (this.fieldSelectedIx == ix)
                return;
            console.log('selecting field', ix)
            this.fieldSelectedIx = ix;
        }
    },
    watch: {
        model(newData) {
            if (this.skipEmitOnce == true) {
                this.skipEmitOnce = false;
                return;
            }
            this.$emit('input', model);
        },
        value(newValue) {
            //Splat the input into the form
            this.skipEmitOnce = true;
            this.fieldSelectedIx = -1,
                this.model = {
                    ...newValue,
                };
        }
    },
});
</script>
