<template>
<router-view :subTabIx="subTabIx"
             @updateSubTabs="$emit('updateSubTabs', $event)"
             @updateSubTitle="$emit('updateSubTitle', $event)" />
</template>
<script>
export default {
    props: ['subTabIx']
}
</script>
