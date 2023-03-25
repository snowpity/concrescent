<template>
<div :style="format.style">
    <template v-if="format.type == 'debug'">
        <pre>{{format}}</pre>
    </template>
    <template v-else-if="format.type == 'simpletext'">
        {{renderText}}
    </template>
    <template v-else-if="format.type == 'text'">
        <v-md-preview v-if="renderText != undefined && renderText.length > 0"
                      :text="renderText" />
    </template>
    <template v-else-if="format.type == 'image'">
        Image goes here
    </template>
    <template v-else>
        Unknown field type: {{format.type}}
    </template>
</div>
</template>

<script>
function getValueByPath(input, s) {
    s = s.replace(/\[(\w+)\]/g, '.$1'); // convert indexes to properties
    s = s.replace(/^\./, ''); // strip a leading dot
    var a = s.split('.');
    for (var i = 0, n = a.length; i < n; ++i) {
        var k = a[i];
        if (isObject(input) && k in input) {
            input = input[k];
        } else {
            return;
        }
    }
    return input;
}

function isObject(o) {
    //How you acomplish this is upto you.
    return o === Object(o);
}

//Adapted from https://stackoverflow.com/a/63108491
function expandTpl(template, templateData) { // s0 is the link_template_input
    //TODO: Fix this regex so it allows [index]-style?
    const r = /\[\[([^\[\]]*)\]\]/gm;
    let s = '';
    let idx = 0
    for (let a;
        (a = r.exec(template)) !== null;) {
        //console.log('expanding', a[0])
        var v = getValueByPath(templateData, a[1]);
        if (v == undefined) {
            console.log('object did not have this property', a[0])
            //v = a[0];
            v = '';
        }
        s += (template.substring(idx, r.lastIndex - a[0].length) + v)
        idx = r.lastIndex
    }
    if (idx < template.length) s += template.substring(idx, template.length)
    return s
}

export default {
    props: ['format', 'value'],
    data: () => ({
        // userResponse: ""
    }),
    methods: {},
    computed: {
        renderText() {
            if (!this.value) {
                //console.log('rendering bare template because badge', this.value)
                return this.format.text;
            }
            //console.log('rendering template', this.format.text)
            //var r = expandTpl(this.format.text, this.value);
            //console.log('render result', r)
            return expandTpl(this.format.text, this.value);
        }
    },

};
</script>
