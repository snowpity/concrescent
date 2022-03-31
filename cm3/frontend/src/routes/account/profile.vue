<template>
<v-container>
    <v-card>
        <profileForm v-model="editingProfileData" />
    </v-card>
    Things: {{JSON.stringify(editingProfileData)}}

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
import profileForm from '@/components/profileForm.vue';

export default {
    components: {
        profileForm,
    },
    data: () => ({
        editingProfileData: {},
        saving: false,
        saveError: ""
    }),
    computed: {

        ...mapGetters('mydata', {
            'getContactInfo': 'getContactInfo',
            'isLoggedIn': 'getIsLoggedIn',
        }),
    },
    methods: {
        ...mapActions('mydata', [
            'updateContactInfo',
        ]),
        save: function() {
            this.saving = true;
            this.updateContactInfo(this.editingProfileData).then((result) => {
                if (result !== true) {
                    this.saveError = result;
                }
                this.saving = false;
            })
        }
    },
    beforeMount() {
        //Prepopulate the form
        this.editingProfileData = this.getContactInfo;
    }
};
</script>
