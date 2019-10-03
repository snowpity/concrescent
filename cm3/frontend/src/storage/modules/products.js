import shop from '../../api/shop'

// initial state
const state = {
  all: []
}

// getters
const getters = {

}

// actions
const actions = {
  getAllProducts({
    commit
  }) {
    //Todo: Load only if necessary?
    shop.getProducts(products => {
      commit('setProducts', products)
    })
  }
}

// mutations
const mutations = {
  setProducts(state, products) {
    state.all = products
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