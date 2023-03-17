<template>
<div :style="sStyle">

    <fieldPositioner v-for="(item,ix) in model.layout"
                     :key="ix"
                     :format="format.layout[ix]"
                     :value="badge"
                     :edit="ix == fieldSelectedIx"
                     :order="ix"
                     readOnly />

</div>
</template>

<script>
export default {
    components: {},
    props: ['badge', 'format'],
    data: () => ({}),
    computed: {
        sSizeArray() {
            //TODO: Retrieve default size somewhere else and inject it here?
            return (this.format.customSize || '').split('*');
        },
        sWidth() {
            if (this.sSizeArray.length > 0)
                return this.sSizeArray[0];
            return '5in';
        },
        sHeight() {
            if (this.sSizeArray.length > 1)
                return this.sSizeArray[1];
            return '3in';
        },
        sStyle() {
            var v = {
                height: this.sHeight,
                width: this.sWidth,
                position: 'relative'
            };
            return v;
        },
    },
};
</script>
