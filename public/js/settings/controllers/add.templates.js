'use strict';

angular.module('myApp')
  .controller('AddTemplatesCtr', function ($scope, $state, UUID, Api, Percolate) {
    console.log('Add New Channel - Templates state')

    // Check if we have the active User
    if( !$scope.activeChannel.user ) {
      $scope.showError('No active user found.')
      $state.go('manage')
    }

    angular.extend($scope, {
      formData : {},
      earliestImport : [
        {key: 'draft', label: 'Draft'},
        {key: 'queued', label: 'Queued'},
        {key: 'queued.publishing', label: 'On Schedule'}
      ],
      postTypes : [],
      isAcfActive : false,
      acfGroups : [],
      isWpmlActive : false,
      wpmlLanguages : [],
    })

    // Prepare form data
    if( $scope.edit.active === true ) {
      $scope.formData = $scope.edit.channel
    }


    function apiError (err) {
      $scope.stopLoader()
      $scope.showError(err)
      return
    }


    function getTemplateSchemas(res) {
      console.log('Schemas', res.data)
      $scope.stopLoader()

      if( !res.data || !res.data.data ) {
        $scope.showError('There was an error.')
        return
      }

      $scope.templates = res.data.data

      return
    }


    function getCpts (res) {
      console.log('Post types', res.data)
      delete res.data.page
      delete res.data.attachment
      $scope.postTypes = res.data

      // Set default valus if new channel
      if( !$scope.edit.active ) {
        $scope._.each($scope.templates, function(obj) {
          $scope.formData[obj.id].postType = ''
        })
      }
      return Api.getAcfStatus()
    }


    function getAcfStatus (res) {
      if(res.data === '1') {
        console.log('ACF is found')
        $scope.isAcfActive = true
        return Api.getAcfData()
      }
    }


    function getAcfData (res) {
      if( $scope.isAcfActive ) {
        console.log('ACF data', res)

        $scope.acfGroups = res.data.groups
        $scope.acfFields = res.data.fields

        // Set default valus if new channel
        if( !$scope.edit.active ) {
          $scope._.each($scope.templates, function(obj) {
            $scope.formData[obj.id].acfSet = 'false'
          })
        }
      }
      return Api.getWpmlStatus()
    }


    function getWpmlStatus (res) {
      console.log('WPML status', res)
      $scope.isWpmlActive = res
    }


    function setData (res) {
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
    }



    function submitForm (form) {
      /**
       * Populate data object in parent scope
       */

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
        .then(setData, apiError)
    }





    /**
     * Get the templates from Percolate
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
      .then(getTemplateSchemas, apiError)


    /**
     * Populate the form fields with data from WP and Percolate
     */
    Api.getCpts()
      .then(getCpts, apiError)
      .then(getAcfStatus, apiError)
      .then(getAcfData, apiError)
      .then(getWpmlStatus, apiError)

    /**
     * Exports
     */
    angular.extend($scope, {
      submitForm : submitForm
    })

  })
  .filter('safeName', function () {
    return function (value) {
        return (!value) ? '' :  escape(value.replace(/ /g, '').replace(/'/g, 'A'))
    }
  })
  .filter('filterType', function () {
    return function (list, type) {
      return _.filter(list, function(obj){ return obj.type === type })
    }
  })
