'use strict';

angular.module('myApp')
  .controller('LogCtr', function ($scope, Api) {

    // -- Display the log --
    Api.getLog().then(updateLog)

    function updateLog(res) {
      if(!res.data || !res.data.log) { return false }
      $scope.log = res.data.log
    }

  })
  .filter('trustedHtml', function ($sce) {
      return function(html) {
        return $sce.trustAsHtml(html)
      }
    })
