'use strict';

angular.module('myApp')
  .controller('LogCtr', function ($scope, Api) {

    // -- Display the log --
    Api.getLog().then(updateLog)

    function updateLog(res) {
      if(!res.data || !res.data.log) { return false }
      $scope.log = res.data.log
    }

    function deleteLog() {
      $scope.log = ''
      Api.deleteLog()
    }

    function refreshLog() {
      $scope.log = ''
      Api.getLog().then(updateLog)
    }

    angular.extend($scope, {
      deleteLog : deleteLog,
      refreshLog : refreshLog
    })

  })
  .filter('trustedHtml', function ($sce) {
      return function(html) {
        return $sce.trustAsHtml(html)
      }
    })
