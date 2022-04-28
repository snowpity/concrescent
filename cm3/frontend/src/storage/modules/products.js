import shop from '../../api/shop'
import Vue from 'vue'

// initial state
const state = {
    eventinfo: [],
    selectedEventId: null,
    selectedEvent: null,
    badgecontexts: [],
    badgecontextselectedix: 0,
    badgecontextselected: null,
    badges: {},
    questions: {},
    addons: {},
    gotEventInfo: false,
    gotBadgeContexts: false,
    gotBadges: {},
    gotQuestions: {},
    gotAddons: {}
}

// getters
const getters = {

    events: (state) => {
        return state.eventinfo || [];
    },
    selectedEventId: (state) => {
        return state.selectedEventId || 0;
    },
    selectedEvent: (state) => {
        return state.selectedEvent || {
            "id": 0,
            "shortcode": "",
            "active": 0,
            "display_name": "Loading...",
            "date_start": "",
            "date_end": ""
        };
    },
    badgeContexts: (state) => {
        return state.badgecontexts || [];
    },
    selectedbadgecontext: (state) => {
        return state.badgecontextselected || {
            "context_code": "",
            "name": "Loading..."
        };
    },
    contextBadges: (state) => {
        if (state.badgecontextselected == undefined) return [];
        return state.badges[state.badgecontextselected.context_code] || [];
    },
    contextQuestions: (state) => {
        if (state.badgecontextselected == undefined) return [];
        return state.questions[state.badgecontextselected.context_code] || [];
    },
    contextAddons: (state) => {
        if (state.badgecontextselected == undefined) return [];
        return state.addons[state.badgecontextselected.context_code] || [];
    },
}

// actions
const actions = {
    selectEventId({
        commit
    }, event_id) {
        return new Promise((resolve) => {
            commit('selectEvent', event_id);
            resolve();
        })
    },
    getEventInfo({
        commit,
        state
    }) {

        return new Promise((resolve) => {
            //Load only if necessary
            if (!state.gotEventInfo) {
                shop.getEventInfo(eventinfo => {
                    commit('setEventInfo', eventinfo);
                    commit('selectEvent', eventinfo[0].id);
                    resolve();
                })
            } else {
                resolve();
            }
        })
    },
    getBadgeContexts({
        commit,
        state
    }) {

        return new Promise((resolve) => {
            //Load only if necessary
            if (!state.gotBadgeContexts) {
                shop.getBadgeContexts(state.selectedEventId, contexts => {
                    commit('setBadgeContexts', contexts);
                    commit('setBadgeContextSelected', contexts[0].context_code);
                    resolve();
                })
            } else {
                resolve();
            }
        })
    },
    selectContext({
        dispatch,
        commit,
        state
    }, context_code) {
        return new Promise(async (resolve, reject) => {
            await dispatch('getBadgeContexts');
            //Confirm we have a context to select that matches
            commit('setBadgeContextSelected', context_code);
            //Check that the desired context exists
            if (state.badgecontextselected == undefined)
                return reject('Context Code not found');
            //Fetch all the things!
            await dispatch('getContextBadges');
            await dispatch('getContextQuestions');
            await dispatch('getContextAddons');

        })
    },
    getContextBadges({
        dispatch,
        commit,
        state
    }) {
        return new Promise((resolve, reject) => {
            //Prerequisite: We need a context
            if (state.badgecontextselected == undefined)
                return reject('Context not selected!');
            //Load only if necessary
            if (state.gotBadges[state.badgecontextselected.context_code] != undefined)
                return resolve();
            shop.getBadges(state.selectedEventId,
                state.badgecontextselected.context_code,
                badges => {
                    commit('setContextBadges', {
                        badges: badges,
                        context_code: state.badgecontextselected.context_code,
                        success: true
                    });
                    resolve();
                },
                error => {
                    commit('setContextBadges', {
                        badges: [],
                        context_code: state.badgecontextselected.context_code,
                        success: false
                    });
                    resolve()
                })
        })
    },
    getContextQuestions({
        dispatch,
        commit,
        state
    }) {
        return new Promise((resolve, reject) => {
            //Prerequisite: We need a context
            if (state.badgecontextselected == undefined)
                return reject('Context not selected!');
            //Load only if necessary
            if (state.gotQuestions[state.badgecontextselected.context_code] != undefined)
                return resolve();
            shop.getQuestions(state.selectedEventId,
                state.badgecontextselected.context_code,
                badges => {
                    commit('setContextQuestions', {
                        questions: questions,
                        context_code: state.badgecontextselected.context_code,
                        success: true
                    });
                    resolve();
                },
                error => {
                    commit('setContextQuestions', {
                        questions: [],
                        context_code: state.badgecontextselected.context_code,
                        success: false
                    });
                    resolve()
                })
        })
    },
    getContextAddons({
        dispatch,
        commit,
        state
    }) {
        return new Promise((resolve, reject) => {
            //Prerequisite: We need a context
            if (state.badgecontextselected == undefined)
                return reject('Context not selected!');
            //Load only if necessary
            if (state.gotAddons[state.badgecontextselected.context_code] != undefined)
                return resolve();
            shop.getAddons(state.selectedEventId,
                state.badgecontextselected.context_code,
                badges => {
                    commit('setContextAddons', {
                        addons: addons,
                        context_code: state.badgecontextselected.context_code,
                        success: true
                    });
                    resolve();
                },
                error => {
                    commit('setContextAddons', {
                        addons: [],
                        context_code: state.badgecontextselected.context_code,
                        success: false
                    });
                    resolve()
                })
        })
    },
}

// mutations
const mutations = {
    setEventInfo(state, eventinfo) {
        state.eventinfo = eventinfo;
        state.gotEventInfo = true;
        //Ask for a reset of the rest of the shop
        state.gotBadgeContexts = false;
        state.gotBadges = {};
        state.gotQuestions = {};
        state.gotAddons = {};
    },
    selectEvent(state, eventid) {
        state.selectedEventId = eventid;
        state.selectedEvent = state.eventinfo.find(x => x.id == eventid);
        //Ask for a reset of the rest of the shop
        state.gotBadgeContexts = false;
        state.gotBadges = {};
        state.gotQuestions = {};
        state.gotAddons = {};
    },
    setBadgeContexts(state, contexts) {
        state.badgecontexts = contexts;
        state.gotBadgeContexts = true;;
        state.gotBadges = {};
        state.gotQuestions = {};
        state.gotAddons = {};
    },
    setBadgeContextSelected(state, context) {
        state.badgecontextselected = state.badgecontexts.find(x => x.context_code == context);
    },
    setContextBadges(state, data) {
        Vue.set(state.badges, data.context_code, data.badges);
        Vue.set(state.gotBadges, data.context_code, data.success);
    },
    setContextQuestions(state, questions) {
        Vue.set(state.questions, data.context_code, data.questions)
        Vue.set(state.gotQuestions, data.context_code, data.success);
    },
    setContextAddons(state, addons) {
        Vue.set(state.addons, data.context_code, data.addons)
        Vue.set(state.gotAddons, data.context_code, data.success);
    },

    decrementProductQuantity(state, {
        id
    }) {
        const product = state.all.find(product => product.id === id)
        if (product.quantity > 0) {
            product.quantity--
        }

    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}