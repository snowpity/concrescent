<template>
    <v-item-group>
        <v-item v-for="(item) in questions" :key="item.id">
            <v-card>
                <formQuestionRender :question="item" :value="responses[item.id]" readonly />
            </v-card>
        </v-item>
    </v-item-group>
</template>

<script>
import formQuestionRender from '@/components/formQuestionRender.vue';
export default {
    components: {
        formQuestionRender
    },
    props: ['questions', 'responses'],
    data: () => ({
    }),
};
</script>
