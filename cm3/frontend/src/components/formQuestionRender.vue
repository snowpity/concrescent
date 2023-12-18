<template>
<v-container>
    <template v-if="question.type == 'debug'">
        {{question}}
    </template>
    <template v-if="question.type == 'h1'">
        <h1 v-if="question.title != ''">{{question.title}}</h1>
        <v-md-preview v-if="question.text != null && question.text.length > 0"
                      :text="question.text" />
    </template>
    <template v-if="question.type == 'h2'">
        <h2 v-if="question.title != ''">{{question.title}}</h2>
        <v-md-preview v-if="question.text != null && question.text.length > 0"
                      :text="question.text" />
    </template>
    <template v-if="question.type == 'h3'">
        <h3 v-if="question.title != ''">{{question.title}}</h3>
        <v-md-preview v-if="question.text != null && question.text.length > 0"
                      :text="question.text" />
    </template>
    <template v-if="question.type == 'p'">
        <p v-if="question.title != ''">
            <strong>{{question.title}}</strong>
        </p>
        <v-md-preview v-if="question.text != null && question.text.length > 0"
                      :text="question.text" />
    </template>
    <template v-if="question.type == 'q'">
        <p v-if="question.title != ''">
            <strong>{{question.title}}</strong>
        </p>
        <blockquote class="blockquote">
            <v-md-preview v-if="question.text != null && question.text.length > 0"
                          :text="question.text" />
        </blockquote>
    </template>
    <template v-if="question.type == 'hr'">
        <v-row v-if="question.title != ''">
            <v-col>
                <v-divider role="presentation"></v-divider>
            </v-col>
            <div class="text-center">
                <strong>{{question.title}}</strong>
            </div>
            <v-col>
                <v-divider role="presentation"></v-divider>
            </v-col>
        </v-row>
        <v-divider v-else></v-divider>
    </template>
    <template v-if="question.type == 'text'">
        <v-text-field :hint="question.text"
                      :readonly="readonly"
                      v-model="userResponse"
                      :rules="question.required ? RulesRequired : undefined ">
            <template v-slot:label>
                {{question.title}}
                <span v-if="isRequired"
                      class="red--text"> *</span>
            </template>
        </v-text-field>
    </template>
    <template v-if="question.type == 'textarea'">
        <v-textarea :label="question.title"
                    :hint="question.text"
                    :readonly="readonly"
                    v-model="userResponse"
                    :rules="isRequired ? RulesRequired : undefined ">
            <template v-slot:label>
                {{question.title}}
                <span v-if="isRequired"
                      class="red--text"> *</span>
            </template>
        </v-textarea>
    </template>
    <template v-if="question.type == 'url'">
        <v-text-field :label="question.title"
                      :hint="question.text"
                      :readonly="readonly"
                      v-model="userResponse"
                      :rules="isRequired ? RulesURLRequired : RulesURL ">
            <template v-slot:label>
                {{question.title}}
                <span v-if="isRequired"
                      class="red--text"> *</span>
            </template>
        </v-text-field>
    </template>
    <template v-if="question.type == 'urllist'">
        <p v-if="question.title != ''">
            <strong>{{question.title}}
                <span v-if="isRequired"
                      class="red--text"> *</span></strong>
        </p>
        <v-md-preview v-if="question.text != null && question.text.length > 0"
                      :text="question.text" />
        <v-list outlined>

            <v-list-item v-for="(item,i) in multiSelectResponse"
                         :key="i">
                <v-list-item-content>
                    <v-text-field v-model="multiSelectResponse[i]"
                                  append-outer-icon="mdi-close"
                                  :readonly="readonly"
                                  :rules="isRequired ? RulesURLRequired : RulesURL "
                                  @click:append-outer="removeValue(i)"
                                  @keyup="listValueChanged" />
                </v-list-item-content>
            </v-list-item>
            <v-subheader>
                <v-spacer />
                <v-btn @click="addValue"
                       small>
                    <v-icon>mdi-plus</v-icon>
                </v-btn>
            </v-subheader>
        </v-list>

    </template>
    <template v-if="question.type == 'email'">
        <v-text-field :label="question.title"
                      :hint="question.text"
                      :readonly="readonly"
                      v-model="userResponse"
                      :rules="isRequired ? RulesEmailRequired : RulesEmail ">
            <template v-slot:label>
                {{question.title}}
                <span v-if="isRequired"
                      class="red--text"> *</span>
            </template>
        </v-text-field>
    </template>
    <template v-if="question.type == 'radio'">
        <p v-if="question.title != ''">
            <strong>{{question.title}}</strong>
            <span v-if="isRequired"
                  class="red--text"> *</span>
        </p>
        <v-radio-group :readonly="readonly"
                       v-model="userResponse"
                       :mandatory="false"
                       :rules="isRequired ? RulesRequired : undefined ">
            <template v-slot:label>
                <v-md-preview v-if="question.text != null && question.text.length > 0"
                              :text="question.text" />
            </template>
            <v-radio v-for="(item,idx) in listItems"
                     v-bind:label="item"
                     v-bind:value="item"
                     v-bind:key="idx"></v-radio>
        </v-radio-group>
    </template>
    <template v-if="question.type == 'checkbox'">
        <p v-if="question.title != ''">
            <strong>{{question.title}}</strong>
            <span v-if="isRequired"
                  class="red--text"> *</span>
        </p>
        <v-md-preview v-if="question.text != null && question.text.length > 0"
                      :text="question.text" />
        <v-checkbox hide-details
                    v-for="(item,idx) in listItems"
                    v-bind:label="item"
                    v-bind:value="item"
                    v-model="multiSelectResponse"
                    v-bind:key="idx"></v-checkbox>

        <v-input :readonly="readonly"
                 v-model="userResponse"
                 :rules="isRequired ? RulesRequired : undefined ">
            <template v-slot:default>
                <div></div>
            </template>
        </v-input>
    </template>
    <template v-if="question.type == 'select'">
        <v-select :label="question.title"
                  :hint="question.text"
                  :items="listItems"
                  :clearable="isRequired"
                  :readonly="readonly"
                  v-model="userResponse"
                  :rules="isRequired ? RulesRequired : undefined ">
            <template v-slot:label>
                {{question.title}}
                <span v-if="isRequired"
                      class="red--text"> *</span>
            </template>
        </v-select>
    </template>
</v-container>
</template>

<script>
import VInput from 'vuetify/lib/components/VInput/VInput.js';
export default {
    extends: VInput,
    props: ['question', 'value', 'readonly'],
    data: () => ({
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
            (v) => !v || /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-zA-Z0-9]+([-.]{1}[a-zA-Z0-9]+)*\.[a-zA-Z]{2,63}(:[0-9]{1,5})?(\/.*)?$/.test(v) || 'URL must be valid',
        ],
        RulesURLRequired: [
            (v) => !!v || 'URL is required',
            (v) => /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-zA-Z0-9]+([-.]{1}[a-zA-Z0-9]+)*\.[a-zA-Z]{2,63}(:[0-9]{1,5})?(\/.*)?$/.test(v) || 'URL must be valid',
        ],
    }),
    methods: {
        addValue() {
            var t = this.multiSelectResponse;
            t.push("");
            this.multiSelectResponse = t;
        },
        removeValue(ix) {

            var t = this.multiSelectResponse;
            t.splice(ix, 1);
            this.multiSelectResponse = t;
        },
        listValueChanged() {
            this.multiSelectResponse = this.multiSelectResponse;
        }
    },
    computed: {
        listItems() {
            return this.question.values; // .split("\n")
        },
        isRequired() {
            return (this.question.required > 0) || false;
        },
        userResponse: {
            get() {
                return this.value || '';
            },
            set(userResponse) {
                this.$emit('input', userResponse);
            },
        },
        multiSelectResponse: {
            get() {
                return (this.userResponse || '').split('\n') || [];
                // if (this.question.type == 'urllist')
                //     result.push("");
                return result;
            },
            set(multiSelectResponse) {
                if (multiSelectResponse.length > 0 && multiSelectResponse[0] == '')
                    multiSelectResponse.splice(0, 1);
                this.userResponse = multiSelectResponse ? multiSelectResponse.join('\n') : '';
            },
        },

    },

};
</script>
