<template>
<v-form ref="fBadgeType"
        v-model="validbadgeTypeInfo">
    <v-container fluid>
        <v-row>
            <v-col cols="6"
                   sm="8"
                   md="6">
                <v-text-field label="Name"
                              v-model="model.name"
                              counter="255"
                              :rules="RulesRequired">
                </v-text-field>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-row>
                    <v-col>
                        <v-checkbox dense
                                    hide-details
                                    v-model="model.active">
                            <template v-slot:label>
                                Active
                            </template>
                        </v-checkbox>
                    </v-col>
                    <v-col>
                        <v-text-field v-if="!model.active"
                                      label="Active Override code"
                                      v-model="model.active_override_code"
                                      append-icon="mdi-link"
                                      @click:append="copyOverrideLink" />
                    </v-col>
                </v-row>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Price"
                              v-model="model.price"
                              :rules="RulesRequired"></v-text-field>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-text-field label="Quantity Available"
                              type="number"
                              v-model="model.quantity"></v-text-field>
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3">
                <v-checkbox dense
                            hide-details
                            v-model="model.payable_onsite">
                    <template v-slot:label>
                        Payable on-site?
                    </template>
                </v-checkbox>
            </v-col>
        </v-row>
        <v-row>
            <v-col>
                Description
                <v-md-editor label="Description"
                             v-model="model.description" />
            </v-col>
            <v-col>
                Rewards
                <v-md-editor label="Rewards"
                             v-model="model.rewards" />
            </v-col>
        </v-row>
        <v-row>
            <v-col>
                <v-select label="Applies to"
                          v-model="model.valid_badge_type_ids"
                          :items="badge_types"
                          item-text="name"
                          item-value="id"
                          chips
                          multiple
                          persistent-hint
                          hint="Select which badges can have this addon added to them" />
            </v-col>
        </v-row>
        <v-row>

            <v-col cols="12"
                   md="6">
                <v-menu ref="menuStartDate"
                        v-model="menuStartDate"
                        :close-on-content-click="false"
                        transition="scale-transition"
                        offset-y
                        min-width="290px">
                    <template v-slot:activator="{ on }">
                        <v-text-field v-model="model.start_date"
                                      type="date"
                                      clearable
                                      label="Available starting"
                                      v-on="on"></v-text-field>
                    </template>
                    <!--TODO: Set this based on event end! :max="new Date().toISOString().substr(0, 10)"saveStartDate -->
                    <v-date-picker ref="pickerStartDate"
                                   v-model="model.start_date"
                                   min="2000-01-01"
                                   @change="saveStartDate"></v-date-picker>
                </v-menu>
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
                        <v-text-field v-model="model.end_date"
                                      type="date"
                                      clearable
                                      label="Unavailable after"
                                      v-on="on"></v-text-field>
                    </template>
                    <!--TODO: Set this based on event end! :max="new Date().toISOString().substr(0, 10)"saveEndDate -->
                    <v-date-picker ref="pickerEndDate"
                                   v-model="model.end_date"
                                   min="2000-01-01"
                                   @change="saveEndDate"></v-date-picker>
                </v-menu>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <v-textarea label="Notes (Not public)"
                            v-model="model.notes" />
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
        'isGroup': {
            type: Boolean
        },
        'badge_types': {
            type: Array
        }
    },
    data() {
        return {

            skipEmitOnce: false,
            validbadgeTypeInfo: false,
            model: {
                id: this.value?.id || null,
                valid_badge_type_ids: (this.value?.valid_badge_type_ids || '').split(',').map(Number) || [],
                active: this.value?.active == 1,
                display_order: this.value?.display_order || null,
                name: this.value?.name || "",
                description: this.value?.description || "",
                rewards: this.value?.rewards || "",
                price: this.value?.price || "",
                payable_onsite: this.value?.payable_onsite == 1,
                quantity: this.value?.quantity || "",
                start_date: this.value?.start_date || "",
                end_date: this.value?.end_date || "",
                min_age: this.value?.min_age || "",
                max_age: this.value?.max_age || "",
                active_override_code: this.value?.active_override_code || "",
                date_created: this.value?.date_created || "",
                date_modified: this.value?.date_modified || "",
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
            var vbs = this.model.valid_badge_type_ids || '';
            if (typeof vbs == 'object')
                vbs = vbs.join();
            if (vbs.length == 0)
                vbs = null;
            var result = {
                id: this.model.id || null,
                valid_badge_type_ids: vbs,
                active: this.model.active == 1,
                display_order: this.model.display_order || 0,
                name: this.model.name || "",
                description: this.model.description || "",
                rewards: this.model.rewards || "",
                price: this.model.price || "",
                payable_onsite: this.model.payable_onsite == 1,
                quantity: nullIfEmptyOrZero(this.model.quantity),
                start_date: nullIfEmptyOrZero(this.model.start_date),
                end_date: nullIfEmptyOrZero(this.model.end_date),
                min_age: nullIfEmptyOrZero(this.model.min_age),
                max_age: nullIfEmptyOrZero(this.model.max_age),
                active_override_code: this.model.active_override_code || "",
                date_created: this.model.date_created || "",
                date_modified: this.model.date_modified || "",
                notes: this.model.notes || "",
            };
            return result;
        },
    },
    methods: {
        copyOverrideLink() {

        },
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
                valid_badge_type_ids: (newValue?.valid_badge_type_ids || '').split(',').map(Number) || [],
                active: newValue?.active == 1,
                display_order: newValue?.display_order || null,
                name: newValue?.name || "",
                description: newValue?.description || "",
                rewards: newValue?.rewards || "",
                price: newValue?.price || "",
                payable_onsite: newValue?.payable_onsite == 1,
                quantity: newValue?.quantity || "",
                start_date: newValue?.start_date || "",
                end_date: newValue?.end_date || "",
                min_age: newValue?.min_age || "",
                max_age: newValue?.max_age || "",
                active_override_code: newValue?.active_override_code || "",
                date_created: newValue?.date_created || "",
                date_modified: newValue?.date_modified || "",
                notes: newValue?.notes || ""
            };
            this.result.quantity + 1;
        }
    },
};
</script>
