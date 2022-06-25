<template>
<v-container>
    <v-sheet v-if="!preview">
        <v-row>
            <v-col cols="12">
                <v-select :items="questionTypes"
                          v-model="result.type"
                          label="Type"
                          hide-details="true"></v-select>
            </v-col>
        </v-row>
        <v-row v-if="!questionSettings.hideTitle">
            <v-col cols="12">
                <v-text-field v-model="result.title"
                              hide-details="true"
                              label="Title">
                </v-text-field>
            </v-col>
        </v-row>
        <v-row v-if="!questionSettings.hideText">
            <v-col cols="12">
                <v-textarea v-model="result.text"
                            hide-details="true"
                            rows="1"
                            auto-grow
                            label="Text">
                </v-textarea>
            </v-col>
        </v-row>
        <v-row v-if="!questionSettings.hideValue">
            <v-col cols="12">
                <v-list outlined>
                    <v-subheader>Available options
                        <v-spacer />
                        <v-btn @click="addValue"
                               small>
                            <v-icon>mdi-plus</v-icon>
                        </v-btn>
                    </v-subheader>
                    <v-list-item v-for="(item,i) in result.values"
                                 :key="i">
                        <v-list-item-content>

                            <v-text-field v-model="result.values[i]"
                                          hide-details="true"
                                          append-outer-icon="mdi-close"
                                          @click:append-outer="removeValue(i)" />
                        </v-list-item-content>
                    </v-list-item>
                </v-list>
            </v-col>
        </v-row>
    </v-sheet>
    <v-sheet v-else>
        <formQuestionRender :question="result"
                            v-model="previewValue" />
    </v-sheet>
</v-container>
</template>

<script>
import formQuestionRender from '@/components/formQuestionRender.vue';
export default {
    components: {
        formQuestionRender
    },
    props: ['question', 'value', 'readonly', 'preview'],
    data: () => ({
        previewValue: '',
        result: {
            type: 'h1',
            title: '',
            text: '',
            values: [],
            listed: false,
        },
        questionTypes: [{
            value: 'h1',
            text: "Title",
            hideValue: true
        }, {
            value: 'h2',
            text: "Medium Title",
            hideValue: true
        }, {
            value: 'h3',
            text: "Small Title",
            hideValue: true
        }, {
            value: 'p',
            text: "Text block",
            hideValue: true
        }, {
            value: 'q',
            text: "Indented Text",
            hideValue: true
        }, {
            value: 'hr',
            text: "Horizontal Separator",
            hideText: true,
            hideValue: true
        }, {
            value: 'text',
            text: "Short answer",
            hideValue: true
        }, {
            value: 'textarea',
            text: "Long answer",
            hideValue: true
        }, {
            value: 'url',
            text: "Web link",
            hideValue: true
        }, {
            value: 'urllist',
            text: "List of web links",
            hideValue: true
        }, {
            value: 'email',
            text: "Email address",
            hideValue: true
        }, {
            value: 'radio',
            text: "Multiple choice (Single select)"
        }, {
            value: 'checkbox',
            text: "Multiple choice (Multiple select)"
        }, {
            value: 'select',
            text: "Single select (dropdown)"
        }, {
            value: 'file',
            text: "File upload (Not yet functional)"
        }],
        // userResponse: ""
        RulesRequired: [
            (v) => !!v || 'Answer is required',
        ],
        RulesEmail: [
            (v) => !v || /.+@.+\..+/.test(v) || 'E-mail must be valid',
        ],
        RulesEmailRequired: [
            (v) => !!v || 'E-mail is required',
            (v) => /.+@.+\..+/.test(v) || 'E-mail must be valid',
        ],
        RulesURL: [
            (v) => !v || /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([-.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/.test(v) || 'URL must be valid',
        ],
        RulesURLRequired: [
            (v) => !!v || 'URL is required',
            (v) => /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([-.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/.test(v) || 'URL must be valid',
        ],
    }),
    computed: {
        questionSettings() {
            var settings = this.questionTypes.find(t => t.value == this.result.type) || {};
            settings.hideTitle = settings.hideTitle || false;
            settings.hideText = settings.hideText || false;
            settings.hideValue = settings.hideValue || false;
            return settings;
        },

    },
    methods: {
        addValue() {
            this.result.values.push("");
        },
        removeValue(ix) {
            this.result.values.splice(ix, 1);
        }
    },
    watch: {
        result(newData) {
            this.$emit('input', newData);
        },
        value(newValue) {
            //Splat the input into the form
            this.result.type = newValue.type;
            this.result.title = newValue.title;
            this.result.text = newValue.text;
            this.result.values = newValue.values;
            this.result.listed = newValue.listed;
        }
    },
    created() {
        this.result = this.value;
    }

};
</script>
