<template>
<v-data-table :headers="headers"
              :items="model"
              hide-default-footer
              :itemsPerPage="-1"
              class="elevation-1">
    <template v-slot:top>
        <v-toolbar flat>
            <v-toolbar-title>Included: {{base_applicant_count}}, Max: {{max_applicant_count}}</v-toolbar-title>
            <v-divider class="mx-4"
                       inset
                       vertical></v-divider>
            <v-spacer></v-spacer>
            <v-dialog v-model="dialog"
                      scrollable
                      max-width="1500px">
                <template v-slot:activator="{ on, attrs }">
                    <v-btn color="primary"
                           dark
                           class="mb-2"
                           v-bind="attrs"
                           v-on="on">
                        Add Badge
                    </v-btn>
                </template>
                <v-card>
                    <v-card-title>
                        <span class="text-h5">{{ formTitle }}</span>
                    </v-card-title>

                    <v-card-text>
                        <v-container>
                            <badgeGenInfo v-model="editedItem" />

                            <h3>Notify email</h3>
                            <v-row>
                                <v-col cols="12"
                                       sm="6"
                                       md="6">
                                    <v-text-field label="Email address to send badge to"
                                                  v-model="editedItem.notify_email"
                                                  :rules="RulesEmail"></v-text-field>
                                </v-col>
                                <v-col cols="12"
                                       sm="6"
                                       md="6">
                                    <v-checkbox dense
                                                hide-details
                                                v-model="editedItem.can_transfer">
                                        <template v-slot:label>
                                            <small>Allow badge transfer to the owner of this email.</small>
                                        </template>
                                    </v-checkbox>
                                </v-col>
                            </v-row>

                            <h3>In case of Emergency</h3>
                            <v-row>

                                <v-col cols="12"
                                       sm="6"
                                       md="3">
                                    <v-text-field label="Emergency Contact Name"
                                                  v-model="editedItem.ice_name"></v-text-field>
                                </v-col>
                                <v-col cols="12"
                                       sm="6"
                                       md="3">
                                    <v-text-field label="Relationship"
                                                  v-model="editedItem.ice_relationship"></v-text-field>
                                </v-col>
                                <v-col cols="12"
                                       sm="6"
                                       md="3">
                                    <v-text-field label="Email address"
                                                  v-model="editedItem.ice_email_address"
                                                  :rules="RulesEmail"></v-text-field>
                                </v-col>
                                <v-col cols="12"
                                       sm="6"
                                       md="3">
                                    <v-text-field label="Phone Number"
                                                  v-model="editedItem.ice_phone_number"
                                                  :rules="RulesPhone"></v-text-field>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>

                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="blue darken-1"
                               text
                               @click="close">
                            Cancel
                        </v-btn>
                        <v-btn color="blue darken-1"
                               text
                               @click="save">
                            Save
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
            <v-dialog v-model="dialogDelete"
                      max-width="500px">
                <v-card>
                    <v-card-title class="text-h5">Are you sure you want to delete this item?</v-card-title>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="blue darken-1"
                               text
                               @click="closeDelete">Cancel</v-btn>
                        <v-btn color="blue darken-1"
                               text
                               @click="deleteItemConfirm">OK</v-btn>
                        <v-spacer></v-spacer>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-toolbar>
    </template>
    <template v-slot:[`item.actions`]="{ item }">
        <v-icon class="mr-2"
                @click="editItem(item)">
            mdi-pencil
        </v-icon>
        <v-icon @click="deleteItem(item)">
            mdi-delete
        </v-icon>
    </template>
    <template v-slot:no-data>
        No badges, please add some!
    </template>
</v-data-table>
</template>

<script>
import badgeGenInfo from '@/components/badgeGenInfo.vue';
export default {

    components: {
        badgeGenInfo,
    },
    props: {
        'value': {
            type: Array,
            default: () => [],
        },
        'base_applicant_count': {
            type: Number,
            default: 0,
        },
        'max_applicant_count': {
            type: Number,
            default: 0,
        },
        'readonly': {
            type: Boolean
        }
    },
    data: () => ({

        dialog: false,
        dialogDelete: false,
        headers: [{
                text: 'ID',
                align: 'start',
                sortable: false,
                value: 'display_id',
            },
            {
                text: 'Name',
                value: 'real_name'
            },
            {
                text: 'Fandom Name',
                value: 'fandom_name'
            },
            {
                text: 'Displayed as',
                value: 'name_on_badge'
            },
            {
                text: 'Actions',
                value: 'actions',
                sortable: false
            },
        ],
        model: [],
        editedIndex: -1,
        editedItem: {},
        defaultItem: {
            real_name: '',
            fandom_name: '',
            name_on_badge: 'Real Name Only',
            date_of_birth: '',
            notify_email: '',
            can_transfer: 1,
            ice_name: '',
            ice_relationship: '',
            ice_email_address: '',
            ice_phone_number: '',
        },
        RulesEmail: [
            (v) => !v || /.+@.+\..+/.test(v) || 'E-mail must be valid',
        ],
        RulesPhone: [
            (v) => !v || v.length > 6 || 'Phone number too short',
            /* v =>  !v || /^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/.test(v) || 'Phone number should be valid' */
        ],
    }),
    computed: {

        formTitle() {
            return this.editedIndex === -1 ? 'New Item' : 'Edit Item'
        },
    },
    methods: {

        editItem(item) {
            this.editedIndex = this.model.indexOf(item)
            this.editedItem = Object.assign({}, item)
            this.dialog = true
        },

        deleteItem(item) {
            this.editedIndex = this.model.indexOf(item)
            this.editedItem = Object.assign({}, item)
            this.dialogDelete = true
        },

        deleteItemConfirm() {
            this.model.splice(this.editedIndex, 1)
            this.closeDelete()
        },

        close() {
            this.dialog = false
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem)
                this.editedIndex = -1
            })
        },

        closeDelete() {
            this.dialogDelete = false
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem)
                this.editedIndex = -1
            })
        },

        save() {
            if (this.editedIndex > -1) {
                Object.assign(this.model[this.editedIndex], this.editedItem)
            } else {
                this.model.push(this.editedItem)
            }
            this.close()
        },
    },
    watch: {
        model(newData) {
            this.$emit('input', newData);
        },
        value(newValue) {
            //Splat the input into the form
            this.model = newValue;
        },
        dialog(val) {
            val || this.close()
        },
        dialogDelete(val) {
            val || this.closeDelete()
        },
    },
    created() {
        this.model = this.value;
    }
};
</script>
