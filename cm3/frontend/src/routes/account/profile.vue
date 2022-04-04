<template>
<v-container>
    <v-card>
        <profileForm v-model="editingProfileData" />
    </v-card>

    <v-snackbar v-model="saved"
                color="primary"
                :timeout="4000">
        Profile saved!
    </v-snackbar>
    <v-snackbar v-model="created"
                color="primary"
                :timeout="9000">
        Profile created!
    </v-snackbar>

    <v-row>
        <v-col>
            <v-btn text
                   x-large
                   disabled
                   block>
                <!-- Hack allocating space for the footer -->
            </v-btn>
        </v-col>
    </v-row>
    <v-footer fixed
              cols="12">
        <v-spacer></v-spacer>

        <v-btn color="primary"
               v-if="isLoggedIn"
               :disabled="saving"
               :loading="saving"
               @click="save">Save
        </v-btn>
        <v-btn v-else
               color="primary"
               :disabled="saving"
               :loading="saving"
               @click="create">Create
        </v-btn>

    </v-footer>

    <v-dialog transition="dialog-top-transition"
              max-width="600"
              v-model="isCreateError">
        <v-card>
            <v-toolbar color="error"
                       dark>
                <h1>Profile creation error</h1>
            </v-toolbar>
            <v-card-text>
                <div class="text-h5 pa-4">{{createError}}</div>
            </v-card-text>
            <v-card-actions>
                <v-btn color="green"
                       :disabled="sendingmagicemail"
                       :loading="sendingmagicemail"
                       @click="SendMagicLink">Send magic link?</v-btn>
                <v-spacer />
                <v-btn color="primary"
                       @click="createError = ''">Try again</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-dialog max-width="600"
              v-model="sentmagicmail">
        <v-card>
            <v-toolbar color="primary"
                       dark>
                <h1>Magic link sent</h1>
            </v-toolbar>
            <v-card-text>
                <v-card-text>If you have purchased any badges with the contact email <b>{{this.editingProfileData.email_address}}</b>, you should receive the badge retrieval email shortly to confirm.</v-card-text>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="primary"
                       @click="closeerror">Ok</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>


</v-container>
</template>
<script>
import {
    mapGetters,
    mapActions
} from 'vuex'
import profileForm from '@/components/profileForm.vue';

export default {
    components: {
        profileForm,
    },
    data: () => ({
        editingProfileData: {},
        saving: false,
        saved: false,
        saveError: "",
        created: false,
        createError: "",
        sendingmagicemail: false,
        sentmagicmail: false
    }),
    computed: {

        ...mapGetters('mydata', {
            'getContactInfo': 'getContactInfo',
            'isLoggedIn': 'getIsLoggedIn',
        }),
        isSaveError() {
            return this.saveError.length > 0;
        },
        isCreateError: {
            get() {
                return this.createError.length > 0;
            },
            set(newval) {
                this.createError = newval ? "???" : "";
            }
        },
    },
    methods: {
        ...mapActions('mydata', [
            'createAccount',
            'updateContactInfo',
            'sendRetrieveBadgeEmail',
        ]),
        SendMagicLink() {
            this.sendingmagicemail = true;
            this.sendRetrieveBadgeEmail(this.editingProfileData.email_address).then(() => {
                this.sentmagicmail = true;
                this.sendingmagicemail = false;
            });
        },
        save: function() {
            this.saving = true;
            this.updateContactInfo(this.editingProfileData).then((result) => {
                if (result !== true) {
                    this.saveError = result;
                } else {
                    this.saved = true;
                }
                this.saving = false;
            })
        },
        create: function() {
            this.saving = true;
            this.createAccount(this.editingProfileData)
                .then((result) => {
                    if (result !== true) {
                        this.createError = result;
                    } else {
                        this.created = true;
                    }
                    this.saving = false;
                }).catch((error) => {
                    this.createError = error.error.message;
                    this.saving = false;
                    console.log(error)

                })
        },
        closeerror: function() {
            this.createError = "";
            this.sentmagicmail = false;
        }

    },
    beforeMount() {
        //Prepopulate the form
        this.editingProfileData = this.getContactInfo;
    }
};
</script>
