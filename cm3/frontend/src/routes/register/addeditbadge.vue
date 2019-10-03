<template>
<v-stepper v-model="step" :vertical="true">
    <v-stepper-step :editable="reachedStep >= 1" :complete="reachedStep > 1" step="1">Badge Information</v-stepper-step>
    <v-stepper-content step="1">

        <v-form ref="fGenInfo" v-model="validGenInfo">
            <v-row>
                <v-col cols="12" md="6">
                    <v-text-field v-model="nameFirst" :counter="20" :rules="RulesName" label="First Name" required></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field v-model="nameLast" :counter="20" :rules="RulesName" label="Last Name" required></v-text-field>
                </v-col>
            </v-row>
            <v-row>

                <v-col cols="12" md="6">
                    <v-text-field v-model="nameFandom" :counter="30" :rules="RulesNameFandom" label="Fandom Name (Optional)"></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                    <v-select v-if="nameFandom" v-model="nameDisplay" :items="nameDisplayType" label="Display on badge"></v-select>
                </v-col>
            </v-row>
            <v-row>
                <v-col cols="12" md="6">
                    <v-menu ref="menuBDay" v-model="menuBDay" :close-on-content-click="false" transition="scale-transition" offset-y full-width min-width="290px">
                        <template v-slot:activator="{ on }">
                            <v-text-field v-model="birthday" type="date" label="Date of Birth" prepend-icon="mdi-calendar" v-on="on"></v-text-field>
                        </template>
                        <v-date-picker ref="pickerBDay" v-model="birthday" :max="new Date().toISOString().substr(0, 10)" min="1920-01-01" @change="saveBDay"></v-date-picker>
                    </v-menu>
                </v-col>
            </v-row>
            <h3>Badge Type</h3>
            <div class="d-none d-sm-block">
                <v-slide-group v-model="selectedBadge" class="pa-4" show-arrows mandatory center-active>
                    <v-icon slot="prev" size="100">mdi-chevron-left</v-icon>
                    <v-icon slot="next" size="100">mdi-chevron-right</v-icon>
                    <v-slide-item v-for="product in badges" :key="product.id" v-slot:default="{ active, toggle }">
                        <v-card :dark="active" :color="active ? 'primary' : 'grey lighten-1'" class="ma-4" min-width="220" @click="toggle" :disabled="product.quantity != null && !product.quantity">
                            <v-card-title align="center" justify="center">
                                {{ product.name | subname}}
                            </v-card-title>
                            <v-card-text>
                                {{ product.name | subname(true)}}&nbsp;
                            </v-card-text>
                            <v-card-actions>&nbsp;
                                <h4 text v-if="product.quantity">Only
                                    {{product.quantity}}
                                    left!</h4>
                                <h4 v-else-if="product.quantity == 0">Sold out!</h4>
                                <v-spacer></v-spacer>
                                <v-btn color="green" dark>{{product.price | currency}}</v-btn>
                            </v-card-actions>

                        </v-card>
                    </v-slide-item>
                </v-slide-group>
            </div>
            <div class="d-block d-sm-none">
                <v-select v-model="selectedBadge" :items="badges" label="Select Badge" :item-value="badgeIndex" :item-disabled="quantityZero">
                    <template v-slot:item="{item}">
                        <v-card color="grey lighten-1" class="ma-4" min-width="220" :disabled="item.quantity != null && !item.quantity">
                            <v-card-title align="center" justify="center">
                                {{ item.name | subname}}
                            </v-card-title>
                            <v-card-text>
                                {{ item.name | subname(true)}}&nbsp;
                            </v-card-text>
                            <v-card-actions>&nbsp;
                                <h4 text v-if="item.quantity">Only
                                    {{item.quantity}}
                                    left!</h4>
                                <h4 v-else-if="item.quantity == 0">Sold out!</h4>
                                <v-spacer></v-spacer>
                                <v-btn color="green" dark>{{item.price | currency}}</v-btn>
                            </v-card-actions>

                        </v-card>
                    </template>
                    <template v-slot:selection="{ item }">
                        <v-card dark color="primary" class="ma-4" min-width="220" :disabled="item.quantity != null && !item.quantity">
                            <v-card-title align="center" justify="center">
                                {{ item.name | subname}}
                            </v-card-title>
                            <v-card-text>
                                {{ item.name | subname(true)}}&nbsp;
                            </v-card-text>
                            <v-card-actions>&nbsp;
                                <h4 text v-if="item.quantity">Only
                                    {{item.quantity}}
                                    left!</h4>
                                <h4 v-else-if="item.quantity == 0">Sold out!</h4>
                                <v-spacer></v-spacer>
                                <v-btn color="green" dark>{{item.price | currency}}</v-btn>
                            </v-card-actions>

                        </v-card>
                    </template>
                </v-select>
            </div>
            <v-expand-transition>
                <v-sheet v-if="selectedBadge != null" color="grey lighten-4" tile>
                    <v-card>
                        <v-card-title class="title">Selected:
                            {{ badges[selectedBadge].name }}</v-card-title>
                        <v-card-text class="text--primary">
                            <p >
                                <b>Description:</b>
                                {{ badges[selectedBadge].description }}</p>
                            <h4>Rewards:</h4>
                            <ul>
                                <li v-for="reward in rewardlist" :key="reward">
                                    {{reward}}
                                </li>
                            </ul>
                        </v-card-text>
                    </v-card>
                </v-sheet>
            </v-expand-transition>
        </v-form>

        <v-btn color="primary" :disabled="!validGenInfo" @click="step = 2">Continue</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 2" :complete="step > 2" step="2">Contact Information</v-stepper-step>
    <v-stepper-content step="2">
        <v-form ref="fContactInfo" v-model="validContactInfo">
          <v-row>
              <v-col cols="12" sm="6" md="3">
                  <v-text-field label="Email Address" v-model="contactEmail"></v-text-field>
              </v-col>
              <v-col cols="12" sm="6" md="3">
                  <v-text-field label="Phone" v-model="contactPhone"></v-text-field>
              </v-col>
              <v-col><v-switch v-model="contactReuse" inset label="Reuse previous" ></v-switch></v-col>
          </v-row>

            <vuetify-google-autocomplete id="map" append-icon="mdi-map-search" @placechanged="retrieveAddress" placeholder="Search Address" types="address" fields="address_components"></vuetify-google-autocomplete>
            <v-row>
                <v-col cols="12" sm="6" md="3">
                    <v-text-field label="Street Address" v-model="contactStreet1"></v-text-field>
                </v-col>
                <v-col cols="12" sm="6" md="3">
                    <v-text-field label="Street Address 2" v-model="contactStreet2"></v-text-field>
                </v-col>
            </v-row>
            <v-row>
                <v-col cols="12" sm="6" md="3">
                    <v-text-field label="City" v-model="contactCity"></v-text-field>
                </v-col>
                <v-col cols="6" sm="3" md="2">
                    <v-text-field label="State/Province" v-model="contactState"></v-text-field>
                </v-col>
                <v-col cols="6" sm="2" md="1">
                    <v-text-field label="Zip/Postal Code" v-model="contactPostalCode"></v-text-field>
                </v-col>
            </v-row>
            <v-row>

                <v-col cols="12" sm="6" md="3">
                    <v-text-field label="Country" v-model="contactCountry"></v-text-field>
                </v-col>
            </v-row>
            <h3>In case of Emergency</h3>
            <v-row>

              <v-col cols="12" sm="6" md="3">
                  <v-text-field label="Emergency Contact Name" v-model="contactEmergencyName"></v-text-field>
              </v-col>
              <v-col cols="12" sm="6" md="3">
                  <v-text-field label="Relationship" v-model="contactEmergencyRelationship"></v-text-field>
              </v-col>
              <v-col cols="12" sm="6" md="3">
                  <v-text-field label="Email address" v-model="contactEmergencyEmail"></v-text-field>
              </v-col>
              <v-col cols="12" sm="6" md="3">
                  <v-text-field label="Phone Number" v-model="contactEmergencyPhone"></v-text-field>
              </v-col>
            </v-row>
        </v-form>
        <v-btn color="primary" @click="step = 3">Continue</v-btn>
        <v-btn text @click="step = 1">Back</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 3" :complete="step > 3" step="3">Additional Information</v-stepper-step>

    <v-stepper-content step="3">
        <v-card color="grey lighten-1" class="mb-12" height="200px">Auto-gen optional questions here</v-card>
        <v-btn color="primary" @click="step = 4">Continue</v-btn>
        <v-btn text @click="step = 2">Back</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 4" :complete="step > 4" step="4">Add-ons</v-stepper-step>

    <v-stepper-content step="4">
        <v-card color="grey lighten-1" class="mb-12" height="200px">Add-ons here!</v-card>
        <v-btn color="primary" @click="addBadgeToCart">{{ cartId > 0 || editBadgeId > 0 ? "Update in " :  "Add to "}} Cart</v-btn>
        <v-btn text @click="step = 3">Back</v-btn>
    </v-stepper-content>

</v-stepper>
</template>

<script>
import { mapState, mapActions } from 'vuex'
export default {
  data () {
      return {
        step: 0,
        reachedStep: 0,
        cartId:0,
        editBadgeId:0,
        editBadgeType:'',

        validGenInfo: true, //TODO: Make that computed?
        nameFirst:"",
        nameLast:"",
        nameFandom:"",
        nameDisplay:null,
        nameDisplayType: ["Fandom Name Large, Real Name Small", "Real Name Large, Fandom Name Small","Real Name Only", "Fandom Name Only"],
        birthday:null,
        selectedBadge: null,
        menuBDay:false,

        validContactInfo: true, //TODO: Make that computed?
        contactReuse: false,
        contactEmail:"",
        contactPhone:"",
        contactStreet1:"",
        contactStreet2:"",
        contactCity:"",
        contactState:"",
        contactPostalCode:"",
        contactCountry:"",
        contactEmergencyName:"",
        contactEmergencyRelationship:"",
        contactEmergencyEmail:"",
        contactEmergencyPhone:"",


      RulesName: [
        v => !!v || 'Name is required',
        v => (v && v.length <= 20) || 'Name must be less than 20 characters',
      ],
            RulesNameFandom: [

              v => (v == "" || (v && v.length <= 30)) || 'Name must be less than 30 characters',
            ],
      emailRules: [
        v => !!v || 'E-mail is required',
        v => /.+@.+\..+/.test(v) || 'E-mail must be valid',
      ],

      }
    },
  computed: {
    ...mapState({
      products: state => state.products.all
    }),
    rewardlist: function() {
      return this.$options.filters.split_carriagereturn(this.badges[this.selectedBadge].rewards);
    },
    badges: function() {
      let badges = this.products;
      //First, do we have a birthday?
      var bday = new Date(this.birthday)
      if(this.birthday && bday)
      {

        //TODO: Use the event start date?
        var age = ((new Date()).getTime() - bday.getTime()) / (1000  * 60 * 60 * 24 * 365.25);
        badges = badges.filter(badge => {
          if((badge.min_age == null || badge.min_age < age)
            &&(badge.max_age == null || badge.max_age >= age)
        )
          return badge;
        });

      }
      badges.sort((a,b) => a.order - b.order);
      return badges;
    },
    compiledBadge: function() {
      //Special because of how the select dropdown works
      var selectedbadge = this.badges[this.selectedBadge]
      return {

        cartId:this.cartId,
        editBadgeId:this.editBadgeId,
        editBadgeType:this.editBadgeType,

        nameFirst: this.nameFirst,
        nameLast: this.nameLast,
        nameFandom: this.nameFandom,
        nameDisplay: this.nameDisplay,
        birthday: this.birthday,
        selectedBadgeId: selectedbadge.id,

        contactEmail:this.contactEmail,
        contactPhone:this.contactPhone,
        contactStreet1:this.contactStreet1,
        contactStreet2:this.contactStreet2,
        contactCity:this.contactCity,
        contactState:this.contactState,
        contactPostalCode:this.contactPostalCode,
        contactCountry:this.contactCountry,
        contactEmergencyName:this.contactEmergencyName,
        contactEmergencyRelationship:this.contactEmergencyRelationship,
        contactEmergencyEmail:this.contactEmergencyEmail,
        contactEmergencyPhone:this.contactEmergencyPhone,

      };
    },
  },
  watch: {
    step: function(newStep){
      this.reachedStep = Math.max(this.reachedStep,newStep);
    },
    menuBDay (val) {
        val && setTimeout(() => (this.$refs.pickerBDay.activePicker = 'YEAR'))
      },
  },
  methods: {
      ...mapActions('cart', [
      'addProductToCart',
      'getProductInCart'
    ]),
    saveBDay(date) {
      this.$refs.menuBDay.save(date);
      this.birthday = this.birthday;
    },
    loadBadge(){
      if(this.$route.params.cartId > 0)
      {
        this.cartId = this.$route.params.cartId;
        //Load up the badge from the cart
        var cartItem = this.$store.getters["cart/getProductInCart"](this.cartId);
        Object.assign(this, cartItem);
        //Special props
        this.selectedBadge = this.badges.findIndex(badge => badge.id == cartItem.selectedBadgeId);
        this.reachedStep = 4;
      }
    },
    addBadgeToCart() {

      this.addProductToCart(this.compiledBadge);
      //TODO: Resolve a promise from the above so we can update the history to point to the edit-badge version of this

      //Go to the cart
      this.$router.push("/cart");
    },
    badgeIndex: function(item){
      return this.badges.indexOf(item);
    },
    quantityZero: function(item) {
      return item.quantity == 0;
    },
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
  },
  created () {
      this.$store.dispatch('products/getAllProducts')
      this.loadBadge();
  }
}
</script>
