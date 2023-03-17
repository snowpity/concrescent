<template>
<v-toolbar elevation="0"
           color="blue">

    <template v-if="$vuetify.breakpoint.mdAndUp">
        <v-tooltip bottom>
            <template v-slot:activator="{ on, attrs }">
                <v-btn v-bind="attrs"
                       color="primary"
                       v-on="on"
                       @click="templateTextDialog = true">
                    <v-icon>mdi-text-recognition</v-icon>
                </v-btn>
            </template>
            <span>Edit template text</span>
        </v-tooltip>
        <v-combobox :items="dropdown_font"
                    v-model="model.style['font-family']"
                    label="Select font"
                    hide-details
                    outlined
                    dense
                    style="width:0px;">
        </v-combobox>
        <v-combobox :items="dropdown_size"
                    v-model="applySize"
                    label="Font size"
                    hide-details
                    outlined
                    dense
                    style="width:0px;">
        </v-combobox>

        <v-select :items="content_fit"
                  v-model="model.fit"
                  label="Content Scale"
                  hide-details
                  outlined
                  dense
                  style="width:0px;"
                  overflow></v-select>

        <v-btn-toggle multiple
                      v-model="applyStyles">
            <v-tooltip v-for="s in style_toggles"
                       :key="s.name"
                       bottom>
                <template v-slot:activator="{ on, attrs }">
                    <v-btn v-bind="attrs"
                           v-on="on">
                        <v-icon>mdi-{{s.icon}}</v-icon>
                    </v-btn>
                </template>
                <span>{{s.title}}</span>
            </v-tooltip>
        </v-btn-toggle>
        <v-divider vertical></v-divider>

        <v-btn-toggle v-model="applyAlign">
            <v-btn v-for="alignment in alignments"
                   :key="alignment">
                <v-icon>mdi-format-align-{{alignment}}</v-icon>
            </v-btn>
        </v-btn-toggle>
        <v-btn icon>
            <v-icon>mdi-format-color-fill</v-icon>
        </v-btn>
    </template>
    <template v-else>
        <!-- For smol screens -->
        <v-dialog v-model="dialog"
                  scrollable>
            <template v-slot:activator="{ on, attrs }">
                <v-btn color="primary"
                       dark
                       v-bind="attrs"
                       v-on="on">
                    <v-icon>mdi-pencil</v-icon>
                </v-btn>
            </template>
            <v-card>
                <v-card-title>Edit field</v-card-title>
                <v-divider></v-divider>
                <v-card-text>
                    Controls here
                    <fieldRender :value="value"
                                 :format="model"
                                 class="contained"
                                 ref="contained" />
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions>
                    <v-btn color="blue darken-1"
                           text
                           @click="dialog = false">
                        Close
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </template>

    <v-dialog v-model="templateTextDialog"
              scrollable>
        <v-card>
            <v-card-title>Edit field template</v-card-title>
            <v-divider></v-divider>
            <v-card-text>

                <template v-if="format.type == 'debug'">
                    <pre>{{format}}</pre>
                </template>
                <template v-else-if="format.type == 'simpletext'">
                    <v-text-field v-model="templateText"
                                  hide-details="true">
                    </v-text-field>
                </template>
                <template v-else-if="format.type == 'text'">
                    <v-md-editor v-model="templateText" />
                </template>
                <template v-else-if="format.type == 'image'">
                    Image picker goes here
                </template>
                <template v-else>
                    Unknown field type: {{format.type}}
                </template>


            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="default"
                       @click="revertTemplateText">Cancel</v-btn>
                <v-btn color="primary"
                       @click="saveTemplateText">Save</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</v-toolbar>
</template>

<script>
import interact from "interactjs";
import fieldRender from './fieldRender';
const minmax = (num, min, max) => Math.min(Math.max(num, min), max)
export default {
    components: {
        fieldRender
    },
    props: {
        'format': {
            type: Object
        },
        'value': {
            type: String
        },
    },
    data() {
        return {
            dialog: false,
            templateTextDialog: false,
            templateText: this.format.text,

            content_fit: [{
                    text: 'Fill',
                    value: 'fill'
                },
                {
                    text: 'Contain',
                    value: 'contain'
                },
                {
                    text: 'Cover',
                    value: 'cover'
                },
                {
                    text: 'Scale Down',
                    value: 'scale-down'
                },
                {
                    text: 'None',
                    value: 'none'
                },
            ],
            dropdown_font: [
                'Arial', 'Calibri', 'Courier', 'Verdana'
            ],
            dropdown_size: [
                '8px', '9px', '10px', '11px', '12px', '14px', '16px', '18px', '20px', '22px', '24px', '26px', '28px', '36px', '48px', '72px'
            ],
            alignments: ['left', 'center', 'right', 'justify'],
            style_toggles: [{
                title: 'Bold',
                icon: 'format-bold',
                on: 'bold',
                off: 'normal',
                name: 'font-weight'
            }, {
                title: 'Italic',
                icon: 'format-italic',
                on: 'italic',
                off: 'normal',
                name: 'font-style'
            }, {
                title: 'Underline',
                icon: 'format-underline',
                on: 'underline',
                off: undefined,
                name: 'text-decoration'
            }, ],
            model: {
                type: 'debug',
                text: "small-name",
                left: 0.4,
                top: 0.4,
                width: 0.2,
                height: 0.2,
                fit: 'contain', //used like img object-fit css style
                style: {},
                ...this.format
            },
            skipEmitOnce: false,
        };
    },
    methods: {
        styleToggleIsOn(styleName, onValue) {
            return this.model.style[styleName] == onValue
        },
        revertTemplateText() {
            this.templateText = this.format.text;
            this.templateTextDialog = false;
        },
        saveTemplateText() {
            this.model.text = this.templateText;
            this.templateTextDialog = false;
        }
    },
    watch: {
        model: {
            handler: function(newData) {
                if (this.skipEmitOnce == true) {
                    this.skipEmitOnce = false;
                    return;
                }
                //console.log('emitting format', newData);
                this.$emit('update:format', newData);
            },
            deep: true
        },
        format(newformat) {
            //console.log('got new positioner format', newformat);
            this.skipEmitOnce = true;
            this.model = {
                ...newformat,
            };
            this.templateText = this.format.text;
        },
    },
    computed: {
        parent() {
            if (this.domParentEl == undefined) {
                //This is undefined at fist go, give some bogus values
                return {
                    height: 2000,
                    width: 4000
                };
            }
            return this.domParentEl;
        },
        applySize: {
            get() {
                var a = this.format.style['font-size'];
                console.log('looking for size, which is', a)
                return a
            },
            set(newSize) {
                console.log('setting size to', newSize);
                this.model.style['font-size'] = newSize;
                this.model.style = {
                    ...this.model.style
                };
            }
        },
        applyAlign: {
            get() {
                var a = this.alignments.findIndex(a => a == this.format.style['text-align']);
                //console.log('looking for align, which is ix', a)
                return a
            },
            set(newAlign) {
                //console.log('setting aign to', newAlign);
                this.model.style['text-align'] = this.alignments[newAlign];
                this.model.style = {
                    ...this.model.style
                };
            }
        },
        applyStyles: {
            get() {
                var a = this.style_toggles.map((t, ix) => this.format.style[t.name] == t.on ? ix : false).filter(x => x !== false);
                //console.log('looking for align, which is ix', a)
                return a
            },
            set(newStyles) {
                //console.log('setting aign to', newAlign);
                this.style_toggles.forEach((style, i) => {
                    this.model.style[style.name] =
                        newStyles.includes(i) ? style.on : style.off;
                });
                this.model.style = {
                    ...this.model.style
                };
            }
        },
    },
    mounted() {

    },

};
</script>

<style scoped>
</style>
