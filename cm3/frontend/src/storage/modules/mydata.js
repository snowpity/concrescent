import shop from '../../api/shop'

// initial state
const state = {
  ownedbadges: {},
  BadgeRetrievalStatus: false,
  BadgeRetrievalResult: ''
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
}

// actions
const actions = {
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
        commit('setBadgeRetrievalResult', "Error retrieving badges.");
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
