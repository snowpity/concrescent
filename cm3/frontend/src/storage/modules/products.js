import shop from '../../api/shop'

// initial state
const state = {
  eventinfo:[],
  selectedEventId:0,
  selectedEvent:null,
  badgecontexts:[],
  badgecontextselectedix:0,
  badgecontextselected:null,
  all: [],
  questions: [],
  addons: [],
  gotEventInfo:false,
  gotBadgeContexts:false,
  gotAll: false,
  gotQuestions: false,
  gotAddons: false
}

// getters
const getters = {

      events: (state) => {
        return state.eventinfo|| [];
      },
      selectedEventId: (state) => {
        return state.selectedEventId|| 0;
      },
      selectedEvent: (state) => {
        return state.selectedEvent|| {
            "id": 0,
            "shortcode": "",
            "active": 0,
            "display_name": "Loading...",
            "date_start": "",
            "date_end": ""
        };
      },
      badgecontexts: (state) => {
        return state.badgecontexts|| [];
      },
      selectedbadgecontext: (state) => {
        return state.badgecontextselected|| {
            "context_code": "",
            "name": "Loading..."
        };
      },
}

// actions
const actions = {
    selectEventId({commit},event_id) {
        return new Promise((resolve) => {
            commit('selectEvent', event_id);
            resolve();
        })
    },
    getEventInfo({commit,state}){

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
    getBadgeContexts({commit,state}){

          return new Promise((resolve) => {
            //Load only if necessary
            if (!state.gotBadgeContexts) {
              shop.getProductContexts(state.selectedEventId, contexts => {
                  commit('setBadgeContexts', contexts);
                  commit('setBadgeContextSelected', contexts[0].context_code);
                resolve();
              })
            } else {
              resolve();
            }
          })
    },
  getAllProducts({
      dispatch,
    commit,
    state
  }) {
    return new Promise((resolve) => {
    //Prerequisite: We need a context
    return dispatch('getBadgeContexts').then(() =>{
          //Load only if necessary
          if (!state.gotAll) {
            shop.getProducts(state.selectedEventId,
                state.badgecontextselected.context_code,
                products => {
              commit('setProducts', products);
              resolve();
            })
          } else {
            resolve();
          }
      })
  })
  },
  getAllQuestions({
      dispatch,
    commit,
    state
  }) {
    return new Promise((resolve) => {
      //Load only if necessary
      if (!state.gotQuestions) {
            return dispatch('getBadgeContexts').then(() =>{
                shop.getQuestions(state.selectedEventId,
                    state.badgecontextselected.context_code,
                    questions => {
                  commit('setQuestions', questions)
              });
          })
      } else {
        resolve();
      }
    })
  },
  getAllAddons({
      dispatch,
    commit,
    state
  }) {
    return new Promise((resolve) => {
      //Load only if necessary
      if (!state.gotAddons) {
          return dispatch('getBadgeContexts').then(() =>{
            shop.getAddons(state.selectedEventId,
                state.badgecontextselected.context_code,
                addons => {
                  commit('setAddons', addons)
                })
          })
      }
      else {
        resolve();
      }
    })
  },
}

// mutations
const mutations = {
  setEventInfo(state, eventinfo) {
    state.eventinfo = eventinfo;
    state.gotEventInfo = true;
    //Ask for a reset of the rest of the shop
    state.gotBadgeContexts= false,
    state.gotAll= false,
    state.gotQuestions= false,
    state.gotAddons= false
  },
  selectEvent(state, eventid) {
      state.selectedEventId = eventid;
      state.selectedEvent = state.eventinfo.find(x => x.id == eventid);
      //Ask for a reset of the rest of the shop
      state.gotBadgeContexts= false,
      state.gotAll= false,
      state.gotQuestions= false,
      state.gotAddons= false
  },
  setBadgeContexts(state, contexts) {
    state.badgecontexts = contexts;
    state.gotBadgeContexts = true;
    state.gotAll= false,
    state.gotQuestions= false,
    state.gotAddons= false
  },
  setBadgeContextSelected(state, context) {
    state.badgecontextselected = state.badgecontexts.find(x => x.context_code == context);
    state.gotAll= false,
    state.gotQuestions= false,
    state.gotAddons= false
  },
  setProducts(state, products) {
    state.all = products;
    state.gotAll = true;
  },
  setQuestions(state, questions) {
    state.questions = questions;
    state.gotQuestions = true;
  },
  setAddons(state, addons) {
    state.addons = addons;
    state.gotAddons = true;
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
