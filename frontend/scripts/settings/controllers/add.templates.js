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
      postStatuses : [
        {key: 'draft', label: 'Draft', weight: 0},
        {key: 'queued', label: 'Queued', weight: 1},
        {key: 'queued.publishing', label: 'On Schedule', weight: 2}
      ],
      postTypes : [],
      isAcfActive : false,
      acfGroups : [],
      isWpmlActive : false,
      wpmlLanguages : [],
      taxonomiesWP: [],
      taxonomiesPerco: [],
      taxonomiesPercoAll: [],
      termsWP: [],
      termsPerco: [],
      selectedTaxonomiesWP: []
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


    function getHandoffStatuses (importKey, templateId) {
      // get the valid states for handoff
      var _statusImport = _.find($scope.postStatuses, {key: importKey})
      var _statuses = []
      _.each($scope.postStatuses, function(status) {
        if (status.weight >= _statusImport.weight) {
          _statuses.push(status)
        }
      })
      // reset the handoff value, if it's earlier then import
      var _currentHandoff = _.find($scope.postStatuses, {key: $scope.formData[templateId].handoff})
      if (!_currentHandoff || (_currentHandoff && _currentHandoff.weight < _statuses[0].weight)) {
        $scope.formData[templateId].handoff = _statuses[0].key
      }
      return _statuses
    }


    function getTemplateSchemas(res) {
      console.log('Schemas', res.data)

      if( !res.data || !res.data.data ) {
        $scope.showError('There was an error.')
        return
      }

      $scope.templates = res.data.data

      $scope.templates.forEach(function(item) {
        item.taxonomiesWP = angular.copy($scope.taxonomiesWP)

        for (var taxonomy in item.taxonomiesWP) {
          if ($scope.taxonomiesWP.hasOwnProperty(taxonomy)) {
            item.taxonomiesWP[taxonomy].hasBeenSelected = false
          }
        }
        var newFields = []

        item.fields.forEach(function(field, index, object) {

          if (field.type == "term") {

            $scope.taxonomiesPercoAll.forEach(function (percTaxonomy) {

              if(field.ext.parent_term_ids.includes(percTaxonomy.root_id)) {
                if (!item.hasOwnProperty('taxonomies')) {
                  item.taxonomies = []
                }

                var showTax = angular.copy(percTaxonomy)
                showTax.showName = field.label
                showTax.keyId = field.key
                item.taxonomies.push(showTax)
              }
            })
          } else {
            newFields.push(field)
          }
        })
        item.fields = newFields;
      })

      selectedWPCategory();
      $scope.templates.forEach(function(item) {
        selectedWPCategoryTemplate(item.id);
      })
      console.log('Percolate templates', $scope.templates)
      $scope.stopLoader()
    }

    //Taxonomies

    function getTaxonomiesPerco(res) {
      //console.log('Percolate taxonomies', res)
      if (res.data) $scope.taxonomiesPercoAll = res.data.data
      return setTimeout(null, 1)
    }

    function getTermsPerco(res) {
      //console.log('Percolate terms', res)
      if (res.data) $scope.termsPerco = res.data.data
      processSchemas()
      if ($scope.percolateUsers) { $scope.stopLoader() }
    }

    function processSchemas() {
      if (!$scope.taxonomiesPercoAll) return
      $scope.taxonomiesPercoAll = $scope.taxonomiesPercoAll.map(function(taxonomy) {
        taxonomy.terms = _.filter($scope.termsPerco, function(term) {
          if (term.path_ids.indexOf(taxonomy.root_id) > -1)
            return term
        })
        return taxonomy
      })
    }

    function getMetadataCreative() {
      $scope.showLoader('Loading metadatas...')
      Percolate.getTemplateSchemas({
        key    : $scope.activeChannel.key,
        fields : {
          'scope_ids': 'license:' + $scope.activeChannel.license,
          'type': 'metadata'
        }
      })
        .then(getMetadataPerco, apiError)

    }

    function getMetadataPerco(res) {
      //console.log("Percolate all taxonomies", $scope.taxonomiesPercoAll);
      $scope.taxonomiesPerco = []
      res.data.data.forEach(function(item) {
        if(item.status == "active") {
          item.fields.forEach(function(field) {
            if((!field.deprecated) && (field.type == "term")) {
              field.assignedTaxonomy = null;
              field.assignedTaxonomyRootId = null;
              $scope.taxonomiesPercoAll.forEach(function (percoTax) {
                field.ext.parent_term_ids.forEach(function(parentId) {
                  if(percoTax.root_id == parentId) {
                    field.assignedTaxonomy = percoTax;
                    field.assignedTaxonomyRootId = percoTax.root_id;
                  }
                })

              })

              $scope.taxonomiesPerco.push(field);
            }
          })
        }
      })

      //console.log('Percolate taxonomies defined in Creative detail', $scope.taxonomiesPerco)

      if ($scope.taxonomiesPerco) { $scope.stopLoader() }
    }
    // Get Percolate topics
    $scope.showLoader('Loading taxonomies from Percolate...')
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
      .then(getMetadataCreative, apiError)



    function getTaxonomiesWP(res) {
      if (res.data) {
        $scope.taxonomiesWP = res.data

        for (var item in $scope.taxonomiesWP) {
          if ($scope.taxonomiesWP.hasOwnProperty(item)) {
            $scope.taxonomiesWP[item].hasBeenSelected = false
          }
        }
      }
      return Api.getTerms()
    }

    function getTermsWP(res) {
      if (res.data) $scope.termsWP = res.data
    }
    //#Taxonomies

    function getCpts (res) {
      // console.log('Post types', res.data)
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
        // console.log('ACF is found')
        $scope.isAcfActive = true
        return Api.getAcfData()
      }
    }


    function getAcfData (res) {
      if( $scope.isAcfActive ) {
        // console.log('ACF data', res)

        $scope.acfGroups = res.data.groups
        $scope.acfFields = res.data.fields

        // Set default valus if new channel
        if( !$scope.edit.active ) {
          $scope._.each($scope.templates, function(obj) {
            $scope.formData[obj.id].acfSet = 'false'
          })
        }
      }
    }


    function getWpmlStatus (res) {
      $scope.isWpmlActive = (res.data === 'true')
      return Api.getMetaBoxStatus()
    }

    function getMetaBoxStatus (res) {
      $scope.isMetaBoxActive = (res.data === '1')
      return Api.getMetaBoxData()
    }

    function getMetaBoxData (res) {
      //console.log("Percolate metadboxdata: ", res)
      if ($scope.isMetaBoxActive) {
        $scope.metaboxGroups = res.data.groups
      }
      return
    }


    function setData (res) {
      $scope.stopLoader()
      if(!res.data.success) {
        // console.log('Error while saving to DB', res)
        $scope.showError('Error while saving to DB.')
      } else {
        // console.log('Data saved', res)
        // reset the new channel object
        $scope.activeChannel = {}
        // all done here
        $state.go('manage')
      }
    }

    function addMapping() {
      if (!$scope.formData.taxonomyMapping) $scope.formData.taxonomyMapping = []
      $scope.formData.taxonomyMapping.push({})
    }

    function addMappingTemplate(templateId, taxonomies) {
      if(taxonomies == null) {
        return;
      }
      if (!$scope.formData[templateId].taxonomyMapping) $scope.formData[templateId].taxonomyMapping = []
      $scope.formData[templateId].taxonomyMapping.push({})
    }

    function deleteMapping(key) {
      $scope.formData.taxonomyMapping.splice(key, 1)
      selectedWPCategory()
    }

    function deleteMappingTemplate(key, templateId) {
      $scope.formData[templateId].taxonomyMapping.splice(key, 1)
      selectedWPCategoryTemplate(templateId, key)
    }

    function getTermsForTaxonomy(rootId) {
      var tax = _.find($scope.taxonomiesPercoAll, {root_id: rootId})
      if (tax) return tax.terms
    }

    function selectedWPCategory() {
      for (var item in $scope.taxonomiesWP) {
        if ($scope.taxonomiesWP.hasOwnProperty(item)) {
          $scope.taxonomiesWP[item].hasBeenSelected = false

          if ($scope.formData.taxonomyMapping) {
            $scope.formData.taxonomyMapping.forEach(function (index) {
              if (item == index['taxonomyWP']) {
                $scope.taxonomiesWP[item].hasBeenSelected = true
              }
            })
          }


        }
      }
      //console.log("TaxonomiesWP - ", $scope.taxonomiesWP);
    }

    function selectedWPCategoryTemplate(templateId) {
      var template = $scope.templates.filter(function(item) {
        return item.id === templateId;
      })[0];

        for (var item in template.taxonomiesWP) {
          if (template.taxonomiesWP.hasOwnProperty(item)) {
            template.taxonomiesWP[item].hasBeenSelected = false

            if (($scope.formData[templateId]) && ($scope.formData[templateId].taxonomyMapping)) {
              $scope.formData[templateId].taxonomyMapping.forEach(function (index) {
                if (item == index['taxonomyWP']) {
                  template.taxonomiesWP[item].hasBeenSelected = true
                }
              })
            }


          }
      }
    }

    function selectedPercoTemplate(templateID, key) {
      var keyId = $scope.formData[templateID].taxonomyMapping[key]['taxonomyPercoKey'];

      $scope.templates.forEach(function(template) {
        if(template.id == templateID) {
          template.taxonomies.forEach(function(taxonomy) {
            if(taxonomy.keyId == keyId) {
              $scope.formData[templateID].taxonomyMapping[key]['taxonomyPerco'] = taxonomy.root_id;
            }
          })
        }
      })
    }

    function selectedPerco(key) {
      var keyId = $scope.formData.taxonomyMapping[key]['taxonomyPercoKey'];

      $scope.taxonomiesPerco.forEach(function (taxonomy) {
        if(taxonomy.key == keyId) {
          $scope.formData.taxonomyMapping[key]['taxonomyPerco'] = taxonomy.assignedTaxonomy.root_id;
        }
      })
    }

    function disableWPCategory(option, selected) {
      var retVal = false;
      if(option.hasBeenSelected) {
        retVal = true
        if(option.name == selected) {
          retVal = false
        }
      }

      return retVal
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
    Api.getWpmlStatus()
      .then(getWpmlStatus, apiError)
      .then(getMetaBoxStatus, apiError)
      .then(getMetaBoxData, apiError)

    /**
     * Exports
     */
    angular.extend($scope, {
      addMapping: addMapping,
      addMappingTemplate: addMappingTemplate,
      deleteMapping: deleteMapping,
      deleteMappingTemplate: deleteMappingTemplate,
      getTermsForTaxonomy: getTermsForTaxonomy,
      submitForm : submitForm,
      getHandoffStatuses : getHandoffStatuses,
      selectedWPCategory: selectedWPCategory,
      selectedWPCategoryTemplate: selectedWPCategoryTemplate,
      selectedPercoTemplate: selectedPercoTemplate,
      selectedPerco: selectedPerco,
      disableWPCategory: disableWPCategory
    })

    Api.getTaxonomies()
      .then(getTaxonomiesWP, apiError)
      .then(getTermsWP, apiError)
  })
  .filter('safeName', function () {
    return function (value) {
        return (!value) ? '' :  escape(value.replace(/ /g, '').replace(/'/g, 'A'))
    }
  })
  .filter('filterType', function () {
    return function (list, type) {
      if(Array.isArray(type)) {
        return _.filter(list, function(obj){ return type.indexOf(obj.type) > -1 })
      }
      return _.filter(list, function(obj){ return obj.type === type })
    }
  })
  .filter('filterByTaxonomy', function () {
    return function (list, taxId) {
      return _.filter(list, function(obj){ return obj.taxonomy == taxId })
    }
  })