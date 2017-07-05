!function(exports) {
  'use strict';
  exports.__genValue = exports.__genValue || {};

  exports.__genValue.defineHandlers = function(mainId) {
    __genValue[mainId] = {
      onchange: function() {
        console.log('onchange!');
        var newValue = '';
        var qsBase = '.' + mainId;
        var table = document.getElementById(mainId + '__table');
        var menus = table.querySelectorAll(qsBase + '__menu');
        var types = table.querySelectorAll(qsBase + '__type');
        var literals = table.querySelectorAll(qsBase + '__literal');
        var titles = table.querySelectorAll(qsBase + '__title');
        var separator = '';
        for(var i = 0; i < menus.length; i++) {
           var type= types[i].options[types[i].selectedIndex].value;
          var menu = menus[i].value;
          var literal = literals[i].value;
          var title = titles[i].value;
          newValue += separator + menu +'|' + literal + ',' + title + ',' + type;
          separator = ';';
        }
        var origField = document.getElementById(mainId);
        origField.value = newValue;
        console.log('NV:', newValue);
      },
      genRow: function() {
        var row =
          document.getElementById(mainId + '__fakeRow').cloneNode(true);
        document.getElementById(mainId + '__table').appendChild(row);
      }
    };
  };

}(this); /* jshint ignore: line */
