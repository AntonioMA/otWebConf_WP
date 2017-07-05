!function(exports) {
  'use strict';
  var __otWebConf = exports.__otWebConf || {};

  __otWebConf.startConference = function(url, domElement, style, title) {
    if (!url || url === "''") {
      return opentok.widget.stop();
    }
    var options = {
      title: title,
      target: domElement,
      style: style,
    };
    return opentok.widget.start(url, options);
  };

  // Set the list of users (with a valid appointment for each)
  __otWebConf.setUserApptList = function(users) {

  };

  exports.__otWebConf = __otWebConf;

}(this); /* jshint ignore: line */