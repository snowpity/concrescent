const axios = require('axios').default;

export default {

  badgeSearch(token, searchText,pageOptions, cb,errorCb) {
      var params = new URLSearchParams({"find":searchText,...pageOptions}).toString();
    axios.get(global.config.apiHostURL + "Badge/CheckIn?" + params,{
          headers: { Authorization: `Bearer ${token}` }
      })
      .then(function(response) {
        cb(response.data, parseInt(response.headers['x-total-rows']));
      })
      .catch(function(error) {
          if(typeof errorCb != "undefined")
            errorCb(response.response.data);
      })
  },
    badgeFetch(token, context,id, cb,errorCb) {
      axios.get(global.config.apiHostURL + "Badge/CheckIn/" + context + "/" + id,{
            headers: { Authorization: `Bearer ${token}` }
        })
        .then(function(response) {
          cb(response.data);
        })
        .catch(function(error) {
            if(typeof errorCb != "undefined")
              errorCb(response.response.data);
        })
    },

}
