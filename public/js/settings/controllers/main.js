'use strict';

angular.module('myApp')
  .controller('MainCtr', function ($scope, $state, Api, $rootScope, Percolate, UUID) {
    console.log('Angular app started, setting the state...');

    /* --------------------------
     * Public variables
     * -------------------------- */

     // -- Underscore --
    $scope._ = window._

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
     * Extending the Scope with methods
     * -------------------------- */

    angular.extend($scope, {
      editChannel: editChannel,
      deleteChannel: deleteChannel,
      restoreChannel: restoreChannel,
      importChannel: importChannel,

      showError: showError,
      resetError: resetError,
      showLoader: showLoader,
      stopLoader: stopLoader,

      dismissMessage: dismissMessage
    })

    /* --------------------------
     * Startup
     * -------------------------- */
    $state.go('manage')

    // -- Get Percolate settings
    showLoader('Loading data...')
    Api.getData()
      .then(init, showError)

    // -- Display the log --
    Api.getLog().then(updateLog)


    /* --------------------------
     * Public methods
     * -------------------------- */

    function editChannel (channelId) {
      angular.extend($scope.edit, {
        active : true,
        channelId : channelId,
        channel : $scope.Percolate.channels[channelId]
      })
      console.log($scope.edit);
      $state.go('add.topics')
    }

    function deleteChannel (channelId, isDeleted) {
      $scope.Percolate.channels[channelId].active = 'false'
      if (isDeleted) {
        delete $scope.Percolate.channels[channelId]
      }

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
        }, showError)
    }

    function deleteHiddenChannels() {
      _.each($scope.Percolate.channels, function (channel, uuid, list) {
        console.log(channel, uuid)
        if(!$scope.Percolate.channels[uuid].active || $scope.Percolate.channels[uuid].active === 'false' ) {
          console.log('delete')
          delete $scope.Percolate.channels[uuid]
        }
      })
      // console.log('Cleaned up: ', $scope.Percolate);
      Api.setData($scope.Percolate)
    }

    function restoreChannel (channelId) {
      $scope.Percolate.channels[channelId].active = 'true'

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

    function importChannel (channelId) {
      $scope.showLoader('Importing channel...')
      Percolate.doImport(channelId)
        .then(function (res) {
          $scope.stopLoader()
          Api.getMessages().then(updateMessages)
          console.log('Import channel', res)
        }, function (err) {
          $scope.stopLoader()
          $scope.showError(err)
        })
    }

    function showError (error) {
      /*
       * Show & log errors
       */
      console.info(error)
      $scope.error.active = true
      $scope.error.message = error
      return
    }


    function resetError () {
      /*
       * Reset error messages
       */
      $scope.error.active = false
      $scope.error.message = ''
      return
    }


    function showLoader (msg) {
      /*
       * Start loader
       */
      $scope.loader.active = true
      $scope.loader.message = msg
      return
    }


    function stopLoader () {
      /*
       * Stop loader
       */
      $scope.loader.active = false
      $scope.loader.message = ''
      return
    }

    function dismissMessage ($index) {
      $scope.messages.warning.splice($index, 1)
      Api.setMessages($scope.messages)
    }

    /* --------------------------
     * Event handlers
     * -------------------------- */

    $rootScope.$on('$stateChangeSuccess', resetError)

    /* --------------------------
     * Private methods
     * -------------------------- */

    function init (res) {
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
    }

    function updateMessages(res) {
      if(!res.data) { return false }
      $scope.messages = res.data
      console.info('Messages', res.data)
    }

    function updateLog(res) {
      if(!res.data || !res.data.log) { return false }
      $scope.log = res.data.log
    }

  })
