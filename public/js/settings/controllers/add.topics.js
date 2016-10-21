'use strict';

angular.module('myApp')
  .controller('AddTopicsCtr', function ($scope, $state, Api, Percolate) {
    console.log('Add New Channel - Topics state')

    /* --------------------------------------
     * Public variables
     * -------------------------------------- */

    $scope.formData = {}
    // Prepare form data
    if( $scope.edit.active === true ) {
      $scope.formData = $scope.edit.channel
    }

    // Users from Percolate
    $scope.percolateUsers = []

    // Categories from WP
    $scope.categories = [
      {
        cat_name: 'Select a category...',
        term_id: ''
      }
    ]

    // Edit mode
    if( $scope.edit.active && $scope.edit.channel ) {
      angular.extend($scope.activeChannel, $scope.edit.channel)
    }


    /* --------------------------------------
     * Private methods
     * -------------------------------------- */

    function apiError (err) {
      $scope.stopLoader()
      $scope.showError(err)
      return
    }

    function processWpUsers (res) {
      $scope.wpUsers = res.data
      if( !$scope.formData.wpUser ) {
        $scope.formData.wpUser = $scope.wpUsers[0].ID
      } else {
        $scope.formData.wpUser = +$scope.formData.wpUser
      }
      console.log('WP users', $scope.wpUsers)
    }

    function processPercolateTopics (res) {
      console.log('Topics', res.data)
      if ($scope.percolateUsers) $scope.stopLoader()

      if( !res.data || !res.data.data ) {
        $scope.showError('There was an error.')
        return
      }

      return $scope.topics = res.data.data
    }

    function processWpCategories(res) {
      // var tree = _unflatten(res.data)
      $scope.categories = $scope.categories.concat(res.data)
      console.log('WP categories', $scope.categories)
    }

    function processPercolateUsers(res){
      console.log('Percolate users: ', res)
      if ($scope.topics) $scope.stopLoader()

      if( !res.data || !res.data.data ) {
        $scope.showError('There was an error.')
        return
      }

      $scope.percolateUsers = res.data.data
    }


   function _unflatten( array, parent, tree ){

      tree = typeof tree !== 'undefined' ? tree : [];
      parent = typeof parent !== 'undefined' ? parent : { id: 0 };

      var children = $scope._.filter( array, function(child){ return child.parentid == parent.id; });

      if( !$scope._.isEmpty( children )  ){
          if( parent.id == 0 ){
             tree = children;
          }else{
             parent['children'] = children
          }
          $scope._.each( children, function( child ){ _unflatten( array, child ) } );
      }

      return tree;
  }

    /* --------------------------------------
     * Public methods
     * -------------------------------------- */

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
      if( $scope.formData ) {
        angular.extend($scope.activeChannel, $scope.formData)
      }
      console.log('Submiting form, current dataset: ', $scope.activeChannel)
      $state.go('add.templates')
    }

    /* --------------------------------------
     * Bootstrap
     * -------------------------------------- */

     // Check if we have the active User
     if( !$scope.activeChannel.user ) {
       $scope.showError('No active user found.')
       $state.go('manage')
     }

    // Get Percolate topics
    $scope.showLoader('Getting data from Percolate...')
    Percolate.getTopics({
      key    : $scope.activeChannel.key,
      fields : {
        owner_uid: 'license:' + $scope.activeChannel.license
      }
    }).then(processPercolateTopics, apiError)

    Percolate.getUsersByLicense({
      key    : $scope.activeChannel.key,
      license: $scope.activeChannel.license,
      fields : {
        limit: 100,
        offset: 0
      }
    }).then(processPercolateUsers, apiError)


    // Get WP users
    Api.getUsers().then(processWpUsers, apiError)

    // Categories from WP
    Api.getCategories().then(processWpCategories, apiError)

  })
