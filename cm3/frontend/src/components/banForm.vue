<template>
<v-form ref="fBadgeType"
        v-model="validbadgeTypeInfo">
    <v-container fluid>
        <v-row>
            <v-col cols="6"
                   sm="8"
                   md="6">
                <v-text-field label="Real Name"
                              v-model="model.real_name"
                              counter="255"
                              :rules="RulesRequired">
                </v-text-field>
            </v-col>
            <v-col cols="6"
                   sm="8"
                   md="6">
                <v-text-field label="Fandom Name"
                              v-model="model.fandom_name"
                              counter="255">
                </v-text-field>
            </v-col>
            <v-col cols="6"
                   sm="8"
                   md="6">
                <v-text-field label="Email address"
                              v-model="model.email_address"
                              counter="255">
                </v-text-field>
            </v-col>
            <v-col cols="6"
                   sm="8"
                   md="6">
                <v-text-field label="Phone Number"
                              v-model="model.phone_number"
                              counter="255">
                </v-text-field>
            </v-col>
        </v-row>
        <v-row>
            <v-col>
                <v-textarea label="Notes"
                            v-model="model.notes" />
            </v-col>
            <v-col cols="6"
                   sm="8"
                   md="6">
                <v-textarea label="Context"
                              v-model="model.context" />
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="6"
                   sm="8"
                   md="6">
                <v-text-field label="Added by"
                              v-model="model.added_by"
                              counter="255">
                </v-text-field>
            </v-col>
            <v-col cols="12"
                   md="6">
                <v-menu ref="menuEndDate"
                        v-model="menuEndDate"
                        :close-on-content-click="false"
                        transition="scale-transition"
                        offset-y
                        min-width="290px">
                    <template v-slot:activator="{ on }">
                        <v-text-field v-model="model.date_expired"
                                      type="date"
                                      clearable
                                      label="End of ban after"
                                      v-on="on"></v-text-field>
                    </template>
                    <!--TODO: Set this based on event end! :max="new Date().toISOString().substr(0, 10)"saveEndDate -->
                    <v-date-picker ref="pickerEndDate"
                                   v-model="model.date_expired"
                                   min="2000-01-01"
                                   @change="saveEndDate"></v-date-picker>
                </v-menu>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <v-text-field readonly
                              label="Created"
                              v-model="model.date_created" />
            </v-col>
            <v-col>
                <v-text-field readonly
                              label="Updated"
                              v-model="model.date_modified" />
            </v-col>
        </v-row>

    </v-container>
</v-form>
</template>

<script>
import {
    mapGetters
} from 'vuex'

function nullIfEmptyOrZero(inValue) {
    if (inValue == 0 || inValue == '' || inValue == null) return null;
    return inValue;
}

function ZeroIfEmpty(inValue) {
    if (inValue == 0 || inValue == '' || inValue == null) return 0;
    return inValue;
}
export default {
    components: {},
    props: {
        'value': {
            type: Object
        },
    },
    data() {
        return {

            skipEmitOnce: false,
            validbadgeTypeInfo: false,
            model: {
                id: this.value?.id || null,
                real_name: this.value?.real_name || "",
                fandom_name: this.value?.fandom_name || "",
                email_address: this.value?.email_address || "",
                phone_number: this.value?.phone_number || "",
                added_by: this.value?.added_by || "",
                context: this.value?.context || "",
                date_created: this.value?.date_created || "",
                date_modified: this.value?.date_modified || "",
                date_expired: this.value?.date_expired || "",
                notes: this.value?.notes || ""
            },
            menuStartDate: false,
            menuEndDate: false,

            RulesRequired: [
                (v) => !!v || 'Required',
            ],
        };
    },
    computed: {

        ...mapGetters('mydata', {
            'isLoggedIn': 'getIsLoggedIn',
        }),
        result() {
            if (this.model == undefined) return undefined;
            var result = {
                id: this.model.id || null,
                real_name: this.model.real_name || "",
                fandom_name: this.model.fandom_name || "",
                email_address: this.model.email_address || "",
                phone_number: this.model.phone_number || "",
                added_by: this.model.added_by || "",
                context: this.model.context || "",
                date_created: this.model.date_created || "",
                date_modified: this.model.date_modified || "",
                date_expired: this.model.date_expired || "",
                notes: this.model.notes || "",
            };
            return result;
        },
    },
    methods: {

        saveStartDate(date) {
            this.$refs.menuStartDate.save(date);
            this.model.start_date = this.model.start_date;
        },
        saveEndDate(date) {
            this.$refs.menuEndDate.save(date);
            this.model.end_date = this.model.end_date;
        },
    },
    watch: {
        result(newData) {
            if (this.skipEmitOnce == true) {
                this.skipEmitOnce = false;
                return;
            }
            this.$emit('input', newData);
        },
        value(newValue) {
            //Splat the input into the form
            this.skipEmitOnce = true;
            this.model = {

                id: newValue?.id || null,
                real_name: newValue.real_name || "",
                fandom_name: newValue.fandom_name || "",
                email_address: newValue.email_address || "",
                phone_number: newValue.phone_number || "",
                added_by: newValue.added_by || "",
                context: newValue.context || "",
                date_created: newValue.date_created || "",
                date_modified: newValue.date_modified || "",
                date_expired: newValue.date_expired || "",
                notes: newValue.notes || "",
            };
        }
    },
};
</script>
