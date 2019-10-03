<template>
<v-container fluid fill-height>
    <v-layout align-center justify-center>
        <v-flex xs12 sm8 md4>
            <v-card class="elevation-12">
                <v-toolbar color="primary" dark flat>
                    <v-toolbar-title>Retrieve badges</v-toolbar-title>
                    <v-spacer></v-spacer>
                </v-toolbar>
                <v-card-text>
                    <v-form>
                        <v-text-field label="Email used to purchase" name="email" prepend-icon="mdi-email" type="email"></v-text-field>

                        <v-text-field v-show="showpassword" id="password" label="Password" name="password" prepend-icon="mdi-lock" type="password"></v-text-field>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-btn color="default" @click="showpassword=!showpassword">Use a {{ showpassword ? "Magic Link" : "password"}}</v-btn>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" v-show="!showpassword">Send Magic Link</v-btn>
                    <v-btn color="primary" v-show="showpassword">Login</v-btn>
                </v-card-actions>
            </v-card>
        </v-flex>
    </v-layout>
</v-container>
</template>

<script>
export default {
  data: () => ({
    showpassword:false,
  }),
};
</script>
