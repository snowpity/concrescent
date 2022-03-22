import shop from '../../api/shop'

// initial state
const state = {
  token:"",
  permissions:null,
  ownedbadges: {},
  BadgeRetrievalStatus: false,
  BadgeRetrievalResult: '',
  contactInfo: null,
}


// getters
const getters = {
  ownedBadgeCount: (state) => {
    return Object.keys(state.ownedbadges).length || 0;
  },

  getBadgeAsCart: (state) =>
    (badgeId) => {
      var tempProduct = [
        state.ownedbadges[badgeId]
      ]
      var result = shop.transformPOSTData(tempProduct, true)[0];

      if (result.addonsSelected != undefined && result.addonsSelected.length > 0)
        result.editBadgePriorAddons = [...result.addonsSelected];
      result.editBadgeId = result.id;
      return result;
    },
    getIsLoggedIn: (state) => {
        return state.token != "" && state.contactInfo.id != undefined;
    },
    getHasPerms: (state) => {
        return state.permissions != null;
    }
}

// actions
const actions = {
    createAccount({dispatch, commit}, accountInfo){
        return new Promise((resolve, reject)=>{
            shop.createAccount(accountInfo)
            .then((token) => {
                resolve(dispatch('loginToken', token));
            })
            .catch(reject);
        })
    },
    loginToken({dispatch,commit,state}, token){
        return new Promise((resolve) => {
            shop.switchEvent(token,state.event_id,(data) => {
                commit('setToken',data.token);
                commit('setPermissions',data.permissions);
                dispatch('products/selectEventId', data.event_id, { root: true });
                dispatch('refreshContactInfo');
            });

        })
    },
    loginPassword({dispatch,commit,state}, username, password){
        return new Promise((resolve) => {
            shop.loginAccount({username, password}, state.selectedEventId,(data) => {
                commit('setToken',data.token);
                dispatch('products/selectEventId', data.event_id, { root: true });
                dispatch('refreshContactInfo');
            });
        })
    },
    refreshContactInfo({commit,state}) {
            return new Promise((resolve) => {
                shop.getLatestContactInfo(state.token, (data) =>{
                    commit('setContactInfo',data);
                })
            })
    },
  clearCart({
    commit
  }) {

    commit('setCheckoutStatus', null)
    commit('setCartItems', {
      items: []
    })
  },

  removeProductFromCart({
    state,
    commit
  }, product) {

    const cartItem = state.items.find(item => item.cartId === product.cartId && item.cartId != null);
    if (cartItem) {
      commit('removeProductFromCart', cartItem);
    }
  },
  sendRetrieveBadgeEmail({
    commit
  }, email) {
    commit('setBadgeRetrievalStatus', true)
    shop.sentEmailRetrieveBadges(email, () => {
        commit('setBadgeRetrievalStatus', false);

      },
      function(data) {
        //Error?
        commit('setBadgeRetrievalResult', "Error requesting badge retrieval email. " + data);
            //console.log(data);
      });
  },
  retrieveBadges({
    commit,
    state
  }, submitted) {

    shop.getMyBadges(
      submitted.gid, submitted.tid,
      (data) => {
        var newdata = {};
        //First get the existing ones
        Object.keys(state.ownedbadges).forEach(item => newdata[state.ownedbadges[item]['id-string']] = state.ownedbadges[item]);
        data.forEach(item => newdata[item['id-string']] = item);

        commit('setOwnedBadges', {
          items: newdata
        });
        commit('setBadgeRetrievalResult', "Retrieved " + data.length + " badges.");
      },
      function(data) {
        //Error?
        commit('setBadgeRetrievalResult', "Error retrieving badges." + data);
            //console.log(data);
      }
    )
  },
  clearBadgeRetrievalResult({
    commit
  }) {
    commit('setBadgeRetrievalResult', "");
  }
}

// mutations
const mutations = {
  initialiseData(state) {
    // Check if the ID exists
    if (localStorage.getItem('mydata')) {
      // Replace the state object with the stored item
      //this.replaceState(
      Object.assign(state, JSON.parse(localStorage.getItem('mydata')))
      //);
    }
  },

    setToken(state,newtoken){
      state.token = newtoken;
    },
    setPermissions(state,newPermissions){
      state.permissions = newPermissions;
    },
    setContactInfo(state,newContactInfo){
        state.contactInfo = newContactInfo;
    },

  setOwnedBadges(state, {
    items
  }) {
    state.ownedbadges = items
  },
  setBadgeRetrievalStatus(state, newState) {
    state.BadgeRetrievalStatus = newState;
  },
  setBadgeRetrievalResult(state, newState) {
    state.BadgeRetrievalResult = newState;
  },

}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
