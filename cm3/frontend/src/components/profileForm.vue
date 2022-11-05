<template>
<v-form ref="fContactInfo"
        v-model="validContactInfo">
    <v-container fluid>
        <v-row>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Contact Email Address"
                              :readonly="readonly"
                              v-model="contactEmail"
                              :rules="RulesEmailRequired">
                </v-text-field>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Contact Name"
                              :readonly="readonly"
                              v-model="contactName"
                              :rules="RulesNameFandom">
                </v-text-field>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-checkbox dense
                            :readonly="readonly"
                            hide-details
                            v-model="contactSubscribePromotions">
                    <template v-slot:label>
                        <small> You may contact me with promotional emails.<br>(You may
                            <router-link :to="'unsubscribe'">Unsubscribe</router-link>
                            at any time)
                        </small>
                    </template>
                </v-checkbox>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Phone"
                              :readonly="readonly"
                              v-model="contactPhone"
                              :rules="RulesPhone"></v-text-field>
            </v-col>
        </v-row>

        <vuetify-google-autocomplete id="map"
                                     v-if="!readonly"
                                     append-icon="mdi-map-search"
                                     @placechanged="retrieveAddress"
                                     placeholder="Search Address"
                                     types="address"
                                     fields="address_components"></vuetify-google-autocomplete>
        <v-row>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Street Address"
                              :readonly="readonly"
                              v-model="contactStreet1"></v-text-field>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Street Address 2"
                              :readonly="readonly"
                              v-model="contactStreet2"></v-text-field>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="City"
                              :readonly="readonly"
                              v-model="contactCity"></v-text-field>
            </v-col>
            <v-col cols="6"
                   sm="3"
                   md="2">
                <v-text-field label="State/Province"
                              :readonly="readonly"
                              v-model="contactState"></v-text-field>
            </v-col>
            <v-col cols="6"
                   sm="3"
                   md="2">
                <v-text-field label="Zip/Postal Code"
                              :readonly="readonly"
                              v-model="contactPostalCode"></v-text-field>
            </v-col>
        </v-row>
        <v-row>

            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Country"
                              :readonly="readonly"
                              v-model="contactCountry"></v-text-field>
            </v-col>
        </v-row>
    </v-container>
</v-form>
</template>

<script>
import VInput from 'vuetify/lib/components/VInput/VInput.js';

import {
    mapGetters
} from 'vuex'
export default {
    extends: VInput,
    components: {},
    props: {
        'value': Object,
        'readonly': Boolean
    },
    data() {
        return {

            validContactInfo: false,
            contactEmail: this.value.email_address || "",
            contactName: this.value.real_name || "",
            contactSubscribePromotions: this.value.allow_marketing == 1,
            contactPhone: this.value.phone_number || "",
            contactStreet1: this.value.address_1 || "",
            contactStreet2: this.value.address_2 || "",
            contactCity: this.value.city || "",
            contactState: this.value.state || "",
            contactPostalCode: this.value.zip_code || "",
            contactCountry: this.value.country || "",


            RulesRequired: [
                (v) => !!v || 'Required',
            ],
            RulesEmail: [
                v => !v || /.+@.+\..+/.test(v) || 'E-mail must be valid',
            ],
            RulesNameFandom: [

                (v) => (v == '' || (v && v.length <= 255)) || 'Name must be less than 255 characters',
            ],
            RulesEmailRequired: [
                v => !!v || 'E-mail is required',
                v => /.+@.+\..+/.test(v) || 'E-mail must be valid',
            ],
            RulesPhone: [
                v => !v || v.length > 6 || 'Phone number too short',
                /*v =>  !v || /^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/.test(v) || 'Phone number should be valid'*/
            ],
            RulesPhoneRequired: [
                v => !!v || 'Phone number is required',
                v => v.length > 6 || 'Phone number too short',
                /*v => /^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/.test(v) || 'Phone number should be valid'*/
            ],
        };
    },
    computed: {

        ...mapGetters('mydata', {
            'getContactInfo': 'getContactInfo',
            'isLoggedIn': 'getIsLoggedIn',
        }),
        result() {
            return {
                "allow_marketing": this.contactSubscribePromotions ? 1 : 0,
                "email_address": this.contactEmail,
                "real_name": this.contactName,
                "phone_number": this.contactPhone,
                "address_1": this.contactStreet1,
                "address_2": this.contactStreet2,
                "city": this.contactCity,
                "state": this.contactState,
                "zip_code": this.contactPostalCode,
                "country": this.contactCountry
            }
        }
    },
    methods: {
        retrieveAddress: function(addressdata) {
            if (addressdata == null)
                return;
            this.contactStreet1 = (typeof addressdata.street_number == "undefined" ? "" : addressdata.street_number + " ") + addressdata.route;
            this.contactStreet2 = "";
            this.contactCity = addressdata.locality;
            this.contactState = addressdata.administrative_area_level_1;
            this.contactPostalCode = addressdata.postal_code;
            this.contactCountry = addressdata.country;
        },
    },
    watch: {
        result(newData) {
            var isValid = this.$refs.fContactInfo.validate();
            this.$emit('valid', isValid);
            this.$emit('input', newData);
        },
        value(newValue) {
            //Splat the input into the form
            this.contactSubscribePromotions = newValue.allow_marketing;
            this.contactEmail = newValue.email_address;
            this.contactName = newValue.real_name;
            this.contactPhone = newValue.phone_number;
            this.contactStreet1 = newValue.address_1;
            this.contactStreet2 = newValue.address_2;
            this.contactCity = newValue.city;
            this.contactState = newValue.state;
            this.contactPostalCode = newValue.zip_code;
            this.contactCountry = newValue.country;
        },
    },
};
</script>
