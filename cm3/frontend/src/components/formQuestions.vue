<template>
<v-form ref="fFormInfo"
        v-model="validFormInfo">
    <v-row v-for="question in questions"
           v-bind:key="question.id">
        <formQuestionRender v-bind:question="question"
                            :readonly="readonly"
                            v-model="interrimFormData[question.id.toString()]" />
    </v-row>
    <div v-if="questions.length == 0">
        {{no-data-text}}
    </div>
</v-form>
</template>

<script>
import formQuestionRender from '@/components/formQuestionRender.vue';
export default {
    components: {
        formQuestionRender,
    },
    props: ['value', 'questions', 'no-data-text', 'readonly'],
    data: () => ({
        validFormInfo: true,
        interrimFormData: {}
    }),
    watch: {
        interrimFormData(newData) {
            this.$emit('input', newData);
        },
        value(newValue) {
            //Splat the input into the form
            this.interrimFormData = newValue;
        }
    },
    created() {
        this.interrimFormData = this.value;
    }
};
</script>
