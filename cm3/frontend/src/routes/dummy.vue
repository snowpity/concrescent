<template>
<router-view :subTabIx="subTabIx" />
</template>
<script>
export default {
    props: ['subTabIx']
}
</script>
