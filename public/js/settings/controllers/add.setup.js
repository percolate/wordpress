'use strict';

angular.module('myApp')
  .controller('AddSetupCtr', function ($scope, $state, Percolate) {
    console.log('Add New Channel - Setup state')

    $scope.formData = {}

    /*
     * Check the API key and retrieve data from Percolata
     */
    $scope.checkKey = function() {
      if($scope.formData.key && $scope.formData.key.length === 40) {
        console.info('API key has been entered, looking for User...')
        $scope.showLoader('Searching for user in Percolate...')
        Percolate.getUser({key: $scope.formData.key})
          .then(function (res) {

            if( !res.data ) {
              console.info(res)
              $scope.showError('There was an error.')
              return
            }

            $scope.formData.user = res.data

            $scope.showLoader('Loading licenses...')
            return Percolate.getLicenses({
              key    : $scope.formData.key,
              fields : {
                user_id: $scope.formData.user.id,
                limit: 1000
              }
            })

          }, function (err) {
            $scope.showError(err)
            return
          })
          .then(function (res) {

            if( !res.data || !res.data.data ) {
              $scope.showError('There was an error.')
              return
            }

            $scope.licenses = res.data.data
            $scope.formData.license = $scope.licenses[0].id
            $scope.changeLicense()
            return

          }, function (err) {
            $scope.stopLoader()
            $scope.showError(err.statusText)
            return
          })
      }
    }



    /*
     * Fetch platforms upon license change
     */
    $scope.changeLicense = function () {
      // reset data that depends on license
      $scope.platforms = null
      $scope.channels = null
      // reset errors
      $scope.resetError()

      $scope.showLoader('Loading platforms...')
      // get platforms
      Percolate.getPlatforms({
        key    : $scope.formData.key,
        fields : {
          scope_ids: 'license:' + $scope.formData.license
        }
      })
        .then(function (res) {
          console.log('Platforms', res.data)

          if( !res.data || !res.data.data ) {
            $scope.showError('There was an error.')
            return
          }

          $scope.platforms = $scope._.filter(res.data.data, function (o) {
            return o.scope_id
          })

          if(!$scope.platforms || $scope.platforms.length < 1) {
            $scope.showError('No platform was found...')
            return
          }

          $scope.formData.platform = $scope.platforms[0].id

          $scope.changePlatform()
          return

        }, function (err) {
          $scope.stopLoader()
          $scope.showError(err.statusText)
          return
        })
    }

    /*
     * Fetch channels upon license change
     */
    $scope.changePlatform = function () {
      // reset data that depends on platform
      $scope.channels = null
      // reset errors
      $scope.resetError()

      $scope.showLoader('Loading channels...')
      // get channels
      Percolate.getChannels({
        key    : $scope.formData.key,
        fields : {
          'scope_ids': 'license:' + $scope.formData.license
        }
      })
        .then(function (res) {
          console.log('Channels', res.data)
          $scope.stopLoader()

          if( !res.data || !res.data.data ) {
            $scope.showError('There was an error.')
            return
          }

          $scope.channels = $scope._.filter(res.data.data, function (o) {
            return o.platform_id == $scope.formData.platform
          })

          if(!$scope.channels || $scope.channels.length < 1) {
            $scope.showError('No channel was found...')
            return
          }

          return $scope.formData.channel = $scope.channels[0].id

        }, function (err) {
          $scope.stopLoader()
          $scope.showError(err.statusText)
          return
        })
    }

    /*
     * Populate object in parent scope
     */
    $scope.submitForm = function (form) {
      // Trigger validation flag.
      $scope.submitted = true

      // If form is invalid, return and let AngularJS show validation errors.
      if( form.$invalid ) {
        return
      }

      // extend new channel object
      angular.extend($scope.activeChannel, $scope.formData)
      console.log('Submiting form, current dataset: ', $scope.activeChannel)
      $state.go('add.topics')
    }


  })
