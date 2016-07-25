'use strict';

angular.module('myApp')
  .controller('AddTemplatesCtr', function ($scope, $state, UUID, Api, Percolate) {
    console.log('Add New Channel - Templates state')

    // Check if we have the active User
    if( !$scope.activeChannel.user ) {
      $scope.showError('No active user found.')
      $state.go('manage')
    }

    // Prepare form data
    $scope.formData = {}
    if( $scope.edit.active === true ) {
      $scope.formData = $scope.edit.channel
    }

    /**
     * Data for templates
     */
    $scope.showLoader('Loading schemas...')
    Percolate.getTemplateSchemas({
      key    : $scope.activeChannel.key,
      fields : {
        'scope_ids': 'license:' + $scope.activeChannel.license,
        'ext.platform_ids': $scope.activeChannel.platform,
        'type': 'post'
      }
    })
      .then(function (res) {
        console.log('Schemas', res.data)
        $scope.stopLoader()

        if( !res.data || !res.data.data ) {
          $scope.showError('There was an error.')
          return
        }

        $scope.templates = res.data.data

        return

      }, function (err) {
        $scope.stopLoader()
        $scope.showError(err.statusText)
        return
      })


    /**
     * Earliest import
     */
    $scope.earliestImport = [
      {key: 'draft', label: 'Draft'},
      {key: 'queued', label: 'Queued'},
      {key: 'queued.publishing', label: 'On Schedule'}
    ];

    /**
     * Post types
     */
    $scope.postTypes = [{
      label: "Don't import",
      name: 'false'
    }]

    /**
     * ACF groups
     */
    $scope.acfGroups = [{
      post_title: "Don't map",
      ID: 'false'
    }]

    /**
     * Populate fields
     */
    Api.getCpts()
      .then(function (res) {
        console.log('Post types', res.data)
        delete res.data.page
        delete res.data.attachment
        $scope._.each(res.data, function (obj) {
          $scope.postTypes.push(obj)
        })
        // Set default valus if new channel
        if( !$scope.edit.active ) {
          $scope._.each($scope.templates, function(obj) {
            $scope.formData[obj.id].postType = ''
          })
        }
        return Api.getAcfStatus()
      })
      .then(function (res) {
        if(res.data === '1') {
          console.log('ACF is found')
          $scope.isAcfActive = true
          return Api.getAcfData()
        }
      })
      .then(function (res) {
        if( $scope.isAcfActive ) {
          console.log('ACF data', res)

          $scope.acfGroups = $scope.acfGroups.concat(res.data.groups)
          $scope.acfFields = res.data.fields

          // Set default valus if new channel
          if( !$scope.edit.active ) {
            $scope._.each($scope.templates, function(obj) {
              $scope.formData[obj.id].acfSet = 'false'
            })
          }
        }
      })

    /**
     * Populate object in parent scope
     */
    $scope.submitForm = function (form) {
      // Trigger validation flag.
      $scope.submitted = true

      // If form is invalid, return and let AngularJS show validation errors.
      if( form.$invalid ) {
        return
      }

      $scope._.each($scope.templates, function(obj) {
        // Check if the template has a post type set
        if($scope.formData[obj.id] && $scope.formData[obj.id].postType && !$scope.formData[obj.id].postType != 'false') {
          if(!$scope.formData[obj.id].postTitle) {
            $scope.showError('You need to specify post title for every template that has a post type set.')
            return
          }
        }
      })

      // extend new channel object
      angular.extend($scope.activeChannel, $scope.formData)
      // store data in the main scope
      if( $scope.edit.active === true ) {
        $scope.Percolate.channels[$scope.edit.channelId] = $scope.activeChannel
      } else {
        var _uuid = UUID.generate()
        $scope.activeChannel.uuid = _uuid
        $scope.Percolate.channels[_uuid] = $scope.activeChannel
      }

      $scope.showLoader('Saving data...')
      console.log('Submiting data, current dataset: ', $scope.Percolate)

      Api.setData($scope.Percolate)
        .then(function (res) {
          $scope.stopLoader()
          if(!res.data.success) {
            console.log('Error while saving to DB', res)
            $scope.showError('Error while saving to DB.')
          } else {
            console.log('Data saved', res)
            // reset the new channel object
            $scope.activeChannel = {}
            // all done here
            $state.go('manage')
          }
        }, function (err) {
          $scope.stopLoader()
          $scope.showError(err)
        })
    }

  })
  .filter('safeName', function () {
    return function (value) {
        return (!value) ? '' :  escape(value.replace(/ /g, '').replace(/'/g, 'A'))
    }
  })
  .filter('filterAsset', function () {
    return function (list) {
      return _.filter(list, function(obj){ return obj.type === 'asset' })
    }
  })
