const axios = require('axios').default;

export default {

    getEventInfo(cb) {
        axios.get(global.config.apiHostURL + "public")
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                error + "oops"
            })
    },
    getBadgeContexts(event_id, cb) {
        axios.get(global.config.apiHostURL + "public/" + event_id + '/badges')
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                error + "oops"
            })
    },
    getBadges(event_id, context, override_code, cb) {
        const override = (override_code ?? '').replace(/[^a-z0-9]/gi, '').toUpperCase();
        var query = override != '' ? '?override=' + override : '';
        axios.get(global.config.apiHostURL + "public/" + event_id + '/badges/' + context + query)
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                error + "oops"
            })
    },

    getQuestions(event_id, context, cb) {
        axios.get(global.config.apiHostURL + "public/" + event_id + '/questions/' + context)
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                error + "oops"
            })
    },

    getAddons(event_id, context, override_code, cb) {
        const override = (override_code ?? '').replace(/[^a-z0-9]/gi, '').toUpperCase();
        var query = override != '' ? '?override=' + override : '';
        axios.get(global.config.apiHostURL + "public/" + event_id + '/badges/' + context + '/addons' + query)
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                error + "oops"
            })
    },

    getCarts(token, include_all, cb, errorCb) {
        axios.get(global.config.apiHostURL + "account/cart?include_all=" + include_all, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(response) {
                console.log(response)
                errorCb(response.response.data);
            });
    },

    //Response should be a token
    createAccount(accountInfo, cb, errorCb) {
        axios.post(global.config.apiHostURL + "public/createaccount", accountInfo)
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(response) {
                errorCb(response.response.data);
            });
    },
    loginAccount(accountCreds, cb, errorCb) {
        axios.post(global.config.apiHostURL + "public/login", accountCreds)
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(response) {
                errorCb(response.response.data);
            });
    },
    getContactInfo(token, cb, errorCb) {
        axios.get(global.config.apiHostURL + "account", {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(response) {
                errorCb(response.response.data);
            });
    },
    setContactInfo(token, data, cb, errorCb) {
        axios.post(global.config.apiHostURL + "account", data, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(response) {
                errorCb(response.response.data);
            });
    },
    switchEvent(token, event_id, cb, errorCb) {
        axios.post(global.config.apiHostURL + "account/switchevent", {
                "event_id": event_id
            }, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(response) {
                if (typeof errorCb != "undefined")
                    errorCb(response.response.data);
            });
    },
    setAccountSettings(token, settings, cb, errorCb) {
        axios.post(global.config.apiHostURL + "account/settings", settings, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(response) {
                if (typeof errorCb != "undefined")
                    errorCb(response.response.data);
            });
    },

    loadCart(token, cartId, cb, errorCb) {
        axios.get(global.config.apiHostURL + "account/cart/" + cartId, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(er) {
                errorCb(er.response.data);
            });
    },
    saveCart(token, cart, cb, errorCb) {
        axios.post(global.config.apiHostURL + "account/cart", cart, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(er) {
                errorCb(er.response.data);
            });
    },
    deleteCart(token, cartId, cb, errorCb) {
        axios.delete(global.config.apiHostURL + "account/cart/" + cartId, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(er) {
                errorCb(er.response.data);
            });
    },
    buyProducts(token, cartId, payment_system, cb, errorCb) {
        axios.post(global.config.apiHostURL + `account/cart/${cartId}/checkout`, {
                payment_system: payment_system
            }, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(response) {
                errorCb(response.response.data);
            });
    },
    checkoutCartUUID(token, cartUUID, payment_system, cb, errorCb) {
        axios.post(global.config.apiHostURL + `account/cart/-1/checkout`, {
                uuid: cartUUID
            }, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(response) {
                errorCb(response.response.data);
            });
    },

    applyPromo(products, promo, cb, errorCb) {
        axios.post(global.config.apiHostURL + "cart.php", {
                action: 'applypromo',
                code: promo,
                badges: products
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(response) {
                errorCb(response.response.data);
            });
    },

    getMyBadgesByTransaction(gid, tid, cb, errorCb) {
        axios.post(global.config.apiHostURL + "mybadges.php", {
                gid: gid,
                tid: tid
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                errorCb(error.response.data);
            })
    },
    getMyBadges(token, cb, errorCb) {
        axios.get(global.config.apiHostURL + "account/badges", {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                errorCb(error.response.data);
            })
    },
    getSpecificBadge(context_code, id, uuid, cb, errorCb) {
        axios.get(global.config.apiHostURL + "public/getspecificbadge?context_code=" +
                context_code + "&id=" + id + "&uuid=" + uuid)
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                errorCb(error.response.data);
            })
    },
    getMyApplications(token, cb, errorCb) {
        axios.get(global.config.apiHostURL + "account/applications", {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                errorCb(error.response.data);
            })
    },
    sentEmailRetrieveBadges(email, cb, errorCb) {

        axios.post(global.config.apiHostURL + "public/requestmagic", {
                email_address: email
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                errorCb(error.response.data);
            })
    },
}