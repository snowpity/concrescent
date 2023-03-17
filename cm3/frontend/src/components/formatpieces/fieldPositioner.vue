<template>
<div :style="style"
     class="locked"
     v-if="readOnly">
    <fieldRender :value="value"
                 :format="model"
                 class="contained"
                 :style="containedStyle"
                 ref="contained" />
</div>
<interact v-else
          :draggable="!locked"
          :dragOption="dragOption"
          :resizable="!locked"
          :resizeOption="resizeOption"
          class="draggable"
          :style="style"
          @click.native="clicked"
          @dragmove="dragmove"
          @resizemove="resizemove"
          @resizeend="updatePx">
    <fieldRender :value="value"
                 :format="model"
                 class="contained"
                 :style="containedStyle"
                 ref="contained" />
</interact>
</template>

<script>
import interact from "interactjs";
import fieldRender from './fieldRender';
const minmax = (num, min, max) => Math.min(Math.max(num, min), max)
export default {
    components: {
        fieldRender
    },
    props: {
        'format': {
            type: Object
        },
        'value': {
            type: Object,
            default: null
        },
        'edit': {
            type: Boolean
        },
        'readOnly': {
            type: Boolean
        },
        'locked': {
            type: Boolean
        },
        'order': {
            type: Number
        },
    },
    data() {
        const restrict =
            interact.modifiers.restrictRect({
                restriction: "parent",
                endOnly: false
            });

        return {
            domParentEl: null,
            pos_px: {
                x: 10,
                y: 10,
                w: 10,
                h: 10,
            },
            containedStyle: {
                width: 'initial',
                height: 'unset',
                transform: `scale(1, 1)`,
            },
            model: {
                type: 'debug',
                text: "small-name",
                left: 0.4,
                top: 0.4,
                width: 0.2,
                height: 0.2,
                fit: 'contain', //used like img object-fit css style
                style: {},
                ...this.format
            },

            resizeOption: {
                edges: {
                    left: true,
                    right: true,
                    bottom: true,
                    top: true
                },
                //The restrictions don't work right because we're doing restriction internally already?
                //modifiers: [restrict],
            },
            dragOption: {
                // enable inertial throwing
                inertia: false,
                // keep the element within the area of it's parent
                modifiers: [restrict],
                // enable autoScroll
                autoScroll: true
            },

            skipEmitOnce: false,
        };
    },
    methods: {
        clicked(event) {
            this.$emit('click');
        },
        dragmove(event) {
            if (this.locked) return;
            this.pos_px.x += event.dx;
            this.pos_px.y += event.dy;
            this.$emit('move');
        },
        resizemove(event) {
            if (this.locked) return;
            this.pos_px.w = event.rect.width;
            this.pos_px.h = event.rect.height;
            if (event.deltaRect) {
                this.pos_px.x += event.deltaRect.left;
                this.pos_px.y += event.deltaRect.top;
            }
            this.$emit('move');
        },
        toPx(percent, isHeight, defaultIfNull) {
            if (percent == undefined || percent == null) {
                //console.log('toPx had invalid percent of', percent);
                percent = defaultIfNull;
            }
            var c = isHeight ? this.parent.clientHeight : this.parent.clientWidth;
            return c * minmax(percent, 0, 1);
        },
        toPct(px, isHeight, defaultIfNull) {

            var c = isHeight ? this.parent.clientHeight : this.parent.clientWidth;
            if (c == 0) c = 1;
            if (px == undefined || px == null) {
                //console.log('toPct had invalid pixel of', px);
                px = Math.min(c, defaultIfNull);
            }
            return minmax(px, 0, c) / c;
        },
        updatePx() {
            this.pos_px = {
                x: this.toPx(this.model.left, false, 0.20),
                y: this.toPx(this.model.top, true, 0.10),
                w: this.toPx(this.model.width, false, 0.80),
                h: this.toPx(this.model.height, true, 0.20),
            };
            //TODO: Make sure it's in-bounds?
            //console.log('pos_px is now', this.pos_px);
            this.$nextTick(() => {
                this.updateRenderScale();
            });

        },
        savePct() {
            this.model.left = this.toPct(this.pos_px.x, false, 10);
            this.model.top = this.toPct(this.pos_px.y, true, 10);
            this.model.width = this.toPct(this.pos_px.w, false, 10);
            this.model.height = this.toPct(this.pos_px.h, true, 10);
        },
        async updateRenderScale() {
            //Adapted from https://stackoverflow.com/a/61543105
            console.log('updateRenderScale');
            if (this.$refs.contained == undefined) return;
            let scaledContent = this.$refs['contained'].$el;
            if (scaledContent == undefined) return '?';
            // Get the scaled content, and reset its scaling for an instant
            this.containedStyle = {
                width: 'unset',
                height: 'unset',
                transform: `scale(1,1)`,
            }
            await this.$nextTick();

            let {
                width: cw,
                height: ch
            } = scaledContent.getBoundingClientRect();
            let {
                width: ww,
                height: wh
            } = this.style;

            //Initial new scale representing "fill"
            let scaleAmtX = this.pos_px.w / cw;
            let scaleAmtY = this.pos_px.h / ch;

            switch (this.model.fit) {
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
            this.containedStyle = {
                width: (100 / scaleAmtX) + '%',
                height: (100 / scaleAmtY) + '%',
                transform: `scale(${scaleAmtX}, ${scaleAmtY})`,
            }
        }
    },
    watch: {
        'pos_px': {
            handler: function() {
                //Don't save if we're with unreasonable size
                if (this.pos_px.w == 0 && this.pos_px.h == 0) {
                    return;
                }
                this.savePct();
            },
            deep: true
        },
        model: {
            handler: function(newData) {
                if (this.skipEmitOnce == true) {
                    this.skipEmitOnce = false;
                    return;
                }
                //console.log('emitting format', newData);
                this.$emit('update:format', newData);
            },
            deep: true
        },
        format(newformat) {
            //Splat the input into the form
            //console.log('got new positioner format', newformat);
            this.skipEmitOnce = true;
            this.model = {
                ...newformat,
            }
            this.updatePx();
        },
        value() {
            this.$nextTick(() => {
                this.updateRenderScale();
            });
        }
    },
    computed: {
        parent() {
            if (this.domParentEl == undefined) {
                //This is undefined at fist go, give some bogus values
                return {
                    height: 2000,
                    width: 4000
                };
            }
            return this.domParentEl;
        },
        style() {
            var bordercolor = '#29e';
            if (this.edit) {
                bordercolor = 'indianred';
            } else if (this.locked) {
                bordercolor = 'darkslategrey';
            }
            var zindex = 100 + this.order;
            if (!!this.edit) zindex = 1000;
            return {
                //...this.model.style,
                height: `${this.pos_px.h}px`,
                width: `${this.pos_px.w}px`,
                transform: `translate(${this.pos_px.x}px, ${this.pos_px.y}px)`,
                overflow: 'hidden',
                'border-color': bordercolor,
                'z-index': zindex
            };
        },
    },
    mounted() {
        this.domParentEl = this.$el.parentElement;
        setTimeout(() => {
            this.updatePx()
        }, 500); //Animation messes with element calculations. :(
    },

};
</script>

<style scoped>
.draggable {
    position: absolute;
    border-radius: 2px;
    margin: -2px;
    border-style: inset;
    touch-action: none;
    user-select: none;
    box-sizing: border-box;
    transform: translate(0px, 0px);
}

.locked {
    position: absolute;
    touch-action: none;
    user-select: none;
    box-sizing: border-box;
    transform: translate(0px, 0px);

}

.contained {
    box-sizing: border-box;
    display: inline-block;
    transform-origin: 0 0;
    white-space: pre;
    line-height: 1;
}
</style>
