<template>
<div>
  <p >
      <b>Description:</b> {{ description }}</p>
  <h4>Rewards:</h4>
    <Editor
      mode="viewer"
      :outline="false"
      v-model="rewardlist"
    />
  <!-- <ul>
      <li v-for="reward in rewardlist" :key="reward">
          {{reward}}
      </li>
  </ul> -->
</div>
</template>

<script>
import { Editor } from "vuetify-markdown-editor";
export default {
  components: {
    Editor,
  },
  props: ['description','rewardlist'],
  data: () => ({
    //userResponse: ""
  }),
};
</script>
