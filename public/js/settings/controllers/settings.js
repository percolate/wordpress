'use strict';

angular.module('myApp')
  .controller('SettingsCtr', function ($scope, Api, $state, Percolate) {
    console.log('Settings state');

    /*
     * Check the API key and retrieve data from Percolata
     */
    $scope.checkKey = function() {
      if($scope.formData.key && $scope.formData.key.length === 40) {
        console.info('API key has been entered, looking for User...')
        $scope.resetError()
        $scope.showLoader('Searching for the user in Percolate...')
        Percolate.getUser({key: $scope.formData.key})
          .then(function (res) {
            $scope.stopLoader()

            if( res.data ) {
              $scope.userFound = true
              $scope.formData.user = res.data
            } else {
              $scope.userFound = false
              $scope.showError('No user found.')
              return
            }
            $scope.resetError()
            $scope.showLoader('Loading licenses...')
            return Percolate.getLicenses({
              key    : $scope.formData.key,
              fields : {
                user_id: $scope.formData.user.id,
                limit: 100
              }
            })

          }, function (err) {
            $scope.stopLoader()
            $scope.showError(err)
            return
          })
          .then(function (res) {
            $scope.stopLoader()
            if( !res.data || !res.data.data ) {
              $scope.showError('There was an error.')
              return
            }

            $scope.licenses = res.data.data
            if( !$scope.formData.license ) {
              $scope.formData.license = $scope.licenses[0].id
            } else {
              $scope.formData.license = +$scope.formData.license
            }
            return

          }, function (err) {
            $scope.stopLoader()
            $scope.showError(err.statusText)
            return
          })
      }
    }

    $scope.submitForm = function (form) {
      // Trigger validation flag.
      $scope.submitted = true

      // If form is invalid, return and let AngularJS show validation errors.
      if( form.$invalid ) {
        return
      }

      // extend new channel object
      $scope.Percolate.settings = $scope.formData

      $scope.showLoader('Saving data...')
      console.log('Submiting data, current dataset: ', $scope.Percolate)

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


    if( $scope.Percolate.settings ) {
      $scope.formData = $scope.Percolate.settings
      $scope.checkKey()
    }

  })
