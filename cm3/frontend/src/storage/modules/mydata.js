import shop from '../../api/shop'

// initial state
const state = {
  token:"",
  permissions:null,
  adminMode:false,
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
    getAuthToken: (state) => {
            return state.token;
    },
    getIsLoggedIn: (state) => {
        return state.token != "" && state.contactInfo != undefined && state.contactInfo.id != undefined;
    },
    getAdminMode: (state) => {
        return state.token != "" && state.adminMode;
    },
    hasPerms: (state) => {
        return state.permissions != null;
    },
    hasEventPerm: (state) => (permName) => {
        if(state.permissions == null || state.permissions == undefined) return false;
        return state.permissions.EventPerms.findIndex((perm)=> perm == permName || perm == 'GlobalAdmin') > -1;
    },
    hasGroupPerm: (state,getters) => (groupId, permName) => {
        if(state.permissions == null || state.permissions == undefined) return false;
        //Check if they're a GlobalAdmin
        if(getters.hasEventPerm("GlobalAdmin")) return true;
        if(state.permissions.GrouPerms[groupId] == undefined) return false;
        return state.permissions.GrouPerms[groupId].findIndex((perm)=> perm == permName) > -1;
    },
    getContactInfo: (state) => {
            return state.contactInfo;
    },
    getLoggedInName: (state) => {
        if(state.contactInfo != undefined)
        return state.contactInfo.real_name || state.contactInfo.email_address;
        else
        return "Guest";
    }
}

// actions
const actions = {
    createAccount({dispatch, commit}, accountInfo){
        return new Promise((resolve, reject)=>{
            shop.createAccount(accountInfo,
            (token) => {
                resolve(dispatch('loginToken', token));
            },(error) => {
                reject(error);
            });

        })
    },
    loginToken({dispatch,commit,state}, token){
        return new Promise((resolve) => {
            shop.switchEvent(token,state.event_id,(data) => {
                commit('setToken',data.token);
                commit('setPermissions',data.permissions);
                dispatch('products/selectEventId', data.event_id, { root: true });
                dispatch('refreshContactInfo');
                resolve(true);
            },
            (error)=>resolve(error || "Failed, maybe the link expired?")
        );

        })
    },
    loginPassword({dispatch,commit,state}, credentials){
        return new Promise((resolve) => {
            shop.loginAccount(credentials, (data) => {
                commit('setToken',data.token);
                commit('setPermissions',data.permissions);
                commit('setAdminMode', data.permissions != undefined)
                dispatch('products/selectEventId', data.event_id, { root: true });
                dispatch('refreshContactInfo');
                resolve(true);
            }, (error) => {
                resolve(error.error.message);
            });
        })
    },
    logout({commit}){
            commit('setToken',"");
            commit('setPermissions',null);
            commit('setContactInfo',null);
            commit('setAdminMode',false);
    },
    setAdminMode({commit}, newAdminMode) {
        commit('setAdminMode', newAdminMode);
    },
    refreshContactInfo({commit,state}) {
            return new Promise((resolve) => {
                shop.getContactInfo(state.token, (data) =>{
                    commit('setContactInfo',data);
                })
            })
    },
    updateContactInfo({commit,state}, newData) {
        return new Promise((resolve) =>{
            shop.setContactInfo(state.token,newData,(data)=>{
                commit('setContactInfo',data);
                resolve(true);
            })
        }, (error) => {
            resolve(error.error.message);
        });
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
    setAdminMode(state, newAdminMode) {
        state.adminMode = newAdminMode;
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
