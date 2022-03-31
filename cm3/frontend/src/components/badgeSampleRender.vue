<template>
<div>
    <v-row justify="center">
        <v-card-title class="text-h4">{{badge | badgeDisplayName}}</v-card-title>
    </v-row>
    <v-row justify="center"
           class="mt-n6">
        <v-card-subtitle class="text-h5">{{badge | badgeDisplayName(true)}}&zwj;</v-card-subtitle>
    </v-row>
</div>
</template>

<script>
export default {
    components: {},
    props: ['badge'],
    data: () => ({}),
};
</script>
