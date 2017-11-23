'use strict'

angular.module('wpPercolate', [])
  .factory('Percolate', function ($http) {
    var _url = ajax_object.ajax_url

    function callApi (method, data) {
      var payload = {
        key     : data.key,
        method  : method,
      }
      if (data.fields) {
        payload.fields = data.fields
      }

      return $http({
        method          : 'POST',
        url             : _url,
        headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
        data            : jQuery.param({ action: 'call_percolate', data: payload})
      })
    }

    return {
      /* ------------------------------------
       * Setup screen
       * ------------------------------------ */
      getUser: function (data) {
        return callApi('v3/me', data)
      },
      getLicenses: function (data) {
        return callApi('v3/licenses', data)
      },
      getChannelsOld: function (data) {
        return callApi('v4/license_channel/', data)
      },

      /**
       * V5: Get the platform ID for selecting the right templates */
      getPlatforms: function (data) {
        return callApi('v5/platform/', data)
      },
      /**
       * V5: Get the channel ID for selecting the right templates */
      getChannels: function (data) {
        return callApi('v5/channel/', data)
      },

      /* ------------------------------------
       * Topics screen
       * ------------------------------------ */

      /**
       * Get the channel ID for selecting the right templates */
      getTopics: function (data) {
        return callApi('v4/tag/', data)
      },

      /**
       * Get user role by license
       */
      getUsersByLicense: function (data){
        return callApi('v3/licenses/' + data.license + '/users', data)
      },

      /* ------------------------------------
       * Templates screen
       * ------------------------------------ */

      /**
       * Get the channel ID for selecting the right templates */
      getTemplateSchemas: function (data) {
        return callApi('v5/schema/', data)
      },

      /**
       * Get the v5 taxonomies */
      getTaxonomies: function (data) {
        return callApi('v5/taxonomy/', data)
      },

      /**
       * Get the v5 terms */
      getTerms: function (data) {
        return callApi('v5/term/', data)
      },

      /**
       * Get the v5 metadata */
      getMetadata: function (data) {
        return callApi('v5/metadata/', data)
      },


      /* ------------------------------------
       * Importing posts
       * ------------------------------------ */
      doImport: function (uuid) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'do_import', data: uuid })
        })
      },


      /* ------------------------------------
       * Media Library
       * ------------------------------------ */

      /**
       * Get the channel ID for selecting the right templates */
      getMediaToplevel: function (data) {
        return callApi('v3/media', data)
      },

      /**
       * List folder content */
      getFolderContent: function (data) {
        return callApi('v3/media/' + data.folder, data)
      },

    }
  })
