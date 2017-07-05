!function(exports) {
  'use strict';
  const LIST_CLASS = '.contact-list';

  function sendXHR(aType, aURL, aData, aDataType, aResponseType, aHeaders) {
    return new Promise(function(resolve, reject) {
      aData = typeof aData === 'object' && JSON.stringify(aData) || aData;
      var xhr = new XMLHttpRequest();
      xhr.open(aType, aURL);
      xhr.responseType = aResponseType || 'json';
      xhr.overrideMimeType && xhr.overrideMimeType('application/json');
      if (aDataType) {
        // Note that this requires
        xhr.setRequestHeader('Content-Type', aDataType);
      }

      if (aHeaders && typeof aHeaders === 'object') {
        Object.keys(aHeaders).forEach(function(header) {
          xhr.setRequestHeader(header, aHeaders[header]);
        });
      }

      xhr.onload = function (aEvt) {
        if (xhr.status === 200) {
          var response = xhr.responseType === 'json' && (xhr.response || {}) || xhr.responseText;
          if (xhr.responseType === 'json' && typeof xhr.response === 'string') {
            response = JSON.parse(response);
          }
          resolve(response);
        } else {
          reject({ status: xhr.status, reason: xhr.response });
        }
      };

      xhr.onerror = function (aEvt) {
        debug.error('sendXHR. XHR failed ' + JSON.stringify(aEvt) + 'url: '+
                    aURL + ' Data: ' + aData + ' RC: ' + xhr.responseCode);
        reject(aEvt);
      };

      xhr.send(aData);
    });
  }

  function loadUserList() {
    sendXHR('GET', document.location.origin + '/?rest_route=/otWebConf/v1/users').
      then(function(aUserData) {
        fillUserList(aUserData);
      });

  }

  function fillUserList(userList) {
    var ul = document.querySelector(LIST_CLASS);
    // This must be filled in on the parent window before calling this
    userList.forEach(function(user) {
      var li = document.createElement('li');
      var a = document.createElement('a');
      a.href = user.appt_url;
      a.innerText = user.display_name;
      li.appendChild(a);
      ul.appendChild(li);
    });
  }

  exports.loadUserList = loadUserList;

}(this); /* jshint ignore: line */

window.addEventListener('load', window.loadUserList);