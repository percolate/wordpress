'use strict';

angular.module('myApp')
  .controller('MainCtr', function ($scope, $state, Api, $rootScope, Percolate, UUID) {
    console.log('Angular app started, setting the state...');

    $state.go('manage')
    // $state.go('add.setup')

    /* --------------------------
     * Public variables
     * -------------------------- */

     // -- Underscore --
    $scope._ = _

    // -- Loader --
    $scope.loader= {
      active: false,
      message: ''
    }

    // -- Error message --
    $scope.error= {
      active: false,
      message: ''
    }

    // -- Edit channel --
    $scope.edit = {
      active: false,
      channelId: null,
      channel: null
    }

    /* --------------------------
     * Public methods
     * -------------------------- */


    $scope.editChannel = function (channelId) {
      angular.extend($scope.edit, {
        active : true,
        channelId : channelId,
        channel : $scope.Percolate.channels[channelId]
      })
      console.log($scope.edit);
      $state.go('add.topics')
    }

    $scope.deleteChannel = function (channelId) {
      $scope.Percolate.channels[channelId].active = 'false'

      console.log('Submiting data, current dataset: ', $scope.Percolate)
      $scope.showLoader('Saving data...')
      Api.setData($scope.Percolate)
        .then(function (res) {
          $scope.stopLoader()
          console.log('Data saved', res)
          // reset the new channel object
          $scope.activeChannel = {}
          // all done here
          $state.go('manage')
        }, function (err) {
          $scope.stopLoader()
          $scope.showError(err)
        })
    }

    $scope.importChannel = function (channelId) {
      $scope.showLoader('Importing channel...')
      Percolate.doImport(channelId)
        .then(function (res) {
          $scope.stopLoader()
          console.log('Import channel', res)
        }, function (err) {
          $scope.stopLoader()
          $scope.showError(err)
        })
    }

    /*
     * Show & log errors
     */
    $scope.showError = function (error) {
      console.info(error)
      $scope.error.active = true
      $scope.error.message = error
      return
    }

    /*
     * Reset error messages
     */
    $scope.resetError = function () {
      $scope.error.active = false
      $scope.error.message = ''
      return
    }

    /*
     * Start loader
     */
    $scope.showLoader = function (msg) {
      $scope.loader.active = true
      $scope.loader.message = msg
      return
    }

    /*
     * Stop loader
     */
    $scope.stopLoader = function () {
      $scope.loader.active = false
      $scope.loader.message = ''
      return
    }

    /* --------------------------
     * Event handlers
     * -------------------------- */

    // -- Get Percolate settings
    $scope.showLoader('Loading data...')
    Api.getData()
      .then(function (res) {
        $scope.stopLoader()
        console.log('Getting data', res);
        if(!res.data || res.data === 'false') {
          // Create an object for Percolate settings
          $scope.Percolate = {
            channels: {},
            settings: {},
            uuid: UUID.generate()
          }
          $state.go('add.setup')
        } else {
          // Data is already stored, populate the settings object
          $scope.Percolate = res.data
          if(!$scope.Percolate.channels) {
            $scope.Percolate.channels = {}
          }
          if(!$scope.Percolate.settings) {
            $scope.Percolate.settings = {}
          }
        }
      }, function (err) {
        $scope.stopLoader()
        $scope.showError(err)
        console.log(err);
      })

    $rootScope.$on('$stateChangeSuccess', function (event, toState, toParams, from, fromParams) {
      // Reset the error message
      $scope.resetError()
    })

  })
