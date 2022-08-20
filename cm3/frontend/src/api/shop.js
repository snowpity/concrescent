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

    getAddons(event_id, context, cb) {
        axios.get(global.config.apiHostURL + "public/" + event_id + '/badges/' + context + '/addons')
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
    transformPOSTData(inProducts, reverse) {
        //Create a copy of the products. Since it's destined to be JSON anyways, we don't worry about it...
        var Products = JSON.parse(JSON.stringify(inProducts));
        const pMap = {
            cartIx: "index",
            real_name: "real_name",
            fandom_name: "fandom-name",
            name_on_badge: "name-on-badge",
            date_of_birth: "date-of-birth",
            badge_type_id: "badge-type-id",
            contactEmail: "email-address",
            contactSubscribePromotions: "subscribed",
            contactPhone: "phone-number",
            contactStreet1: "address-1",
            contactStreet2: "address-2",
            contactCity: "city",
            contactState: "state",
            contactPostalCode: "zip-code",
            contactCountry: "country",
            ice_name: "ice-name",
            ice_relationship: "ice-relationship",
            ice_email_address: "ice-email-address",
            ice_phone_number: "ice-phone-number",
            promo: "payment-promo-code",
            promoType: "payment-promo-type",
            promoPrice: "payment-promo-amount",
            addonsSelected: "addon-ids",
            form_responses: "form-answers",
            id: "editing-badge",
            idUUID: "uuid",
            editBadgePriorBadgeId: "editing-prior-id",
            editBadgePriorAddons: "editing-prior-addon-ids",
        }
        //Loop all the  Products
        Products.forEach(product => {
            //First, rename the top-level keys
            Object.keys(pMap).forEach(key => {
                var from = reverse ? pMap[key] : key;
                var to = reverse ? key : pMap[key];
                if (product.hasOwnProperty(from)) {
                    delete Object.assign(product, {
                        [to]: product[from]
                    })[from];
                }
            });

            if (!reverse) {

                //Fixup Addons
                Object.keys(product["addon-ids"]).forEach(key => {
                    product["addon-" + product["addon-ids"][key]] = 1;
                });

                //Fixup questions
                Object.keys(product["form-answers"]).forEach(key => {
                    product["cm-question-" + key] = product["form-answers"][key];
                });
            } else {
                //Fixup questions
                if (typeof product["form_responses"] != 'undefined')
                    Object.keys(product["form_responses"]).forEach(key => {
                        product["form_responses"][key] = product["form_responses"][key].join("\n");
                    });
                //Remove addons
                delete product.addons;
            }


            //End looping Products (phew!)
        });
        return Products;
    }
}