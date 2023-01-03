<template>
<div>
    <p>
        <b v-if="description != null && description.length > 0">Description:</b>
        <v-md-preview v-if="description != null && description.length > 0"
                      :text="description" />
    </p>
    <h4 v-if="rewardlist != null && rewardlist.length > 0">Rewards:</h4>
    <v-md-preview v-if="rewardlist != null && rewardlist.length > 0"
                  :text="rewardlist" />
</div>
</template>

<script>
export default {
    components: {},
    props: ['description', 'rewardlist'],
    data: () => ({}),
};
</script>
