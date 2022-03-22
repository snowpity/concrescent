<template>
<v-container fluid fill-height>
    <v-layout align-center justify-center>
        <v-flex xs12 sm8 md4>
            <v-card class="elevation-12">
                <v-toolbar color="primary" dark flat>
                    <v-toolbar-title v-show="!showpassword">Retrieve badges</v-toolbar-title>
                    <v-toolbar-title v-show="showpassword">Login</v-toolbar-title>
                    <v-spacer></v-spacer>
                </v-toolbar>
                <v-card-text>
                    <v-form>
                        <v-text-field v-show="!showpassword" label="Email used to purchase" v-model="email" name="email" prepend-icon="mdi-email" type="email"></v-text-field>

                        <v-text-field v-show="showpassword" id="username" label="Username" v-model="username"  name="username" prepend-icon="mdi-account" type="text"></v-text-field>
                        <v-text-field v-show="showpassword" id="password" label="Password" v-model="password" name="password" prepend-icon="mdi-lock" type="password"></v-text-field>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-btn color="default" @click="showpassword=!showpassword">Use a {{ showpassword ? "Magic Link" : "password"}}</v-btn>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" v-show="!showpassword" @click="login">Send Magic Link</v-btn>
                    <v-btn color="primary" v-show="showpassword"  @click="login">Login</v-btn>
                </v-card-actions>
            </v-card>
        </v-flex>
    </v-layout>
    <v-dialog v-model="emailsent" persistent max-width="290">
     <v-card>
       <v-card-title class="headline">Email sent</v-card-title>
       <v-card-text>If you have purchased any badges with the contact email <b>{{email}}</b>, you should receive the badge retrieval email shortly to confirm.</v-card-text>
       <v-card-actions>
         <v-spacer></v-spacer>
         <v-btn color="green darken-1" text @click="emailsent = false">OK</v-btn>
       </v-card-actions>
     </v-card>
   </v-dialog>
</v-container>
</template>

<script>
import { mapActions } from 'vuex';

export default {
  data: () => ({
    showpassword: false,
    email: '',
    username: '',
    password: '',
    emailsent: false,
  }),
  methods: {
    ...mapActions('mydata', [
      'sendRetrieveBadgeEmail',
      'loginToken',
    ]),
    login() {
        if(this.showpassword) {

        } else {
          this.sendRetrieveBadgeEmail(this.email);
          this.emailsent = true;
        }
    },
  },
  created() {
    let { query } = this.$route;
    if (query.token != undefined) {
      this.loginToken(query.token);
      // Presumably they're here from a Magic link
      // Which *probably* means it was successful, so... clear the cart!
      //this.$router.replace({ ...this.$router.currentRoute, query: {} });
    }

  }
};
</script>
