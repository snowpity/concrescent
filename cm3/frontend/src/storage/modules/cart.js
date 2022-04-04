import Vue from 'vue';
import shop from '../../api/shop';

// initial state
const state = {
    currentlyEditingItem: {},
    cartId: null,
    items: [],
    dirty: false,
    checkoutStatus: null,
};

function calcPromoPrice(basePrice, promoData) {
    if (promoData == null || typeof promoData.promoType === 'undefined') {
        return basePrice;
    }
    switch (promoData.promoType) {
        case 0:
            return Math.max(0, basePrice - promoData.promoPrice);
        case 1:
            return Math.max(0, basePrice * (100 - promoData.promoPrice) / 100);
    }
}

// getters
const getters = {
    isDirty: (state, getters, rootState) => {
        return rootState.mydata.token.length > 0 && state.dirty;
    },
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
        if (badge.id > -1) {
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
        const {
            addons
        } = rootState.products;
        return getters.cartProducts.reduce((total, product) => {
            let addonTotal = 0;
            if (typeof product.addonsSelected.reduce === 'function') {
                let addonsSelected = [];
                if (product.editBadgePriorAddons == undefined) {
                    addonsSelected = product.addonsSelected;
                } else {
                    addonsSelected = product.addonsSelected.filter((addon) => !product.editBadgePriorAddons.includes(addon));
                }
                addonTotal = addonsSelected.reduce((addonTotle, addonid) => {
                    if (addons[product.badge_type_id] == undefined) return addonTotle;
                    const addon = addons[product.badge_type_id].find((addon) => addon.id == addonid);
                    return addonTotle + (addon == undefined ? 0 : parseFloat(addon.price));
                }, 0);
            }
            let prodPrice = parseFloat(product.price);
            if (isNaN(prodPrice)) {
                prodPrice = 0;
            }
            return total + parseFloat(product.price) + addonTotal;
        }, 0);
    },
    getProductInCart: (state) => (cartIx) => state.items.find((item) => item.cartIx === cartIx && item.cartIx != null),
    getCurrentlyEditingItem: (state) => state.currentlyEditingItem,
    getContactInfo: (state) => state.latestContactInfo,
};

// actions
const actions = {
    saveCart({
        commit,
        state,
        rootState
    }, promocode) {
        return new Promise((resolve, reject) => {
            if (rootState.mydata.token.length > 0) {

                shop.saveCart(rootState.mydata.token, {
                    id: state.cartId,
                    items: state.items,
                    promocode: promocode
                }, (result) => {
                    commit('setcartId', result.id);
                    commit('setCheckoutStatus', {
                        errors: result.errors,
                        state: result.state
                    });
                    commit('setCartItems', result);
                    commit('clearDirty');
                    resolve();
                }, (er) => {
                    reject(er);
                })
            } else {
                reject({
                    error: {
                        message: "Not logged in"
                    }
                });
            }
        });
    },
    checkoutCart({
        commit,
        state,
        rootState
    }, payment_system) {
        commit('setCheckoutStatus', null);
        shop.buyProducts(
            rootState.mydata.token,
            state.cartId,
            payment_system || "PayPal",
            (data) => {
                commit('setCheckoutStatus', data);
            },
            (data) => {
                if (typeof data != "string") {
                    commit('setCheckoutStatus', data);
                } else {
                    commit('setCheckoutStatus', {
                        state: 'Failed',
                        errors: []
                    })
                }


            },
        );
    },
    clearCart({
        state,
        commit,
        rootState
    }) {
        if (state.cartId != null) {
            shop.deleteCart(
                rootState.mydata.token,
                state.cartId,
                (data) => {

                    commit('setCheckoutStatus', null);
                    commit('setCartItems', {
                        items: [],
                    });
                    commit('setcartId', null);
                }
            );
        } else {
            //We don't know about any cart, just clear it


            commit('setCheckoutStatus', null);
            commit('setCartItems', {
                items: [],
            });
            commit('setcartId', null);
        }
    },

    addProductToCart({
        state,
        commit,
        rootState,
    }, badge) {
        commit('setCheckoutStatus', null);
        const product = rootState.products.all.find((product) => product.id === badge.badge_type_id);
        if (product.quantity == null | product.quantity > 0) {
            const cartItem = state.items.find((item) => item.cartIx === badge.cartIx && item.cartIx != null);
            if (!cartItem) {
                badge.cartIx = Math.max.apply(this, state.items.map((l) => l.cartIx)) + 1;
                if (badge.cartIx == -Infinity) {
                    badge.cartIx = 0;
                }
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
        const cartItem = state.items.find((item) => item.cartIx === product.cartIx && item.cartIx != null);
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
    }, cartIx) {
        const cartItem = state.items.find((item) => item.cartIx === cartIx && cartIx != null);
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
        state.dirty = true;
    },

    updateProductInCart(state, product) {
        Vue.set(state.items, state.items.findIndex((el) => el.cartIx === product.cartIx), product);
        state.dirty = true;
    },
    removeProductFromCart(state, item) {
        const idx = state.items.indexOf(item);
        if (idx > -1) {
            state.items.splice(idx, 1);
        }
        state.dirty = true;
    },
    removePromoFromProduct(state, item) {
        item.promo = undefined;
        item.promoType = undefined;
        item.promoPrice = undefined;
        state.items[state.items.findIndex((el) => el.cartIx === item.cartIx)] = item;
        state.dirty = true;
    },
    setcartId(state, id) {
        state.cartId = id;
    },

    setCartItems(state, {
        items,
    }) {
        if (Array.isArray(items))
            state.items = items;
    },

    setCheckoutStatus(state, status) {
        state.checkoutStatus = status;
    },
    setCurrentlyEditingItem(state, item) {
        state.currentlyEditingItem = item;
    },
    clearDirty(state) {
        state.dirty = false;
    }
};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations,
};