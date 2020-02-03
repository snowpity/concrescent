<template>
<v-stepper v-model="step" :vertical="true">
    <v-stepper-step :editable="reachedStep >= 1" :complete="reachedStep > 1" step="1">Badge Information <small>{{compiledBadge | badgeDisplayName}} &mdash; {{ badges[selectedBadge] ? badges[selectedBadge].name: "Nothing yet!" | subname }}</small></v-stepper-step>
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
                    <v-menu ref="menuBDay" v-model="menuBDay" :close-on-content-click="false" transition="scale-transition" offset-y min-width="290px">
                        <template v-slot:activator="{ on }">
                            <v-text-field v-model="birthday" type="date" label="Date of Birth" prepend-icon="mdi-calendar" v-on="on" :rules="RulesRequired"></v-text-field>
                        </template>
                        <v-date-picker ref="pickerBDay" v-model="birthday" :max="new Date().toISOString().substr(0, 10)" min="1920-01-01" @change="saveBDay"></v-date-picker>
                    </v-menu>
                </v-col>
                <v-col cols="12" md="6">
                  <v-btn @click="resetBadge" class="float-right">Reset Form</v-btn>
                </v-col>
            </v-row>
            <h3>Badge Type</h3>
            <div class="d-none d-sm-block">
                <v-slide-group v-model="selectedBadge" class="pa-4" show-arrows mandatory center-active>
                    <v-icon slot="prev" size="100">mdi-chevron-left</v-icon>
                    <v-icon slot="next" size="100">mdi-chevron-right</v-icon>
                    <v-slide-item v-for="product in badges" :key="product.id" v-slot:default="{ active, toggle }">
                        <v-card
                            :dark="active"
                            :color="active ? 'primary' : 'grey lighten-1'"
                            class="ma-4"
                            min-width="220"
                            @click="toggle"
                            :disabled="product.quantity != null && !product.quantity">
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
                            {{ badges[selectedBadge] ? badges[selectedBadge].name : "Nothing yet!" }}</v-card-title>
                        <v-card-text class="text--primary">
                          <badgePerksRender :description="badges[selectedBadge] ? badges[selectedBadge].description : '' " :rewardlist="rewardlist"></badgePerksRender>
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
                    <v-switch v-model="contactReuse" inset label="Reuse previous"></v-switch>
                </v-col>
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
            <h3>In case of Emergency</h3>
            <v-row>

                <v-col cols="12" sm="6" md="3">
                    <v-text-field label="Emergency Contact Name" v-model="contactEmergencyName"></v-text-field>
                </v-col>
                <v-col cols="12" sm="6" md="3">
                    <v-text-field label="Relationship" v-model="contactEmergencyRelationship"></v-text-field>
                </v-col>
                <v-col cols="12" sm="6" md="3">
                    <v-text-field label="Email address" v-model="contactEmergencyEmail" :rules="RulesEmail"></v-text-field>
                </v-col>
                <v-col cols="12" sm="6" md="3">
                    <v-text-field label="Phone Number" v-model="contactEmergencyPhone" :rules="RulesPhone"></v-text-field>
                </v-col>
            </v-row>
        </v-form>
        <v-btn color="primary" :disabled="!validContactInfo" @click="step = 3">Continue</v-btn>
        <v-btn text @click="step = 1">Back</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 3" :complete="step > 3" step="3">Additional Information</v-stepper-step>

    <v-stepper-content step="3">
        <v-form ref="fAdditionalInfo" v-model="validAdditionalInfo">
            <v-row v-for="question in badgeQuestions" v-bind:key="question.id">
                <badgeQuestionRender v-bind:question="question" v-model="questionResponses[question['question-id'].toString()]"></badgeQuestionRender>
            </v-row>
        </v-form>
        <v-btn color="primary" :disabled="!validAdditionalInfo" @click="step = 4">Continue</v-btn>
        <v-btn text @click="step = 2">Back</v-btn>
    </v-stepper-content>

    <v-stepper-step :editable="reachedStep >= 4" :complete="step > 4" step="4">Choose your Add-ons <small v-if="addonsSelected.length">{{addonsSelected.length}} Selected</small></v-stepper-step>

    <v-stepper-content step="4">
        <v-expansion-panels v-model="addonDisplayState" multiple v-if="badgeAddons.length">
            <v-expansion-panel v-for="addon in badgeAddons" v-bind:key="addon.id">
                <v-expansion-panel-header>
                    <v-checkbox hide-details multiple :value="addon['id']" v-model="addonsSelected">
                        <template slot="label">
                            <h3 class="black--text">{{addon.name}}</h3>
                        </template>
                    </v-checkbox>
                    <template slot="actions">
                        <h4 text v-if="addon['quantity-remaining']">Only
                            {{addon['quantity-remaining']}}
                            left!</h4>
                        <h4 v-else-if="addon['quantity-remaining'] == 0">Sold out!</h4>
                        <v-btn class="ml-5" color="green" dark>{{addon.price | currency}}</v-btn>
                        <v-icon class="px-3" color="primary">$expand</v-icon>
                    </template>
                </v-expansion-panel-header>
                <v-expansion-panel-content>
                    <div v-html="addon.description"></div>
                </v-expansion-panel-content>
            </v-expansion-panel>

        </v-expansion-panels>
        <div v-else>
          <h3>No addons are currently available. Check back later when they become available!</h3>
        </div>
        <v-btn color="primary" @click="addBadgeToCart">{{ cartId > 0 || editBadgeId > 0 ? "Update in " :  "Add to "}}
            Cart</v-btn>
        <v-btn text @click="step = 3">Back</v-btn>
    </v-stepper-content>

</v-stepper>
</template>

<script>
import { mapState, mapActions } from 'vuex'

import badgeQuestionRender from '@/components/badgeQuestionRender.vue'
import badgePerksRender from '@/components/badgePerksRender.vue'

export default {
  data () {
      return {
        step: 1,
        reachedStep: 1,
        cartId:0,
        editBadgeId:0,
        editBadgeType:'',

        validGenInfo: false,
        nameFirst:"",
        nameLast:"",
        nameFandom:"",
        nameDisplay:null,
        nameDisplayType: ["Fandom Name Large, Real Name Small", "Real Name Large, Fandom Name Small","Real Name Only", "Fandom Name Only"],
        birthday:null,
        selectedBadge: null,
        menuBDay:false,

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
        contactEmergencyName:"",
        contactEmergencyRelationship:"",
        contactEmergencyEmail:"",
        contactEmergencyPhone:"",

        validAdditionalInfo: false,
        questionResponses: {},
        addonsSelected: [],


        RulesRequired: [
          v => !!v || 'Required'
        ],
        RulesName: [
          v => !!v || 'Name is required',
          v => (v && v.length <= 20) || 'Name must be less than 20 characters',
        ],
        RulesNameFandom: [

          v => (v == "" || (v && v.length <= 30)) || 'Name must be less than 30 characters',
        ],
        RulesEmail: [
          v => !v || /.+@.+\..+/.test(v) || 'E-mail must be valid',
        ],
        RulesEmailRequired: [
          v => !!v || 'E-mail is required',
          v => /.+@.+\..+/.test(v) || 'E-mail must be valid',
        ],
        RulesPhone: [
          v =>  !v || v.length > 6 || 'Phone number too short',
          v =>  !v || /^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/.test(v) || 'Phone number should be valid'
        ],
        RulesPhoneRequired: [
          v => !!v || 'Phone number is required',
          v => v.length > 6 || 'Phone number too short',
          v => /^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/.test(v) || 'Phone number should be valid'
        ],

      addonDisplayState:[]
      }
    },
  computed: {
    ...mapState({
      products: state => state.products.all,
      questions: state => state.products.questions,
      addons: state => state.products.addons
    }),
    rewardlist: function() {
      //return this.$options.filters.split_carriagereturn(this.badges[this.selectedBadge].rewards);
      return this.badges[this.selectedBadge] ? this.badges[this.selectedBadge].rewards : [];
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
      var selectedbadge =  typeof this.badges[this.selectedBadge] == "undefined" ? null : this.badges[this.selectedBadge].id;
      return {

        cartId:this.cartId,
        editBadgeId:this.editBadgeId,
        editBadgeType:this.editBadgeType,

        nameFirst: this.nameFirst,
        nameLast: this.nameLast,
        nameFandom: this.nameFandom,
        nameDisplay: this.nameDisplay,
        birthday: this.birthday,
        selectedBadgeId: selectedbadge,

        contactEmail:this.contactEmail,
        subscribed:this.contactSubscribePromotions,
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
        questionResponses:this.questionResponses,
        addonsSelected:this.addonsSelected

      };
    },
    currentContactInfo: function() {
      return {

                contactEmail:this.contactEmail,
                subscribed:this.contactSubscribePromotions,
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
    badgeQuestions: function() {
      //Todo: Filter by badge context
      var badgeId = typeof this.badges[this.selectedBadge] == "undefined" ? "" : this.badges[this.selectedBadge].id.toString();
      //Filter out the ones that don't apply to this badge
      var result = this.questions.filter (function(question){
        var include = true;
        include = include && question.active;
        include = include && (question.visible == '*' || question.visible.includes(badgeId));
        return include;
      });
      //Apply logic to required
      result.forEach(function(question){
        question.isRequired = question.required == '*' || question.required.includes(badgeId)
      })
      //Sort it out
      result.sort((a,b) => a.order - b.order);
      return result;
    },
    badgeAddons: function() {
      //Todo: Filter by badge context
      var badgeId = typeof this.badges[this.selectedBadge] == "undefined" ? "" : this.badges[this.selectedBadge].id.toString();
      //Filter out the ones that don't apply to this badge
      var result = this.addons.filter (function(addon){
        var include = true;
        include = include && addon.active;
        include = include && (addon["badge-type-ids"] == '*' || addon["badge-type-ids"].includes(badgeId));
        //TODO: Birthday filter
        return include;
      });
      ////Apply logic to required
      //result.forEach(function(question){
      //  question.isRequired = question.required == '*' || question.required.includes(badgeId)
      //})
      //Sort it out
      result.sort((a,b) => a.order - b.order);
      return result;
    },
  },
  watch: {
    step: function(newStep){
      this.reachedStep = Math.max(this.reachedStep,newStep);
    },
    menuBDay (val) {
      val && setTimeout(() => (this.$refs.pickerBDay.activePicker = 'YEAR'))
    },
    contactReuse(val) {
      if(val){
        //Apply the contact info
        var contactInfo  = this.$store.getters["cart/getLatestContactInfo"];
        Object.assign(this, contactInfo);
      }
    },
    compiledBadge() {
      this.autoSaveBadge();
    },
    '$route.name' : function(name) {
      //The only way this changes is... if they click the Add Badge from the main menu while still here
      //Still, in case of weirdness...
      if(name == "addbadge")
      {
        this.resetBadge();
      }
    }
  },
  methods: {
      ...mapActions('cart', [
      'addProductToCart',
      'setLatestContactInfo',
    ]),
    saveBDay(date) {
      this.$refs.menuBDay.save(date);
      this.birthday = this.birthday;
    },
    loadBadge(){
      var cartItem;
      if(this.$route.params.cartId > 0)
      {
        this.cartId = this.$route.params.cartId;
        //Load up the badge from the cart
        cartItem = this.$store.getters["cart/getProductInCart"](this.cartId);
        Object.assign(this, cartItem);
        //Special props
        this.selectedBadge = this.badges.findIndex(badge => badge.id == cartItem.selectedBadgeId);
        this.reachedStep = 4;
      }
      else if(this.$route.params.cartId == undefined)
      {
        //It's a new badge or they're back here from a refresh/navigation
        cartItem = this.$store.getters["cart/getCurrentlyEditingItem"];
        Object.assign(this, cartItem);
        //Should only be needed if we didn't have a selectedBadge?
        //this.selectedBadge = this.badges.findIndex(badge => badge.id == cartItem.selectedBadgeId);
        this.step = cartItem.step;

      }
    },
    resetBadge(){
      Object.assign(this.$data, this.$options.data.apply(this));
      this.step = 1;
    },
    autoSaveBadge(){
      var cartItem = this.compiledBadge;
      cartItem.reachedStep = this.reachedStep;
      cartItem.step = parseInt(this.step);
      cartItem.selectedBadge=this.selectedBadge;
      this.$store.commit("cart/setCurrentlyEditingItem", cartItem);

    },
    addBadgeToCart() {

      this.addProductToCart(this.compiledBadge);
      this.$store.commit("cart/setLatestContactInfo", this.currentContactInfo);
      //TODO: Resolve a promise from the above so we can update the history to point to the edit-badge version of this
      this.resetBadge();
      //Also reset the saved info so we don't accidentally resume editing
      this.autoSaveBadge();
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
  components: {
    badgeQuestionRender,
    badgePerksRender
  },
  created () {
      this.$store.dispatch('products/getAllProducts')
      this.$store.dispatch('products/getAllQuestions')
      this.$store.dispatch('products/getAllAddons')
      this.loadBadge();
  }
}
</script>
