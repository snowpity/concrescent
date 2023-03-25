<template>
<div :style="containedStyle">
    <slot />
</div>
</template>

<script>
import {
    debounce
} from '@/plugins/debounce';
const minmax = (num, min, max) => Math.min(Math.max(num, min), max)
export default {
    components: {},
    props: {
        'zoom': {
            type: Number,
            default: 1
        },
        //used like img object-fit css style
        'fit': {
            type: String,
            default: 'scale-down'
        },
    },
    data() {

        return {
            domParentEl: null,
            domParentResizeWatcher: null,
            isUpdating: false,
            containedStyle: {
                width: 'initial',
                height: 'unset',
                transform: `scale(1, 1)`,
            },

        };
    },
    methods: {
        updatePx() {
            //TODO: Make sure it's in-bounds?
            //console.log('pos_px is now', this.pos_px);
            this.$nextTick(() => {
                this.updateRenderScale();
            });

        },
        async updateRenderScale() {
            if (this.isUpdating) return;
            //Adapted from https://stackoverflow.com/a/61543105
            // console.log('updateRenderScale');
            if (this.$el == undefined) return;
            let scaledContent = this.$el;
            if (scaledContent == undefined) return '?';
            this.isUpdating = true;
            // Get the scaled content, and reset its scaling for an instant
            this.containedStyle = {
                width: 'unset',
                height: 'unset',
                overflow: 'visible',
                transform: `scale(1,1)`,
            }
            await this.$nextTick();

            let {
                width: cw,
                height: ch
            } = scaledContent.getBoundingClientRect();
            let {
                width: pw,
                height: ph
            } = this.$el.parentElement.getBoundingClientRect();
            // Get the how big the parent would be if this didn't exist
            this.containedStyle = {
                display: 'none',
            }
            await this.$nextTick();

            let {
                width: dw,
                height: dh
            } = this.$el.parentElement.getBoundingClientRect();
            // console.log('parent would be', {
            //     dw,
            //     dh
            // })


            //Initial new scale representing "fill"
            let scaleAmtX = dw / cw;
            let scaleAmtY = dh / ch;
            let scaleSideSmaller = scaleAmtX < scaleAmtY;
            // console.log('fitting', {
            //     fit: this.fit,
            //     scaleAmtX,
            //     scaleAmtY
            // })
            switch (this.fit) {
                case 'contain':
                    scaleAmtX = scaleAmtY = Math.min(scaleAmtX, scaleAmtY);
                    break;
                case 'cover':
                    scaleAmtX = scaleAmtY = Math.max(scaleAmtX, scaleAmtY);
                    break;
                case 'scale-down':
                    scaleAmtX = scaleAmtY = Math.min(scaleAmtX, scaleAmtY, 1.0);
                    break;
                case 'none':
                    scaleAmtX = scaleAmtY = 1.0;
                    break;
            }
            //Apply zoom scale
            scaleAmtX *= this.zoom;
            scaleAmtY *= this.zoom;
            this.containedStyle = {
                'margin-right': (dw - pw) + 'px',
                'margin-bottom': (dh - ph) + 'px',
                // height: (this.$el.parentElement.clientHeight * scaleAmtY) + 'px',
                // top: (this.pos_px.h * -scaleAmtX) + 'px',
                transform: `scale(${scaleAmtX}, ${scaleAmtY})`,
                position: 'relative',
                'transform-origin': '0 0',
            }
            // console.log("c", JSON.parse(JSON.stringify(this.containedStyle)));
            this.isUpdating = false;
        },
        parentResized() {
            if (this.$el.parentElement == null) return;
            if (this.$el.parentElement.clientWidth * this.$el.parentElement.clientHeight < 1) return;
            // console.log('positioner detected parent resized, rerender', this.$el.parentElement.clientWidth)
            this.updateRenderScale();

        },
    },
    watch: {
        zoom: function() {
            this.updateRenderScale();
        }
    },
    computed: {},
    mounted() {
        //console.log('field positioner mounted')
        this.domParentEl = this.$el.parentElement;
        this.domParentResizeWatcher = new ResizeObserver(() => this.parentResized());
        this.domParentResizeWatcher.observe(this.$el.parentElement);
        if (this.$slots.default != undefined) {

            if (this.$slots.default.length > 0)
                this.domParentResizeWatcher.observe(this.$slots.default[0].elm);
        }
        // setTimeout(() => {
        //     console.log('mounted update')
        //     this.updateRenderScale()
        // }, 500); //Animation messes with element calculations. :(
    },
    unmounted: function() {
        // console.log('I am unmounted')
        this.domParentResizeWatcher.unobserve(this.$el.parentElement);
    },

};
</script>

<style scoped>
</style>
