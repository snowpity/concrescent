<template>
<v-container>
    <v-card>
        <editAdminUser v-model="userData"
                       readonly_perms />
    </v-card>

    <v-snackbar v-model="saved"
                color="primary"
                :timeout="4000">
        Settings saved!
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
               :disabled="saving"
               :loading="saving"
               @click="save">Save
        </v-btn>

    </v-footer>



</v-container>
</template>
<script>
import {
    mapGetters,
    mapActions
} from 'vuex'
import editAdminUser from '@/components/editAdminUser.vue';

export default {
    components: {
        editAdminUser,
    },
    data: () => ({
        userData: {},
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
            'getUsername': 'getUsername',
            'getPerms': 'getPerms',
            'getPreferences': 'getPreferences',
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
            'updateSettings',
        ]),
        save: function() {
            this.saving = true;
            this.updateSettings(this.userData).then((result) => {
                if (result !== true) {
                    this.saveError = result;
                } else {
                    this.saved = true;
                }
                this.saving = false;
            }).catch((error) => {
                this.saving = false;
            })
        },
        closeerror: function() {
            this.createError = "";
            this.sentmagicmail = false;
        }

    },
    beforeMount() {
        //Prepopulate the form
        this.userData = JSON.parse(JSON.stringify({
            contact_id: -1,
            username: this.getUsername || "",
            password: undefined,
            active: true,
            adminOnly: false,
            preferences: this.getPreferences,
            permissions: this.getPerms,
        }))
    }
};
</script>
