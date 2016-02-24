'use strict';

angular.module('myApp')
  .controller('AddTopicsCtr', function ($scope, $state, Api, Percolate) {
    console.log('Add New Channel - Topics state')

    if( $scope.edit.active && $scope.edit.channel )
    angular.extend($scope.activeChannel, $scope.edit.channel)

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
     * Get WP users
     */
     Api.getUsers()
       .then(function (res) {
         $scope.wpUsers = res.data
         if( !$scope.formData.wpUser ) {
            $scope.formData.wpUser = $scope.wpUsers[0].ID
          } else {
            $scope.formData.wpUser = +$scope.formData.wpUser
          }
         console.log('WP users', $scope.wpUsers)
       }, function (err) {
         $scope.showError(err)
         return
       })

    /*
     * Structure & test data for topics
     */
    $scope.showLoader('Getting topics...')
    Percolate.getTopics({
      key    : $scope.activeChannel.key,
      fields : {
        owner_uid: 'license:' + $scope.activeChannel.license
      }
    })
      .then(function (res) {
        console.log('Topics', res.data)
        $scope.stopLoader()

        if( !res.data || !res.data.data ) {
          $scope.showError('There was an error.')
          return
        }

        return $scope.topics = res.data.data
      }, function (err) {
        $scope.stopLoader()
        $scope.showError(err)
        return
      })

    /*
     * Categories from WP
     */
    $scope.categories = [
      {
        cat_name: 'Select a category...',
        term_id: ''
      }
    ]

    Api.getCategories()
      .then(function (res) {
        // var tree = unflatten(res.data)
        $scope.categories = $scope.categories.concat(res.data)
        console.log('WP categories', $scope.categories)
      }, function (err) {
        $scope.showError(err.statusText)
        return
      })

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

     function unflatten( array, parent, tree ){

        tree = typeof tree !== 'undefined' ? tree : [];
        parent = typeof parent !== 'undefined' ? parent : { id: 0 };

        var children = $scope._.filter( array, function(child){ return child.parentid == parent.id; });

        if( !$scope._.isEmpty( children )  ){
            if( parent.id == 0 ){
               tree = children;
            }else{
               parent['children'] = children
            }
            $scope._.each( children, function( child ){ unflatten( array, child ) } );
        }

        return tree;
    }
  })
