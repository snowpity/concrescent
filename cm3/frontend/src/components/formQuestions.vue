<template>
<v-form ref="fFormInfo"
        v-model="validFormInfo">
    <v-row v-for="question in questions"
           v-bind:key="question.id">
        <formQuestionRender v-bind:question="question"
                            :readonly="readonly"
                            v-model="interrimFormData[question.id.toString()]" />
    </v-row>
    <div v-if="!hasQuestions">
        {{noDataText}}
    </div>
</v-form>
</template>

<script>
import VInput from 'vuetify/lib/components/VInput/VInput.js';
import formQuestionRender from '@/components/formQuestionRender.vue';
export default {
    extends: VInput,
    components: {
        formQuestionRender,
    },
    props: ['value', 'questions', 'no-data-text', 'readonly'],
    data: () => ({
        validFormInfo: true,
        interrimFormData: {}
    }),
    computed: {
        hasQuestions() {
            return this.questions && this.questions.length > 0;

        }
    },
    watch: {
        interrimFormData(newData) {
            this.$emit('input', newData);
        },
        value(newValue) {
            //Splat the input into the form
            this.interrimFormData = newValue;
        },
        validFormInfo(isValid) {
            this.$emit('valid', isValid);

        }
    },
    created() {
        this.interrimFormData = this.value;
    },
    mounted() {

        //Check if we have any data
        var hasProperties = false;
        for (var x in this.interrimFormData) {
            if (this.interrimFormData.hasOwnProperty(x)) {
                hasProperties = true;
                break;
            }
        }
        if (hasProperties) {
            console.log('Loaded form data, validating')
            this.$refs.fFormInfo.validate();
        }
        if (!this.hasQuestions) {
            //If there are no questions, then the form is always valid
            this.$emit('valid', true);
        }
        console.log('hasQuestions', this.hasQuestions)
    }
};
</script>
