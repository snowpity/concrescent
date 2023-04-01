import shop from '../../api/shop'

// initial state
const state = {
    kioskMode: false,
    localPrinting: false,
    preferredRemotePrinter: "",
    servicePrintJobsAs: "",
    printConfig: {
        allowedFormats: [],
        printFull: false, //Whether the background image will print too
        batchMode: false, //Whether to print with multiple "pages" per round
        cycleDelay: 5000, //ms to delay before attempting to print the next in queue
    }
}

// getters
const getters = {
    kioskMode: (state) => {
        return state.kioskMode;
    },

}

// actions
const actions = {
    setKioskMode({
        commit
    }, newMode) {
        commit('setKioskMode', newMode);
    },
}

// mutations
const mutations = {
    setEventInfo(state, eventinfo) {
        state.eventinfo = eventinfo;
    },
    setKioskMode(state, newKioskMode) {
        console.log('setting kiosk mode', newKioskMode);
        state.kioskMode = newKioskMode;
    },
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}