import shop from '../../api/shop'

// initial state
const state = {
    token: "",
    permissions: null,
    adminMode: false,
    ownedbadges: [],
    BadgeRetrievalStatus: false,
    BadgeRetrievalResult: '',
    contactInfo: {
        "allow_marketing": 0,
        "email_address": "",
        "real_name": "",
        "phone_number": "",
        "address_1": "",
        "address_2": "",
        "city": "",
        "state": "",
        "zip_code": "",
        "country": ""
    },
    activeCarts: null,
    allCarts: null,
}


// getters
const getters = {
    ownedBadgeCount: (state) => {
        return state.ownedbadges == undefined ? 0 : state.ownedbadges.length;
    },

    getBadgeAsCart: (state) =>
        (badgeIx) => {
            // var tempProduct = [
            //     state.ownedbadges[badgeIx]
            // ]
            // var result = shop.transformPOSTData(tempProduct, true)[0];
            var result = state.ownedbadges[badgeIx];
            if (result.addonsSelected != undefined && result.addonsSelected.length > 0)
                result.editBadgePriorAddons = [...result.addonsSelected];
            result.id = result.id;
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
        if (state.permissions == null || state.permissions == undefined) return false;
        return state.permissions.EventPerms.findIndex((perm) => perm == permName || perm == 'GlobalAdmin') > -1;
    },
    hasGroupPerm: (state, getters) => (groupId, permName) => {
        if (state.permissions == null || state.permissions == undefined) return false;
        //Check if they're a GlobalAdmin
        if (getters.hasEventPerm("GlobalAdmin")) return true;
        if (state.permissions.GrouPerms[groupId] == undefined) return false;
        return state.permissions.GrouPerms[groupId].findIndex((perm) => perm == permName) > -1;
    },
    getContactInfo: (state) => {
        return state.contactInfo;
    },
    getLoggedInName: (state) => {
        if (state.contactInfo != undefined)
            return state.contactInfo.real_name || state.contactInfo.email_address;
        else
            return "Guest";
    }
}

// actions
const actions = {
    createAccount({
        dispatch,
        commit
    }, accountInfo) {
        return new Promise((resolve, reject) => {
            shop.createAccount(accountInfo,
                (token) => {
                    resolve(dispatch('loginToken', token));
                }, (error) => {
                    reject(error);
                });

        })
    },
    loginToken({
        dispatch,
        commit,
        state
    }, token) {
        return new Promise((resolve) => {
            shop.switchEvent(token, state.event_id, (data) => {
                    commit('setToken', data.token);
                    commit('setPermissions', data.permissions);
                    dispatch('products/selectEventId', data.event_id, {
                        root: true
                    });
                    dispatch('refreshContactInfo');
                    dispatch('retrieveBadges');
                    resolve(true);
                },
                (error) => resolve(error || "Failed, maybe the link expired?")
            );

        })
    },
    RefreshToken({
        state,
        dispatch
    }) {
        return dispatch('loginToken', state.token);
    },
    loginPassword({
        dispatch,
        commit,
        state
    }, credentials) {
        return new Promise((resolve) => {
            shop.loginAccount(credentials, (data) => {
                commit('setToken', data.token);
                commit('setPermissions', data.permissions);
                commit('setAdminMode', data.permissions != undefined)
                dispatch('products/selectEventId', data.event_id, {
                    root: true
                });
                dispatch('refreshContactInfo');
                resolve(true);
            }, (error) => {
                resolve(error.error.message);
            });
        })
    },
    logout({
        commit
    }) {
        commit('setToken', "");
        commit('setPermissions', null);
        commit('setContactInfo', {
            "allow_marketing": 0,
            "email_address": "",
            "real_name": "",
            "phone_number": "",
            "address_1": "",
            "address_2": "",
            "city": "",
            "state": "",
            "zip_code": "",
            "country": ""
        });
        commit('setAdminMode', false);
    },
    setAdminMode({
        commit
    }, newAdminMode) {
        commit('setAdminMode', newAdminMode);
    },
    refreshContactInfo({
        commit,
        state
    }) {
        return new Promise((resolve) => {
            shop.getContactInfo(state.token, (data) => {
                commit('setContactInfo', data);
            })
        })
    },
    fetchCarts({
        commit,
        state
    }, include_all) {
        return new Promise((resolve, reject) => {
            shop.getCarts(state.token, include_all, (carts) => {
                commit('setCarts', {
                    carts,
                    include_all,
                    success: true
                });
                resolve();
            }, (error) => {
                commit('setCarts', {
                    carts: null,
                    include_all,
                    success: false
                });
                reject(error);
            })
        })
    },
    updateContactInfo({
        commit,
        state
    }, newData) {
        return new Promise((resolve) => {
            shop.setContactInfo(state.token, newData, (data) => {
                commit('setContactInfo', data);
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
    }) {
        shop.getMyBadges(state.token, (data) => {
            commit('setOwnedBadges', data);
        })
    },
    retrieveTransactionBadges({
        commit,
        state
    }, submitted) {
        return;
        shop.getMyBadgesByTransaction(
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

    setToken(state, newtoken) {
        state.token = newtoken;
    },
    setPermissions(state, newPermissions) {
        state.permissions = newPermissions;
    },
    setAdminMode(state, newAdminMode) {
        state.adminMode = newAdminMode;
    },
    setContactInfo(state, newContactInfo) {
        state.contactInfo = newContactInfo;
    },

    setOwnedBadges(state, items) {
        if (items != undefined)
            state.ownedbadges = items;
        else
            state.ownedbadges = [];
    },
    setBadgeRetrievalStatus(state, newState) {
        state.BadgeRetrievalStatus = newState;
    },
    setBadgeRetrievalResult(state, newState) {
        state.BadgeRetrievalResult = newState;
    },
    setCarts(state, cartsInfo) {
        //      {carts:null,include_all:include_all,success:false}
        if (cartsInfo.include_all) {
            state.allCarts = carts;
        } else {
            state.activeCarts = carts;
        }
    },

}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}