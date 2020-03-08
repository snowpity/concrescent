import shop from '../../api/shop'

// initial state
const state = {
  all: [],
  questions: [],
  addons: [],
  gotAll: false,
  gotQuestions: false,
  gotAddons: false
}

// getters
const getters = {

}

// actions
const actions = {
  getAllProducts({
    commit,
    state
  }) {
    return new Promise((resolve) => {
      //Load only if necessary
      if (!state.gotAll) {
        shop.getProducts(products => {
          commit('setProducts', products);
          resolve();
        })
      } else {
        resolve();
      }
    })
  },
  getAllQuestions({
    commit,
    state
  }) {
    return new Promise((resolve) => {
      //Load only if necessary
      if (!state.gotQuestions) {
        shop.getQuestions(questions => {
          commit('setQuestions', questions)
        })
      } else {
        resolve();
      }
    })
  },
  getAllAddons({
    commit,
    state
  }) {
    return new Promise((resolve) => {
      //Load only if necessary
      if (!state.gotAddons)
        shop.getAddons(addons => {
          commit('setAddons', addons)
        })
      else {
        resolve();
      }
    })
  }
}

// mutations
const mutations = {
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
