<template>
<v-container fluid
             fill-height>
    <v-layout align-center
              justify-center>
        <v-flex xs12
                sm8
                md4>
            <v-tabs v-model="state"
                    show-arrows
                    background-color="deep-purple accent-4"
                    class="elevation-12"
                    icons-and-text
                    dark
                    grow>
                <v-toolbar color="primary"
                           dark
                           flat>
                    <v-toolbar-title v-show="state==0">Retrieve badges</v-toolbar-title>
                    <v-toolbar-title v-show="state==1">Login</v-toolbar-title>
                    <v-toolbar-title v-show="state==2">Email sent!</v-toolbar-title>
                    <v-toolbar-title v-show="state==3">Logged in!</v-toolbar-title>
                    <v-toolbar-title v-show="state==4">Failed!</v-toolbar-title>

                </v-toolbar>
                <v-tab-item>
                    <v-card class="px-4">
                        <v-form @submit.prevent="SendMagicLink"
                                v-model="formEMValid">
                            {{$route.params.message}}
                            <v-card-text>
                                <v-text-field label="Email used to register"
                                              v-model="email"
                                              name="email"
                                              prepend-icon="mdi-email"
                                              type="email"
                                              :rules="[rules.required]"></v-text-field>
                            </v-card-text>
                            <v-card-actions>
                                <v-btn color="default"
                                       @click="state=1"
                                       v-if="!loading">Use a password</v-btn>
                                <v-spacer></v-spacer>
                                <v-btn color="primary"
                                       type="submit"
                                       :disabled="!formEMValid || loading"
                                       :loading="loading">Send Magic Link</v-btn>
                            </v-card-actions>
                        </v-form>
                    </v-card>
                </v-tab-item>
                <v-tab-item>
                    <v-card class="px-4">
                        <v-form @submit.prevent="login"
                                v-model="formPWValid">
                            {{$route.params.message}}
                            <v-card-text>
                                <v-text-field id="username"
                                              label="Username"
                                              v-model="username"
                                              name="username"
                                              prepend-icon="mdi-account"
                                              type="text"
                                              :rules="[rules.required]"></v-text-field>
                                <v-text-field id="password"
                                              label="Password"
                                              v-model="password"
                                              name="password"
                                              prepend-icon="mdi-lock"
                                              :append-icon="showpassword ? 'mdi-eye' : 'mdi-eye-off'"
                                              :rules="[rules.required]"
                                              :type="showpassword ? 'text' : 'password'"></v-text-field>
                            </v-card-text>
                            <v-card-actions>
                                <v-btn color="default"
                                       @click="state=0"
                                       v-if="!loading">Send a magic link</v-btn>
                                <v-spacer></v-spacer>
                                <v-btn color="primary"
                                       type="submit"
                                       :disabled="!formPWValid || loading"
                                       :loading="loading">Login</v-btn>
                            </v-card-actions>
                        </v-form>
                    </v-card>
                </v-tab-item>
                <v-tab-item>
                    <v-card>
                        <v-card-text>If you have purchased any badges with the contact email <b>{{email}}</b>, you should receive the badge retrieval email shortly to confirm.</v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="green darken-1"
                                   @click="state = 0">Try again</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-tab-item>
                <v-tab-item>
                    <v-card>
                        <v-card-text>Login Success!</v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="green darken-1"
                                   :to="returnTo">OK</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-tab-item>
                <v-tab-item>
                    <v-card>
                        <v-card-text>{{loginFailReason}}</v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="green darken-1"
                                   @click="state = 1">Try again</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-tab-item>
            </v-tabs>

        </v-flex>
    </v-layout>
</v-container>
</template>

<script>
import {
    mapActions
} from 'vuex';

export default {
    data: () => ({
        state: 0,
        loading: false,
        formEMValid: false,
        formPWValid: false,
        formValid: false,
        email: '',
        username: '',
        password: '',
        showpassword: false,
        loginFailReason: "???",

        rules: {
            required: value => !!value || 'Required.',
        },
    }),
    computed: {
        isAdmin: function() {
            return this.$store.getters['mydata/hasPerms'];
        },
        event_id: function() {
            return this.$store.getters['products/selectedEventId'];
        },
        returnTo: function() {
            const currentRoute = this.$router.currentRoute;
            if (currentRoute.query != undefined && currentRoute.query.returnTo != undefined)
                return currentRoute.query.returnTo;
            if (currentRoute.params != undefined && currentRoute.params.returnTo != undefined)
                return currentRoute.params.returnTo;

            if (this.isAdmin) return '/';

            return '/';
        }
    },
    methods: {
        ...mapActions('mydata', [
            'sendRetrieveBadgeEmail',
            'loginToken',
            'loginPassword',
        ]),
        SendMagicLink() {
            this.loading = true;
            this.sendRetrieveBadgeEmail({
                    email_address: this.email,
                    returnTo: this.returnTo
                }).then(() => {
                    this.state = 2;
                    this.loading = false;
                })
                .catch((errorresult) => {
                    this.state = 4;
                    this.loginFailReason = errorresult.error.message;
                    this.loading = false;
                });
        },
        login() {
            this.loading = true;
            this.loginPassword({
                username: this.username,
                password: this.password,
                event_id: this.event_id
            }).then((success) => {
                if (success === true) {
                    this.state = 3;
                } else {
                    this.loginFailReason = success;
                    this.state = 4;
                }
                this.loading = false;
            });
        },
        completeLogin() {
            let {
                query
            } = this.$route;
            if (query.token != undefined) {
                this.loginToken(query.token).then((success) => {
                    if (success === true) {
                        this.state = 3;
                    } else {
                        this.loginFailReason = success;
                        this.state = 4;
                    }
                    this.loading = false;
                });
                // Presumably they're here from a Magic link
                // Which *probably* means it was successful, so... clear the cart!
                //this.$router.replace({ ...this.$router.currentRoute, query: {} });
            }
        }
    },
    watch: {
        $route() {
            this.$nextTick(this.completeLogin);
        }
    },
    created() {
        this.completeLogin();
    }
};
</script>
