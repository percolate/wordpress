'use strict';

angular.module('wpApi', [])
  .factory('Api', function ($http) {
    var _url = ajax_object.ajax_url

    return {
      getTemplate: function ($tpl) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'template', template: $tpl})
        })
      },
      getData: function () {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'get_data'})
        })
      },
      setData: function ($data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'set_data', data: $data})
        })
      },
      getCategories: function ($data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'get_categories'})
        })
      },
      getUsers: function ($data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'get_users'})
        })
      },
      getCpts: function ($data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'get_cpts'})
        })
      },
      getPostData: function ($data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'get_post_data'})
        })
      },
      getAcfStatus: function ($data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'get_acf_status'})
        })
      },
      getAcfData: function ($data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'get_acf_data'})
        })
      },
      getMessages: function () {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'get_messages'})
        })
      },
      setMessages: function ($data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'set_messages', data: $data})
        })
      },
      getLog: function () {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'get_log'})
        })
      },
      deleteLog: function () {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action : 'delete_log'})
        })
      },

      /**
       * Imports image into WP */
      importImage: function (data) {
        return $http({
          method          : 'POST',
          url             : _url,
          headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
          data            : jQuery.param({ action: 'image_import', data: {
            key     : data.key,
            imageKey: data.uid,
            postId  : data.postId,
            featured: data.featured,
            size    : data.size
          }})
        })
      },
    }
  })
