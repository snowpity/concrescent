const axios = require('axios').default;

export default {
    contextToPrefix(context_code) {
        switch (context_code) {
            case 'A':
                return 'Attendee';
            case 'S':
                return 'Staff';
            default:
                return 'Application/' + context_code;
        }
    },
    genericGet(token, path, params, cb, errorCb) {
        var qparams = new URLSearchParams({
            ...params
        }).toString();
        axios.get(global.config.apiHostURL + path + qparams, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(error.response.data);
            })
    },

    genericPost(token, path, data, cb, errorCb) {
        axios.post(global.config.apiHostURL + path, data, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(response.response.data);
            })
    },
    genericPut(token, path, data, cb, errorCb) {
        axios.put(global.config.apiHostURL + path, data, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(response.response.data);
            })
    },
    genericDelete(token, path, cb, errorCb) {
        axios.delete(global.config.apiHostURL + path, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(response.response.data);
            })
    },
    genericGetList(token, path, params, cb, errorCb) {
        var qparams = new URLSearchParams({
            ...params
        }).toString();
        axios.get(global.config.apiHostURL + path + '?' + qparams, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data, parseInt(response.headers['x-total-rows']));
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(response.response.data);
            })
    },
    badgeCheckinSearch(token, searchText, pageOptions, cb, errorCb) {
        var params = new URLSearchParams({
            "find": searchText,
            ...pageOptions
        }).toString();
        axios.get(global.config.apiHostURL + "Badge/CheckIn?" + params, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data, parseInt(response.headers['x-total-rows']));
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(response.response.data);
            })
    },
    badgeCheckinFetch(token, context, id, cb, errorCb) {
        axios.get(global.config.apiHostURL + "Badge/CheckIn/" + context + "/" + id, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(error.response.data);
            })
    },
    badgeCheckinSave(token, badgeData, cb, errorCb) {
        axios.post(global.config.apiHostURL + "Badge/CheckIn/" + badgeData.context_code + "/" + badgeData.id + "/Update", badgeData, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(error.response.data);
            })
    },
    badgeCheckinGetPayment(token, context, id, cb, errorCb) {
        axios.get(global.config.apiHostURL + "Badge/CheckIn/" + context + "/" + id + "/GetPayment", {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(error.response.data);
            })
    },
    badgeCheckinConfirmPayment(token, context, id, payData, cb, errorCb) {
        axios.post(global.config.apiHostURL + "Badge/CheckIn/" + context + "/" + id + "/PostPayment", payData, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(error.response.data);
            })
    },
    badgeCheckinFinish(token, context, id, cb, errorCb) {
        axios.post(global.config.apiHostURL + "Badge/CheckIn/" + context + "/" + id + "/Finish", null, {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            })
            .then(function(response) {
                cb(response.data);
            })
            .catch(function(error) {
                if (typeof errorCb != "undefined")
                    errorCb(error.response.data);
            })
    },

}