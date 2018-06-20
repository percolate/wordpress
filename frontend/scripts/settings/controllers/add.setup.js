'use strict';

angular.module('myApp')
  .controller('AddSetupCtr', function ($scope, $state, Percolate) {
    console.log('Add New Channel - Setup state')

    $scope.formData = {
      active: 'true'
    }

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
      var fd = angular.extend({}, $scope.formData);          
      fd.license = fd.license.trim().replace('license:','');      
      fd.platform = fd.platform.trim();
      fd.channel = fd.channel.trim();

      if (fd.platform.indexOf('platform:') != 0)
        fd.platform = 'platform:' + fd.platform;
      if (fd.channel.indexOf('channel:') != 0)
        fd.channel = 'channel:' + fd.channel;

      $scope.showLoader('Please wait while we verify the configuration...')
      Percolate.getUserV5({key: fd.key})
        .then(d => {
          if (!d.data || !d.data) {
            $scope.stopLoader()
            $scope.showError("Invalid API Key");
            return;
          }          
          fd.user = d.data;

          console.log("API Key validated",d);
          return Percolate.getLicenseV5({key: fd.key, license: fd.license})
        },err => { 
          $scope.stopLoader()
          $scope.showError("Invalid API Key");
        })
        .then(d => {
          if (!d) return;
          if (!d.data || !d.data.data) {
            $scope.stopLoader()
            $scope.showError("Invalid License ID");
            return;
          }
          
          console.log("License validated",d);
          return Percolate.getPlatformV5({key: fd.key, platform: fd.platform})
        },err => { 
          $scope.stopLoader()
          $scope.showError("Invalid License ID");
        })
        .then(d => {
          if (!d) return;
          if (!d.data || !d.data.data) {
            $scope.stopLoader()
            $scope.showError("Invalid Platform ID");
            return;
          }
          
          console.log("platform validated",d);
          return Percolate.getChannelV5({key: fd.key, channel: fd.channel})
        },err => { 
          $scope.stopLoader()
          $scope.showError("Invalid Platform ID");
        })
        .then(d => {
          if (!d) return;
          if (!d.data || !d.data.data) {
            $scope.stopLoader()
            $scope.showError("Invalid Channel ID");
            return;
          }
          
          console.log("channel validated",d);

          $scope.stopLoader();
          
          // extend new channel object
          angular.extend($scope.activeChannel, fd);
          console.log('Submiting form, current dataset: ', $scope.activeChannel)
          $state.go('add.topics')
        },err => { 
          $scope.stopLoader()      
          $scope.showError("Invalid channel ID");
        });
       
    }


  })
