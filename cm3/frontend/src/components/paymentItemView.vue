<template>
<v-container fluid>
    <v-row>
        <v-col cols="12"
               sm="6"
               md="3">
            <v-text-field label="Payment ID"
                          v-model="payment_id">
            </v-text-field>
        </v-col>
        <v-col cols="12"
               sm="6"
               md="3">
            <v-text-field label="Payment Status"
                          v-model="payment_status">
            </v-text-field>
        </v-col>
        <v-col cols="12"
               sm="6"
               md="3">
            <v-text-field label="Payment Price"
                          v-model="payment_promo_price"></v-text-field>
        </v-col>
    </v-row>
</v-container>
</template>

<script>
import {
    mapGetters
} from 'vuex'
export default {
    components: {},
    props: ['value'],
    data() {
        return {

            payment_id: this.value.payment_id,
            payment_status: this.value.payment_status,
            payment_promo_price: this.value.payment_promo_price,


        };
    },
    computed: {

        ...mapGetters('mydata', {
            'getContactInfo': 'getContactInfo',
            'isLoggedIn': 'getIsLoggedIn',
        }),
        result() {
            return {
                "payment_id": this.payment_id,
                "payment_status": this.payment_status,
            }
        }
    },
    methods: {

    },
    watch: {
        result(newData) {
            //this.$emit('input', newData);
        },
        value: {
            handler: function(newValue) {
                //Splat the input into the form
                console.log('new value received', newValue)
                this.payment_id = newValue.payment_id;
                this.payment_status = newValue.payment_status;
            },
            deep: true
        }
    },
};
</script>
