import shop from '../../api/shop'

// initial state
const state = {
  all: [],
  questions: [],
  addons: []
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
  },
  getAllQuestions({
    commit
  }) {
    //Todo: Load only if necessary?
    shop.getQuestions(questions => {
      commit('setQuestions', questions)
    })
  },
  getAllAddons({
    commit
  }) {
    //Todo: Load only if necessary?
    shop.getAddons(addons => {
      commit('setAddons', addons)
    })
  }
}

// mutations
const mutations = {
  setProducts(state, products) {
    state.all = products
  },
  setQuestions(state, questions) {
    state.questions = questions
  },
  setAddons(state, addons) {
    state.addons = addons
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
