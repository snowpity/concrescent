<template>
<v-item-group active-class="light-grey"
              v-model="openQuestions"
              multiple>
    <v-container>
        <v-row>
            <v-col cols="12">
                <h1>Badge Type</h1>
                <v-select :items="contextBadgeTypes"
                          v-model="selectedBadgeType"
                          item-text="name"
                          item-value="id"
                          clearable
                          solo
                          hide-details="true"></v-select>
            </v-col>
        </v-row>
    </v-container>
    <v-row>
        <v-col>
            <v-item v-slot="{active, toggle}"
                    v-for="(item,ix) in questions"
                    :key="item.id">
                <v-card>
                    <v-card @click="toggle"
                            v-if="!active">

                        <formQuestionRender v-if="bQuestionActive(item.id)"
                                            :question="item" />
                        <p v-else>Hidden: {{item.title}} </p>
                        <v-divider></v-divider>
                    </v-card>
                    <v-card v-if="active">
                        <formQuestionEdit v-model="eQuestion(item.id).question"
                                          :preview="eQuestion(item.id).preview" />
                        <v-toolbar v-if="active"
                                   dense>
                            <v-btn icon
                                   @click="prepCancelEdit(ix)">
                                <v-icon>mdi-cancel</v-icon>
                            </v-btn>
                            <v-btn icon
                                   @click="prepDestroyQuestion(ix)">
                                <v-icon>mdi-trash</v-icon>
                            </v-btn>
                            <v-spacer />
                            <i v-if="selectedBadgeType > 0">
                                <v-btn icon
                                       @click="toggleQuestionActive(item.id)">
                                    <v-icon>mdi-eye{{bQuestionActive(item.id) ? '' : '-off'}}</v-icon>
                                </v-btn>
                                <v-btn icon
                                       :disabled="!bQuestionActive(item.id)"
                                       @click="toggleQuestionRequired(item.id)"
                                       :color="bQuestionRequired(item.id) ? 'red' : undefined ">
                                    <v-icon>mdi-asterisk</v-icon>
                                </v-btn>
                            </i>
                            <v-btn icon
                                   color="primary">
                                <v-icon>mdi-arrow-up</v-icon>
                            </v-btn>
                            <v-btn icon
                                   color="primary">
                                <v-icon>mdi-arrow-down</v-icon>
                            </v-btn>
                            <v-spacer />
                            <v-btn icon
                                   @click="eQuestion(item.id).preview = !eQuestion(item.id).preview">
                                <v-icon>mdi-magnify{{eQuestion(item.id).preview ? '-close' :''}}</v-icon>
                            </v-btn>
                            <v-btn icon
                                   :disabled="!bQuestionModified(item.id)"
                                   :loading="eQuestion(item.id).saving"
                                   @click="saveEdit(item.id)"
                                   color="primary">
                                <v-icon>mdi-content-save</v-icon>
                            </v-btn>
                        </v-toolbar>
                        <v-divider></v-divider>
                    </v-card>
                </v-card>
            </v-item>
            <v-item>
                <v-card>
                    <v-card v-if="!newQuestionShow">

                        <v-container fluid
                                     class="text-center">
                            <v-row class="flex">
                                <v-col cols="12">
                                    <v-btn @click="prepNewQuestion">
                                        Add new question
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card>
                    <v-card v-if="newQuestionShow">
                        <formQuestionEdit v-model="newQuestion"
                                          :preview="newQuestionPreview" />
                        <v-toolbar dense>
                            <v-btn icon
                                   @click="cancelNewQuestion()">
                                <v-icon>mdi-cancel</v-icon>
                            </v-btn>
                            <v-spacer />
                            <v-btn icon
                                   @click="newQuestionPreview = !newQuestionPreview">
                                <v-icon>mdi-magnify{{newQuestionPreview ? '-close' :''}}</v-icon>
                            </v-btn>
                            <v-btn icon
                                   :disabled="!newQuestionPreview"
                                   :loading="newQuestionSaving"
                                   @click="saveNewQuestion"
                                   color="primary">
                                <v-icon>mdi-content-save</v-icon>
                            </v-btn>
                        </v-toolbar>
                        <v-divider></v-divider>
                    </v-card>
                </v-card>
            </v-item>
        </v-col>

    </v-row>
    <v-dialog v-model="loading"
              width="200"
              height="200"
              close-delay="1200"
              content-class="elevation-0"
              persistent>
        <v-card-text class="text-center overflow-hidden">
            <v-progress-circular :size="150"
                                 class="mb-0"
                                 indeterminate />
        </v-card-text>
    </v-dialog>
    <v-dialog v-model="askCancelQuestionEdit"
              max-width="390">

        <v-card>
            <v-card-title class="headline">Question modified!</v-card-title>
            <v-card-text>You have unsaved changes, do you wish to save them?
            </v-card-text>
            <v-card-actions>
                <v-btn color="default"
                       @click="askCancelQuestionEdit = false">Cancel</v-btn>
                <v-spacer></v-spacer>
                <v-btn color="red darken-1"
                       @click="cancelEdit(openQuestionToCancel)">Don't save</v-btn>
                <v-btn color="primary"
                       @click="cancelEdit(openQuestionToCancel)">Save</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</v-item-group>
</template>

<script>
import admin from '../api/admin';
import {
    debounce
} from '@/plugins/debounce';
import formQuestionRender from '@/components/formQuestionRender.vue';
import formQuestionEdit from '@/components/formQuestionEdit.vue';
export default {
    components: {
        formQuestionEdit,
        formQuestionRender
    },
    props: ['context_code'],
    data: () => ({
        contextBadgeTypes: [],
        selectedBadgeType: 0,
        loading: false,
        questions: [],
        questionMap: [],
        selectedQuestions: [],
        editedQuestions: {},
        newQuestionShow: false,
        newQuestion: {},
        newQuestionPreview: false,
        newQuestionSaving: false,
        openQuestions: [],
        openQuestionToCancel: 0,
        askCancelQuestionEdit: false,
    }),
    computed: {
        authToken: function() {
            return this.$store.getters['mydata/getAuthToken'];
        },
        eQuestion: {
            get: state => function(id) {
                if (this.editedQuestions[id] == undefined) {
                    var question = this.questions.find(item => item.id == id);
                    this.$set(this.editedQuestions, id, {
                        preview: false,
                        saving: false,
                        question: JSON.parse(JSON.stringify(question))
                    });
                }
                return this.editedQuestions[id];
            }
        },
        bQuestionRequired: {
            get: state => function(id) {
                var mapdata = this.questionMap.find(item => item.question_id == id);
                if (mapdata == undefined) return false;
                return mapdata.required;
            }
        }
    },
    methods: {

        refreshBadgeTypeMap: function() {
            if (this.selectedBadgeType == 0) {
                this.questionMap = [];
                return;
            }
            this.loading = true;
            admin.genericGetList(this.authToken, 'Form/Question/' + this.context_code + '/' + this.selectedBadgeType + '/Map', null, (results, total) => {
                this.questionMap = results;
                this.loading = false;
            })
        },
        refresh: function() {
            this.loading = true;
            admin.genericGetList(this.authToken, 'Form/Question/' + this.context_code, null, (results, total) => {
                this.questions = results;

                admin.genericGetList(this.authToken, admin.contextToPrefix(this.context_code) + '/BadgeType', null, (results, total) => {
                    results.unshift({
                        "id": 0,
                        "active": 0,
                        "display_order": 0,
                        "name": "Show all questions",
                        "price": "0.00",
                        "quantity": null,
                        "dates_available": "0000-00-00 to 0000-00-00"
                    })
                    this.contextBadgeTypes = results;
                    this.loading = false;
                })
            })
        },
        doEmit: function(eventName, item) {
            this.$emit(eventName, item);
        },
        bQuestionActive: function(id) {
            if (this.selectedBadgeType == 0) return true;
            return this.questionMap.find(item => item.question_id == id) != undefined;
        },
        toggleQuestionActive: function(id) {
            if (this.bQuestionActive(id)) {
                //Active, make it not so!
                admin.genericDelete(this.authToken, 'Form/Question/' + this.context_code + '/' + this.selectedBadgeType + '/Map/' + id, (result) => {
                    this.questionMap.splice(this.questionMap.findIndex(item => item.question_id == id), 1)
                })

            } else {
                //Not active, make it so!
                admin.genericPost(this.authToken, 'Form/Question/' + this.context_code + '/' + this.selectedBadgeType + '/Map/' + id, {
                    required: false
                }, (result) => {
                    this.questionMap.push({
                        question_id: id,
                        required: false
                    })
                })
            }

        },
        toggleQuestionRequired: function(id) {
            console.log("toggle required", this.questionMap.find(item => item.question_id == id))
            if (this.bQuestionActive(id)) {
                admin.genericPost(this.authToken, 'Form/Question/' + this.context_code + '/' + this.selectedBadgeType + '/Map/' + id, {
                    required: this.questionMap.find(item => item.question_id == id).required == 0 ? 1 : 0
                }, (result) => {
                    this.$set(this.questionMap, this.questionMap.findIndex(item => item.question_id == id), {
                        question_id: id,
                        required: this.questionMap.find(item => item.question_id == id).required == 0 ? 1 : 0
                    });
                })
            } else {
                //Not active, they can't be required in any case
            }

        },
        bQuestionModified: function(id) {
            var orig = JSON.stringify(this.questions.find(item => item.id == id));
            var edit = JSON.stringify(this.editedQuestions[id].question);
            return orig != edit;
        },
        saveEdit: function(id) {
            this.editedQuestions[id].saving = true;
            admin.genericPost(this.authToken, 'Form/Question/' + this.context_code + '/' + id,
                this.editedQuestions[id].question, (results, total) => {
                    this.$set(this.questions, this.questions.findIndex(item => item.id == id), this.editedQuestions[id].question);
                    this.editedQuestions[id].saving = false;

                    //un-activate the question
                    var ix = this.questions.findIndex(item => item.id == id);
                    this.openQuestions.splice(this.openQuestions.findIndex(item => item == ix), 1);
                })

        },
        prepNewQuestion: function() {
            this.newQuestion = {
                text: '',
                title: '',
                type: 'h1',
                values: [],
            };
            this.newQuestionShow = true;
        },
        cancelNewQuestion: function() {
            this.newQuestion = {};
            this.newQuestionShow = false;
        },
        saveNewQuestion: function() {

            this.newQuestionSaving = true;
            admin.genericPost(this.authToken, 'Form/Question/' + this.context_code,
                this.newQuestion, (results, total) => {
                    //Add the ID that we got to the question
                    this.newQuestion = {
                        ...this.newQuestion,
                        ...results
                    };
                    this.questions.push(this.newQuestion);
                    this.newQuestionSaving = false;
                    //reset
                    this.cancelNewQuestion();
                    //If we're viewing a badge, immediately toggle the active state
                    if (this.selectedBadgeType > 0) {
                        this.toggleQuestionActive(results.id);
                    }

                })
        },
        prepCancelEdit: function(ix) {
            if (this.bQuestionModified(this.questions[ix].id)) {
                //Modified, pop the dialog
                this.openQuestionToCancel = ix;
                this.askCancelQuestionEdit = true;
            } else {
                //Cancel it outright
                this.cancelEdit(ix);
            }
        },
        cancelEdit: function(ix) {
            //reset the editedQuestions entry
            //Note that this gets immediately recreated...?
            this.$delete(this.editedQuestions, this.questions[ix].id);

            //un-activate the question
            this.openQuestions.splice(this.openQuestions.findIndex(item => item == ix), 1);
            this.askCancelQuestionEdit = false;

        },
        prepDestroyQuestion: function(ix) {
            //Begin the question destruction process
        }
    },
    watch: {

        context_code: debounce(function(newSearch) {
            this.refresh();
        }, 500),
        selectedBadgeType: debounce(function(newSearch) {
            this.refreshBadgeTypeMap();
        }, 500),
    },
    created() {
        this.refresh();
        //this.doSearch();
    }
};
</script>
