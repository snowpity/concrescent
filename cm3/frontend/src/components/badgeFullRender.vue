<template>
<div :style="sStyle">

    <fieldPositioner v-for="(item,ix) in model.layout"
                     :key="ix"
                     :format="item"
                     :value="badge"
                     :order="ix"
                     readOnly />

</div>
</template>

<script>
import fieldPositioner from '@/components/formatpieces/fieldPositioner.vue';
export default {
    components: {
        fieldPositioner
    },
    props: ['badge', 'format'],
    data: function() {
        let v = this.format;
        return {
            model: {
                name: v.name || 'New Badge Format',
                customSize: v.customSize || '5in*3in',
                bgImageID: v.bgImageID,
                layoutPosition: v.layoutPosition || null,
                layout: v.layout || []
            },
        }
    },
    computed: {
        sSizeArray() {
            //TODO: Retrieve default size somewhere else and inject it here?
            return (this.model.customSize || '').split('*');
        },
        sWidth() {
            if (this.sSizeArray.length > 0) {
                return this.sSizeArray[0];
            }
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
    watch: {

        format(newformat) {
            //Splat the input into the form
            // console.log('format received', newformat)
            this.model = {
                ...newformat,
            };
        }
    }
};
</script>

<style scoped>



</style>
