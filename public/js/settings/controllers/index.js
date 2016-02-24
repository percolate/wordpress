'use strict';

angular.module('myApp')
  .controller('IndexCtr', function ($scope) {
    console.log('Index / Manage Channel state');

    angular.extend($scope.edit, {
      active : false,
      channelId : null,
      channel : null
    })
  })
