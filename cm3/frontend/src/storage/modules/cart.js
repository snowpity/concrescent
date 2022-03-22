import Vue from 'vue';
import shop from '../../api/shop';

// initial state
const state = {
  currentlyEditingItem: {},
  latestContactInfo: {},
  items: [],
  checkoutStatus: null,
};

function calcPromoPrice(basePrice, promoData) {
  if (promoData == null || typeof promoData.promoType === 'undefined') { return basePrice; }
  switch (promoData.promoType) {
    case 0:
      return Math.max(0, basePrice - promoData.promoPrice);
    case 1:
      return Math.max(0, basePrice * (100 - promoData.promoPrice) / 100);
  }
}

// getters
const getters = {
  cartProducts: (state, getters, rootState) => state.items.map((badge) => {
    const product = rootState.products.all.find((product) => product.id == badge.badge_type_id);
    // if product not found, and we don't have any, assume loading
    if (product == undefined && rootState.products.all.length == 0) {
      return {
        title: 'Loading...',
        price: 'Loading...',
        ...badge,
      };
    }
    const basePrice = (typeof product !== 'undefined' ? product.price : Infinity);

    const result = {
      name: (typeof product !== 'undefined' ? product.name : 'Error!'),
      price: calcPromoPrice(basePrice, badge),
      basePrice,
      ...badge,
    };

    // If we're editing, adjust some things
    if (badge.editBadgeId > -1) {
      const oldproduct = rootState.products.all.find((product) => product.id == badge.editBadgePriorBadgeId);
      result.price = Math.max(0, result.price - oldproduct.price);
    }
    // if (badge.editBadgePriorAddons != undefined) {
    //  result.addonsSelected = badge.addonsSelected.filter(addon => !badge.editBadgePriorAddons.includes(addon));
    // }

    return result;
  }),
  cartCount: (state) => state.items.length || 0,

  cartTotalPrice: (state, getters, rootState) => {
    const { addons } = rootState.products;
    return getters.cartProducts.reduce((total, product) => {
      let addonTotal = 0;
      if (typeof product.addonsSelected.reduce === 'function') {
        let addonsSelected = [];
        if (product.editBadgePriorAddons == undefined) { addonsSelected = product.addonsSelected; } else { addonsSelected = product.addonsSelected.filter((addon) => !product.editBadgePriorAddons.includes(addon)); }
        addonTotal = addonsSelected.reduce((addonTotle, addonid) => {
          if (addons[product.badge_type_id] == undefined) return addonTotle;
          const addon = addons[product.badge_type_id].find((addon) => addon.id == addonid);
          return addonTotle + (addon == undefined ? 0 : parseFloat(addon.price));
        }, 0);
      }
      let prodPrice = parseFloat(product.price);
      if (isNaN(prodPrice)) { prodPrice = 0; }
      return total + parseFloat(product.price) + addonTotal;
    }, 0);
  },
  getProductInCart: (state) => (cartId) => state.items.find((item) => item.cartId === cartId && item.cartId != null),
  getCurrentlyEditingItem: (state) => state.currentlyEditingItem,
  getLatestContactInfo: (state) => state.latestContactInfo,
};

// actions
const actions = {
  checkout({
    commit,
    state,
  }, products) {
    const savedCartItems = [...state.items];
    commit('setCheckoutStatus', null);
    // Should we really empty cart before it's processed?
    // commit('setCartItems', {
    //  items: []
    // })
    shop.buyProducts(
      shop.transformPOSTData(products, false),
      (data) => {
        data.cart = shop.transformPOSTData(data.cart, true);
        commit('setCheckoutStatus', data);
      },
      (data) => {
        commit('setCheckoutStatus', data);
        // rollback to the cart saved before sending the request
        commit('setCartItems', {
          items: savedCartItems,
        });
      },
    );
  },
  clearCart({
    commit,
  }) {
    commit('setCheckoutStatus', null);
    commit('setCartItems', {
      items: [],
    });
  },

  addProductToCart({
    state,
    commit,
    rootState,
  }, badge) {
    commit('setCheckoutStatus', null);
    const product = rootState.products.all.find((product) => product.id === badge.badge_type_id);
    if (product.quantity == null | product.quantity > 0) {
      const cartItem = state.items.find((item) => item.cartId === badge.cartId && item.cartId != null);
      if (!cartItem) {
        badge.cartId = Math.max.apply(this, state.items.map((l) => l.cartId)) + 1;
        if (badge.cartId == -Infinity) { badge.cartId = 0; }
        commit('pushProductToCart', badge);

        // remove 1 item from stock
        commit('products/decrementProductQuantity', {
          id: product.id,
        }, {
          root: true,
        });
      } else {
        // Item already in cart, just update it
        commit('updateProductInCart', badge);
      }
    }
  },
  removeProductFromCart({
    state,
    commit,
  }, product) {
    const cartItem = state.items.find((item) => item.cartId === product.cartId && item.cartId != null);
    if (cartItem) {
      commit('removeProductFromCart', cartItem);
    }
  },
  applyPromoToProducts({
    commit,
  }, promo) {
    commit('setCheckoutStatus', null);
    // Should we really empty cart before it's processed?
    // commit('setCartItems', {
    //  items: []
    // })
    shop.applyPromo(
      shop.transformPOSTData(this.getters['cart/cartProducts'], false),
      promo,
      (data) => {
        data.cart = shop.transformPOSTData(data.cart, true);
        commit('setCartItems', {
          items: data.cart,
        });
        commit('setCheckoutStatus', data);
      },
      (data) => {
        commit('setCheckoutStatus', data);
      },
    );
  },
  removePromoFromProduct({
    state,
    commit,
  }, cartId) {
    const cartItem = state.items.find((item) => item.cartId === cartId && cartId != null);
    if (cartItem) {
      commit('removePromoFromProduct', cartItem);
    }
  },

};

// mutations
const mutations = {
  initialiseCart(state) {
    // Check if the ID exists
    if (localStorage.getItem('cart')) {
      // Replace the state object with the stored item
      // this.replaceState(
      Object.assign(state, JSON.parse(localStorage.getItem('cart')));
      // );
    }
  },
  pushProductToCart(state, item) {
    state.items.push(item);
  },

  updateProductInCart(state, product) {
    Vue.set(state.items, state.items.findIndex((el) => el.cartId === product.cartId), product);
  },
  removeProductFromCart(state, item) {
    const idx = state.items.indexOf(item);
    if (idx > -1) { state.items.splice(idx, 1); }
  },
  removePromoFromProduct(state, item) {
    item.promo = undefined;
    item.promoType = undefined;
    item.promoPrice = undefined;
    state.items[state.items.findIndex((el) => el.cartId === item.cartId)] = item;
  },

  setCartItems(state, {
    items,
  }) {
    state.items = items;
  },

  setCheckoutStatus(state, status) {
    state.checkoutStatus = status;
  },
  setCurrentlyEditingItem(state, item) {
    state.currentlyEditingItem = item;
  },
  setLatestContactInfo(state, contactInfo) {
    state.latestContactInfo = contactInfo;
  },
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
};
