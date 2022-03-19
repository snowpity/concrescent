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
  getProductContexts(event_id, cb) {
    axios.get(global.config.apiHostURL + "public/"+ event_id + '/badges')
      .then(function(response) {
        cb(response.data);
      })
      .catch(function(error) {
        error + "oops"
      })
  },
  getProducts(event_id,context,cb) {
    axios.get(global.config.apiHostURL + "public/" + event_id + '/badges/' + context)
      .then(function(response) {
        cb(response.data);
      })
      .catch(function(error) {
        error + "oops"
      })
  },

  buyProducts(products, cb, errorCb) {
    axios.post(global.config.apiHostURL + "cart.php", {
        action: 'checkout',
        payment_method: 'paypal',
        badges: products
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

  getQuestions(cb) {
    axios.get(global.config.apiHostURL + "questions.php")
      .then(function(response) {
        cb(response.data);
      })
      .catch(function(error) {
        error + "oops"
      })
  },

  getAddons(cb) {
    axios.get(global.config.apiHostURL + "addons.php")
      .then(function(response) {
        cb(response.data);
      })
      .catch(function(error) {
        error + "oops"
      })
  },

  getMyBadges(gid, tid, cb, errorCb) {
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
  sentEmailRetrieveBadges(email, cb, errorCb) {

    axios.post(global.config.apiHostURL + "mybadges.php", {
        email: email
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
      cartId: "index",
      nameFirst: "first-name",
      nameLast: "last-name",
      nameFandom: "fandom-name",
      nameDisplay: "name-on-badge",
      birthday: "date-of-birth",
      selectedBadgeId: "badge-type-id",
      contactEmail: "email-address",
      contactSubscribePromotions: "subscribed",
      contactPhone: "phone-number",
      contactStreet1: "address-1",
      contactStreet2: "address-2",
      contactCity: "city",
      contactState: "state",
      contactPostalCode: "zip-code",
      contactCountry: "country",
      contactEmergencyName: "ice-name",
      contactEmergencyRelationship: "ice-relationship",
      contactEmergencyEmail: "ice-email-address",
      contactEmergencyPhone: "ice-phone-number",
      promo: "payment-promo-code",
      promoType: "payment-promo-type",
      promoPrice: "payment-promo-amount",
      addonsSelected: "addon-ids",
      questionResponses: "form-answers",
      editBadgeId: "editing-badge",
      editBadgeIdUUID: "uuid",
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
        if (typeof product["questionResponses"] != 'undefined')
          Object.keys(product["questionResponses"]).forEach(key => {
            product["questionResponses"][key] = product["questionResponses"][key].join("\n");
          });
        //Remove addons
        delete product.addons;
      }


      //End looping Products (phew!)
    });
    return Products;
  }
}
