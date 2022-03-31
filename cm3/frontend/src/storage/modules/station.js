import shop from '../../api/shop'

// initial state
const state = {
    kioskMode:false,
    localPrinting:false,
    preferredRemotePrinter:"",
    servicePrintJobs:false,
    printConfig:{
        allowedFormats:[],
        printFull:false, //Whether the background image will print too
        batchMode:false, //Whether to print with multiple "pages" per round
        cycleDelay:5000, //ms to delay before attempting to print the next in queue
    }
}

// getters
const getters = {

}

// actions
const actions = {
}

// mutations
const mutations = {
  setEventInfo(state, eventinfo) {
    state.eventinfo = eventinfo;
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
