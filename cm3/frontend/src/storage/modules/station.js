import shop from '../../api/shop'

// initial state
const state = {
    kioskMode: false,
    remotePrinting: true,
    preferredRemotePrinter: "",
    serviceRemoteJobs: false,
    servicePrintJobsAs: "",
    printConfig: {
        allowedFormats: [],
        printFull: false, //Whether the background image will print too
        batchMode: false, //Whether to print with multiple "pages" per round
        cycleDelay: 300, //ms to delay before attempting to print the next in queue
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
    setremotePrinting({
        commit
    }, newremotePrinting) {
        commit('setremotePrinting', newremotePrinting);
    },
    setPreferredRemotePrinter({
        commit
    }, newPreferredRemotePrinter) {
        commit('setPreferredRemotePrinter', newPreferredRemotePrinter);
    },
    setServiceRemoteJobs({
        commit
    }, newServiceRemoteJobs) {
        commit('setServiceRemoteJobs', newServiceRemoteJobs);
    },
    setServicePrintJobsAs({
        commit
    }, newServicePrintJobsAs) {
        commit('setServicePrintJobsAs', newServicePrintJobsAs);
    },
    setPrintConfig({
        commit
    }, newPrintConfig) {
        commit('setPrintConfig', newPrintConfig);
    },
}

// mutations
const mutations = {
    initialiseData(state) {
        // Check if the ID exists
        if (localStorage.getItem('station')) {
            // Replace the state object with the stored item
            //this.replaceState(
            Object.assign(state, JSON.parse(localStorage.getItem('station')))
            //);
        }
    },
    setEventInfo(state, eventinfo) {
        state.eventinfo = eventinfo;
    },
    setKioskMode(state, newKioskMode) {
        state.kioskMode = newKioskMode;
    },
    setremotePrinting(state, newremotePrinting) {
        state.remotePrinting = newremotePrinting;
    },
    setPreferredRemotePrinter(state, newPreferredRemotePrinter) {
        state.preferredRemotePrinter = newPreferredRemotePrinter;
    },
    setServiceRemoteJobs(state, newServiceRemoteJobs) {
        state.serviceRemoteJobs = newServiceRemoteJobs;
    },
    setServicePrintJobsAs(state, newServicePrintJobsAs) {
        state.servicePrintJobsAs = newServicePrintJobsAs;
    },
    setPrintConfig(state, newPrintConfig) {
        state.printConfig = newPrintConfig;
    },
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}