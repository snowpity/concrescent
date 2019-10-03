import shop from '../../api/shop'

// initial state
// shape: [{ id, quantity }]
const state = {
  items: [],
  checkoutStatus: null
}

function calcPromoPrice(basePrice, promoData) {
  if (promoData == null || typeof promoData.promoType == "undefined")
    return basePrice;
  switch (promoData.promoType) {
    case 0:
      return Math.max(0, basePrice - promoData.promoPrice);
    case 1:
      return Math.max(0, basePrice * (100 - promoData.promoPrice) / 100);
  }
}


// getters
const getters = {
  cartProducts: (state, getters, rootState) => {
    return state.items.map((badge) => {
      const product = rootState.products.all.find(product => product.id == badge.selectedBadgeId)
      //if product not found, and we don't have any, assume loading
      if (product == undefined && rootState.products.all.length == 0) {
        return {
          title: "Loading...",
          price: "Loading...",
          ...badge
        }
      } else {
        return {
          name: (typeof product != 'undefined' ? product.name : "Error!"),
          price: calcPromoPrice((typeof product != 'undefined' ? product.price : Infinity), badge),
          basePrice: (typeof product != 'undefined' ? product.price : Infinity),
          ...badge
        }
      }

    })
  },
  cartCount: (state) => {
    return state.items.length || 0;
  },

  cartTotalPrice: (state, getters) => {
    return getters.cartProducts.reduce((total, product) => {
      return total + product.price
    }, 0)
  },
  getProductInCart: (state) =>
    (cartId) => {
      return state.items.find(item => item.cartId === cartId && item.cartId != null);
    },
}

// actions
const actions = {
  checkout({
    commit,
    state
  }, products) {
    const savedCartItems = [...state.items]
    commit('setCheckoutStatus', null)
    // Should we really empty cart before it's processed?
    commit('setCartItems', {
      items: []
    })
    shop.buyProducts(
      products,
      () => commit('setCheckoutStatus', 'successful'),
      () => {
        commit('setCheckoutStatus', 'failed')
        // rollback to the cart saved before sending the request
        commit('setCartItems', {
          items: savedCartItems
        })
      }
    )
  },
  clearCart({
    commit
  }) {

    commit('setCheckoutStatus', null)
    commit('setCartItems', {
      items: []
    })
  },

  addProductToCart({
    state,
    commit,
    rootState
  }, badge) {
    commit('setCheckoutStatus', null)
    const product = rootState.products.all.find(product => product.id === badge.selectedBadgeId)
    if (product.quantity > 0 | product.quantity == null) {
      const cartItem = state.items.find(item => item.cartId === badge.cartId && item.cartId != null)
      if (!cartItem) {
        badge.cartId = Math.max.apply(this, state.items.map((l) => l.cartId)) + 1
        if (badge.cartId == -Infinity)
          badge.cartId = 1;
        commit('pushProductToCart', badge)

        // remove 1 item from stock
        commit('products/decrementProductQuantity', {
          id: product.id
        }, {
          root: true
        })

      } else {
        //Item already in cart, just update it
        commit('updateProductInCart', badge)
      }
    }
  },
  removeProductFromCart({
    state,
    commit
  }, product) {

    const cartItem = state.items.find(item => item.cartId === product.cartId && item.cartId != null);
    if (cartItem) {
      commit('removeProductFromCart', cartItem);
    }
  }
}

// mutations
const mutations = {
  initialiseCart(state) {
    // Check if the ID exists
    if (localStorage.getItem('cart')) {
      // Replace the state object with the stored item
      //this.replaceState(
      Object.assign(state, JSON.parse(localStorage.getItem('cart')))
      //);
    }
  },
  pushProductToCart(state, item) {
    state.items.push(item)
  },

  updateProductInCart(state, product) {
    state.items[state.items.findIndex(el => el.cartId === product.cartId)] = product;
  },
  removeProductFromCart(state, item) {
    var idx = state.items.indexOf(item);
    if (idx > -1)
      state.items.splice(idx, 1);
  },

  setCartItems(state, {
    items
  }) {
    state.items = items
  },

  setCheckoutStatus(state, status) {
    state.checkoutStatus = status
  }
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
