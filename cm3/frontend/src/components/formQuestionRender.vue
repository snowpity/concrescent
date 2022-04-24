<template>
<v-container>
    <template v-if="question.type == 'debug'">
        {{question}}
    </template>
    <template v-if="question.type == 'h1'">
        <h1 v-if="question.title != ''">{{question.title}}</h1>
        <p v-if="question.text != ''">{{question.text}}</p>
    </template>
    <template v-if="question.type == 'h2'">
        <h2 v-if="question.title != ''">{{question.title}}</h2>
        <p v-if="question.text != ''">{{question.text}}</p>
    </template>
    <template v-if="question.type == 'h3'">
        <h3 v-if="question.title != ''">{{question.title}}</h3>
        <p v-if="question.text != ''">{{question.text}}</p>
    </template>
    <template v-if="question.type == 'p'">
        <p v-if="question.title != ''">
            <strong>{{question.title}}</strong>
        </p>
        <p v-if="question.text != ''">{{question.text}}</p>
    </template>
    <template v-if="question.type == 'q'">
        <blockquote class="blockquote">
            <p v-if="question.title != ''">
                <strong>{{question.title}}</strong>
            </p>
            <p v-if="question.text != ''">{{question.text}}</p>
        </blockquote>
    </template>
    <template v-if="question.type == 'hr'">
        <v-divider></v-divider>
    </template>
    <template v-if="question.type == 'text'">
        <v-text-field :label="question.title"
                      :hint="question.text"
                      :readonly="readonly"
                      v-model="userResponse"
                      :rules="question.isRequired ? RulesRequired : undefined "></v-text-field>
    </template>
    <template v-if="question.type == 'textarea'">
        <v-textarea :label="question.title"
                    :hint="question.text"
                    :readonly="readonly"
                    v-model="userResponse"
                    :rules="question.isRequired ? RulesRequired : undefined "></v-textarea>
    </template>
    <template v-if="question.type == 'url'">
        <v-text-field :label="question.title"
                      :hint="question.text"
                      :readonly="readonly"
                      v-model="userResponse"
                      :rules="question.isRequired ? RulesURLRequired : RulesURL "></v-text-field>
    </template>
    <template v-if="question.type == 'email'">
        <v-text-field :label="question.title"
                      :hint="question.text"
                      :readonly="readonly"
                      v-model="userResponse"
                      :rules="question.isRequired ? RulesEmailRequired : RulesEmail "></v-text-field>
    </template>
    <template v-if="question.type == 'radio'">
        <p v-if="question.title != ''">
            <strong>{{question.title}}</strong>
        </p>
        <v-radio-group :readonly="readonly"
                       v-model="userResponse"
                       :mandatory="false">
            <template v-slot:label>
                <p v-if="question.text != ''">{{question.text}}</p>
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
        </p>
        <p v-if="question.text != ''">{{question.text}}</p>
        <v-checkbox hide-details
                    v-for="(item,idx) in listItems"
                    v-bind:label="item"
                    v-bind:value="item"
                    v-model="multiSelectResponse"
                    v-bind:key="idx"></v-checkbox>

        <v-input :readonly="readonly"
                 v-model="userResponse"
                 :rules="question.isRequired ? RulesRequired : undefined ">
            <template v-slot:default>
                <div></div>
            </template>
        </v-input>
    </template>
    <template v-if="question.type == 'select'">
        <v-select :label="question.title"
                  :hint="question.text"
                  :items="listItems"
                  :clearable="question.isRequired"
                  :readonly="readonly"
                  v-model="userResponse"></v-select>
    </template>
</v-container>
</template>

<script>
export default {
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
            (v) => !v || /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([-.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/.test(v) || 'URL must be valid',
        ],
        RulesURLRequired: [
            (v) => !!v || 'URL is required',
            (v) => /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([-.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/.test(v) || 'URL must be valid',
        ],
    }),
    computed: {
        listItems() {
            return this.question.values; // .split("\n")
        },
        userResponse: {
            get() {
                return this.value;
            },
            set(userResponse) {
                this.$emit('input', userResponse);
            },
        },
        multiSelectResponse: {
            get() {
                return (this.userResponse || '').split('\n').filter(Boolean) || [];
            },
            set(multiSelectResponse) {
                this.userResponse = multiSelectResponse ? multiSelectResponse.join('\n') : '';
            },
        },

    },

};
</script>
