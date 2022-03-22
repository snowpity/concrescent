<template>
<div>
  <p >
      <b v-if="description != null && description.length > 0">Description:</b>
      <markdown-renderer v-if="description != null && description.length > 0"
        :value="description"
      />
  </p>
  <h4  v-if="rewardlist != null && rewardlist.length > 0">Rewards:</h4>
    <markdown-renderer v-if="rewardlist != null && rewardlist.length > 0"
      :value="rewardlist"
    />
</div>
</template>

<script>
export default {
  components: {
  },
  props: ['description', 'rewardlist'],
  data: () => ({
  }),
};
</script>
