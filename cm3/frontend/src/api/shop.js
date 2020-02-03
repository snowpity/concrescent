const axios = require('axios').default;

export default {
  getProducts(cb) {
    axios.get(global.config.apiHostURL + "badges.php")
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


    /*
    setTimeout(() => {
      // simulate random checkout failure.
      (Math.random() > 0.5 || navigator.userAgent.indexOf('PhantomJS') > -1) ?
      cb(): errorCb()
    }, 100)*/
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
  }
}
