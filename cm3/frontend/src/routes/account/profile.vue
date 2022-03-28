<template>
<div>
    <v-form ref="fContactInfo" v-model="validContactInfo">
        <v-row>
            <v-col cols="12" sm="6" md="3">
                <v-text-field label="Email Address" v-model="contactEmail" :rules="RulesEmailRequired">
                </v-text-field>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-checkbox dense hide-details v-model="contactSubscribePromotions">
                <template v-slot:label>
                      <small> You may contact me with promotional emails.<br>(You may
                        <router-link
                          :to="'unsubscribe'"
                        >Unsubscribe</router-link>
                        at any time)
                      </small>
                    </template>
                </v-checkbox>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-text-field label="Phone" v-model="contactPhone" :rules="RulesPhoneRequired"></v-text-field>
            </v-col>
        </v-row>

        <vuetify-google-autocomplete
            id="map"
            append-icon="mdi-map-search"
            @placechanged="retrieveAddress"
            placeholder="Search Address"
            types="address"
            fields="address_components"></vuetify-google-autocomplete>
        <v-row>
            <v-col cols="12" sm="6" md="3">
                <v-text-field label="Street Address" v-model="contactStreet1" :rules="RulesRequired"></v-text-field>
            </v-col>
            <v-col cols="12" sm="6" md="3">
                <v-text-field label="Street Address 2" v-model="contactStreet2"></v-text-field>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12" sm="6" md="3">
                <v-text-field label="City" v-model="contactCity"  :rules="RulesRequired"></v-text-field>
            </v-col>
            <v-col cols="6" sm="3" md="2">
                <v-text-field label="State/Province" v-model="contactState"></v-text-field>
            </v-col>
            <v-col cols="6" sm="3" md="2">
                <v-text-field label="Zip/Postal Code" v-model="contactPostalCode"></v-text-field>
            </v-col>
        </v-row>
        <v-row>

            <v-col cols="12" sm="6" md="3">
                <v-text-field label="Country" v-model="contactCountry"></v-text-field>
            </v-col>
        </v-row>
    </v-form>

        <v-btn color="primary" :disabled="!validContactInfo" @click="step = 3">Continue</v-btn>
</div>
</template>

<script>
import { mapGetters } from 'vuex'
export default {
  components: {
  },
  data: () => ({

          validContactInfo: false,
          contactReuse: false,
          contactEmail:"",
          contactSubscribePromotions:false,
          contactPhone:"",
          contactStreet1:"",
          contactStreet2:"",
          contactCity:"",
          contactState:"",
          contactPostalCode:"",
          contactCountry:"",


          RulesEmail: [
            v => !v || /.+@.+\..+/.test(v) || 'E-mail must be valid',
          ],
          RulesEmailRequired: [
            v => !!v || 'E-mail is required',
            v => /.+@.+\..+/.test(v) || 'E-mail must be valid',
          ],
          RulesPhone: [
            v =>  !v || v.length > 6 || 'Phone number too short',
            /*v =>  !v || /^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/.test(v) || 'Phone number should be valid'*/
          ],
          RulesPhoneRequired: [
            v => !!v || 'Phone number is required',
            v => v.length > 6 || 'Phone number too short',
            /*v => /^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/.test(v) || 'Phone number should be valid'*/
          ],
  }),
  computed: {

      ...mapGetters('mydata', {
        'getContactInfo': 'getContactInfo',
        'isLoggedIn': 'getIsLoggedIn',
      }),
  },
  methods: {

      retrieveAddress: function(addressdata){
        if(addressdata == null)
          return;
        this.contactStreet1=(typeof addressdata.street_number == "undefined" ? "" : addressdata.street_number + " ")  + addressdata.route;
        this.contactStreet2="";
        this.contactCity=addressdata.locality;
        this.contactState=addressdata.administrative_area_level_1;
        this.contactPostalCode=addressdata.postal_code;
        this.contactCountry=addressdata.country;
      },
  }
};
</script>
