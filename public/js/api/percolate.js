'use strict';

angular.module('wpPercolate', [])
  .factory('Percolate', function ($http) {
    var _url = ajax_object.ajax_url

    return {
      /* ------------------------------------
       * Setup screen
       * ------------------------------------ */
      getUser: function (data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'call_percolate', data: {
            key     : data.key,
            method  : 'v3/me'
          }})
        })
      },
      getLicenses: function (data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'call_percolate', data: {
            key     : data.key,
            method  : 'v3/licenses',
            fields  : data.fields
          }})
        })
      },
      getChannelsOld: function (data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'call_percolate', data: {
            key     : data.key,
            method  : 'v4/license_channel/',
            fields  : data.fields
          }})
        })
      },

      /**
       * V5: Get the platform ID for selecting the right templates */
      getPlatforms: function (data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'call_percolate', data: {
            key     : data.key,
            method  : 'v5/platform/',
            fields  : data.fields
          }})
        })
      },
      /**
       * V5: Get the channel ID for selecting the right templates */
      getChannels: function (data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'call_percolate', data: {
            key     : data.key,
            method  : 'v5/channel/',
            fields  : data.fields
          }})
        })
      },

      /* ------------------------------------
       * Topics screen
       * ------------------------------------ */

      /**
       * Get the channel ID for selecting the right templates */
      getTopics: function (data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'call_percolate', data: {
            key     : data.key,
            method  : 'v4/tag/',
            fields  : data.fields
          }})
        })
      },

      /* ------------------------------------
       * Templates screen
       * ------------------------------------ */

      /**
       * Get the channel ID for selecting the right templates */
      getTemplateSchemas: function (data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'call_percolate', data: {
            key     : data.key,
            method  : 'v5/schema/',
            fields  : data.fields
          }})
        })
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
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'call_percolate', data: {
            key     : data.key,
            method  : 'v3/media',
            fields  : data.fields
          }})
        })
      },

      /**
       * List folder content */
      getFolderContent: function (data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'call_percolate', data: {
            key     : data.key,
            method  : 'v3/media/' + data.folder,
            fields  : data.fields
          }})
        })
      },
    }
  })
