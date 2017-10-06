'use strict';

var PAGINATION_LIMIT = 10

angular.module('myApp')
  .controller('AddTopicsCtr', function ($scope, $state, Api, Percolate, Pagination) {
    // console.log('Add New Channel - Topics state')

    /* --------------------------------------
     * Public variables
     * -------------------------------------- */

    angular.extend($scope, {
      formData : {},
      percolateUsers : [],
      isWpmlActive : false,
      taxonomiesWP: [],
      taxonomiesPerco: [],
      termsWP: [],
      termsPerco: [],
      _: _
    })

    // Prepare form data
    if( $scope.edit.active === true ) {
      $scope.formData = $scope.edit.channel
    }

    // Edit mode
    if( $scope.edit.active && $scope.edit.channel ) {
      angular.extend($scope.activeChannel, $scope.edit.channel)
    }

    // $state.go('add.templates')

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
      // console.log('WP users', $scope.wpUsers)
    }

    function getTaxonomiesPerco(res) {
      console.log('Percolate taxonomies', res)
      if (res.data) $scope.taxonomiesPerco = res.data.data
      return setTimeout(null, 1)
    }

    function getTermsPerco(res) {
      console.log('Percolate terms', res)
      if (res.data) $scope.termsPerco = res.data.data
      processSchemas()
      if ($scope.percolateUsers) { $scope.stopLoader() }
    }

    function processSchemas() {
      if (!$scope.taxonomiesPerco) return
      $scope.taxonomiesPerco = $scope.taxonomiesPerco.map(function(taxonomy) {
        taxonomy.terms = _.filter($scope.termsPerco, function(term) {
          if (term.path_ids.indexOf(taxonomy.root_id) > -1)
            return term
        })
        return taxonomy
      })

      console.log($scope.taxonomiesPerco);

      // $scope.templates = $scope.templates.map(function(template) {
      //
      //   var taxonomyField = _.find(template.fields, {type: 'term'})
      //
      //   if (!taxonomyField || !taxonomyField.ext || !taxonomyField.ext.parent_term_ids[0])
      //    return template
      //
      //   var taxID = taxonomyField.ext.parent_term_ids[0]
      //   taxonomyField.taxonomy = _.find($scope.taxonomiesPerco, {root_id: taxID})
      //   taxonomyField.terms = _.filter($scope.termsPerco, function(term) {
      //     if (term.path_ids.indexOf(taxID) > -1)
      //       return term
      //   })
      //
      //   return template
      // })
    }

    function getTaxonomiesWP(res) {
      // console.log('WP taxonomies', res)
      if (res.data) {
        $scope.taxonomiesWP = res.data
      }
      return Api.getTerms()
    }

    function getTermsWP(res) {
      // console.log('WP terms', res)
      if (res.data) $scope.termsWP = res.data
    }




    function fetchPercolatUsers(paginationData) {
      $scope.showLoader('Loading users from Percolate...')
      $scope.percolateUsers = null
      return Percolate.getUsersByLicense({
        key    : $scope.activeChannel.key,
        license: $scope.activeChannel.license,
        fields : {
          limit: (paginationData && paginationData.limit) ? paginationData.limit : PAGINATION_LIMIT,
          offset: (paginationData && paginationData.offset) ? paginationData.offset : 0
        }
      }).then(processPercolateUsers, apiError)
    }

    function processPercolateUsers(res){
      // console.log('Percolate users: ', res)
      if ($scope.topics) $scope.stopLoader()

      if( !res.data || !res.data.data ) {
        $scope.showError('There was an error.')
        return
      }
      $scope.userPagination = Pagination.build(res.data.pagination)
      $scope.percolateUsers = res.data.data
    }


    function processWpCategoriesByLanguage () {
      $scope.categoriesByLanguage = {}
      _.each($scope.categories, function(cat) {
        if (cat.language) {
          if (!$scope.categoriesByLanguage[cat.language]) {
            $scope.categoriesByLanguage[cat.language] = []
          }
          $scope.categoriesByLanguage[cat.language].push(cat)
        }
      })

      // console.log($scope.categoriesByLanguage);
    }

    function getWpmlStatus (res) {
      // console.log('WPML status', res)
      $scope.isWpmlActive = (res.data === 'true')

      if (res) {
        processWpCategoriesByLanguage()
      }
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


    function addMapping() {
      if (!$scope.formData.taxonomyMapping) $scope.formData.taxonomyMapping = []
      $scope.formData.taxonomyMapping.push({})
    }

    function deleteMapping(key) {
      $scope.formData.taxonomyMapping.splice(key, 1)
    }

    function getTermsForTaxonomy(rootId) {
      var tax = _.find($scope.taxonomiesPerco, {root_id: rootId})
      if (tax) return tax.terms
    }

    /*
     * Populate object in parent scope
     */
    function submitForm (form) {
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
      // console.log('Submiting form, current dataset: ', $scope.activeChannel)
      $state.go('add.templates')
    }

    /* --------------------------------------
     * Bootstrap
     * -------------------------------------- */


    angular.extend($scope, {
      fetchPercolatUsers: fetchPercolatUsers,
      addMapping: addMapping,
      deleteMapping: deleteMapping,
      getTermsForTaxonomy: getTermsForTaxonomy,
      submitForm: submitForm,
    })

     // Check if we have the active User
     if( !$scope.activeChannel.user ) {
       $scope.showError('No active user found.')
       $state.go('manage')
     }

    // Get Percolate topics
    $scope.showLoader('Loading data from Percolate...')
    Percolate.getTaxonomies({
        key    : $scope.activeChannel.key,
        fields : {
          'scope_ids': 'license:' + $scope.activeChannel.license,
          'ext.platform_ids': $scope.activeChannel.platform,
        }
      })
      .then(getTaxonomiesPerco, apiError)
      .then(function () {
        // Get terms
        return Percolate.getTerms({
          key    : $scope.activeChannel.key,
          fields : {
            'scope_ids': 'license:' + $scope.activeChannel.license,
            'ext.platform_ids': $scope.activeChannel.platform,
            'mode': 'taxonomy',
            'depth': 2,
          }
        })
      }, apiError)
      .then(getTermsPerco, apiError)

    fetchPercolatUsers()

    // Get WP users
    Api.getUsers().then(processWpUsers, apiError)
    Api.getTaxonomies()
      .then(getTaxonomiesWP, apiError)
      .then(getTermsWP, apiError)


  })
  .filter('filterByTaxonomy', function () {
    return function (list, taxId) {
      return _.filter(list, function(obj){ return obj.taxonomy == taxId })
    }
  })
