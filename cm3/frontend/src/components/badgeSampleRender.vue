<template>
<div>
    <v-row>
        <v-spacer></v-spacer>
        <v-card-title>{{badge | badgeDisplayName}}</v-card-title>
        <v-spacer></v-spacer>
    </v-row>
    <v-row>
        <v-spacer></v-spacer>
        <h3>{{badge | badgeDisplayName(true)}}&zwj;</h3>
        <v-spacer></v-spacer>
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
