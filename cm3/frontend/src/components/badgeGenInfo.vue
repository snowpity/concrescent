<template>
<v-form ref="fGenInfo"
        v-model="validGenInfo">
    <v-row>
        <v-col cols="12"
               md="6">
            <v-text-field v-model="model.real_name"
                          :counter="500"
                          :rules="RulesName"
                          label="Real Name"
                          required></v-text-field>
        </v-col>

        <v-col cols="12"
               md="6">
            <v-text-field v-model="model.fandom_name"
                          :counter="255"
                          :rules="RulesNameFandom"
                          label="Fandom Name (Optional)"></v-text-field>
        </v-col>
        <v-col cols="12"
               md="6">
            <v-select v-show="model.fandom_name"
                      v-model="model.name_on_badge"
                      :rules="RulesNameDisplay"
                      :items="name_on_badgeOptions"
                      label="Display on badge"></v-select>
        </v-col>
        <v-col cols="12"
               md="6">
            <v-menu ref="menuBDay"
                    v-model="menuBDay"
                    :close-on-content-click="false"
                    transition="scale-transition"
                    offset-y
                    min-width="290px">
                <template v-slot:activator="{ on }">
                    <v-text-field v-model="model.date_of_birth"
                                  type="date"
                                  label="Date of Birth"
                                  v-on="on"
                                  :rules="RulesRequired"></v-text-field>
                </template>
                <v-date-picker ref="pickerBDay"
                               v-model="model.date_of_birth"
                               :max="new Date().toISOString().substr(0, 10)"
                               min="1920-01-01"
                               @change="saveBDay"
                               :active-picker.sync="bdayActivePicker"></v-date-picker>
            </v-menu>
        </v-col>
    </v-row>
</v-form>
</template>

<script>
export default {
    components: {},
    props: ['value', 'readonly'],
    data() {
        return {

            skipEmitOnce: false,
            validGenInfo: false,
            model: this.value || {
                real_name: "",
                fandom_name: "",
                name_on_badge: "Real Name Only",
                date_of_birth: "",
            },
            name_on_badgeOptions: ['Fandom Name Large, Real Name Small', 'Real Name Large, Fandom Name Small', 'Real Name Only', 'Fandom Name Only'],
            menuBDay: false,
            bdayActivePicker: 'YEAR',

            RulesRequired: [
                (v) => !!v || 'Required',
            ],
            RulesName: [
                (v) => !!v || 'Name is required',
                (v) => (v && v.length <= 500) || 'Name must be less than 500 characters',
            ],
            RulesNameFandom: [

                (v) => (v == '' || (v && v.length <= 255)) || 'Name must be less than 255 characters',
            ],
            RulesNameDisplay: [
                (v) => ((this.model.fandom_name.length < 1) || (this.model.fandom_name.length > 0 && v != '')) || 'Please select a display type',
            ],
        };
    },
    computed: {

        result() {
            return {
                real_name: this.model.real_name,
                fandom_name: this.model.fandom_name,
                name_on_badge: this.model.name_on_badge,
                date_of_birth: this.model.date_of_birth,
            }
        },
    },
    methods: {

        saveBDay(date) {
            this.$refs.menuBDay.save(date);
            this.model.date_of_birth = this.model.date_of_birth;
        },
    },
    watch: {
        validGenInfo(isValid) {
            this.$emit('valid', isValid);
        },
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
                ...newValue
            };
        },
        menuBDay(val) {
            // Whenever opening the picker, always reset it back to start with the Year
            val && setTimeout(() => (this.bdayActivePicker = 'YEAR'));
        },
    },
};
</script>
