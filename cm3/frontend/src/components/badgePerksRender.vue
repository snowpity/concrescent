<template>
<div>
  <p >
      <b>Description:</b> {{ description }}</p>
  <h4>Rewards:</h4>
  <ul>
      <li v-for="reward in rewardlist" :key="reward">
          {{reward}}
      </li>
  </ul>
</div>
</template>

<script>
export default {
  props: ['description','rewardlist'],
  data: () => ({
    //userResponse: ""
  }),
};
</script>
