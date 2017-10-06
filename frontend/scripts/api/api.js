'use strict';

angular.module('wpApi', [])
  .factory('Api', function ($http) {
    var _url = ajax_object.ajax_url

    function callApi(data) {
      return $http({
        method          : 'POST',
        url             : _url,
        headers         : {'Content-Type': 'application/x-www-form-urlencoded'},
        data            : jQuery.param(data)
      })
    }

    return {
      getTemplate: function ($tpl) {
        return callApi({ action : 'template', template: $tpl})
      },
      getData: function () {
        return callApi({ action : 'get_data'})
      },
      setData: function ($data) {
        $data.timestamp = new Date().toUTCString()
        return callApi({ action : 'set_data', data: $data})
      },
      getCategories: function () {
        return callApi({ action : 'get_categories'})
      },
      getTaxonomies: function () {
        return callApi({ action : 'get_taxonomies'})
      },
      getTerms: function () {
        return callApi({ action : 'get_terms'})
      },
      getUsers: function () {
        return callApi({ action : 'get_users'})
      },
      getCpts: function () {
        return callApi({ action : 'get_cpts'})
      },
      getPostData: function () {
        return callApi({ action : 'get_post_data'})
      },
      getAcfStatus: function () {
        return callApi({ action : 'get_acf_status'})
      },
      getAcfData: function () {
        return callApi({ action : 'get_acf_data'})
      },
      getMetaBoxStatus: function () {
        return callApi({ action : 'get_metabox_status'})
      },
      getMetaBoxData: function () {
        return callApi({ action : 'get_metabox_data'})
      },
      getWpmlStatus: function () {
        return callApi({ action : 'get_wpml_status'})
      },
      getWpmlData: function () {
        return callApi({ action : 'get_wpml_language'})
      },
      getMessages: function () {
        return callApi({ action : 'get_messages'})
      },
      setMessages: function ($data) {
        return callApi({ action : 'set_messages', data: $data})
      },
      getLog: function () {
        return callApi({ action : 'get_log'})
      },
      deleteLog: function () {
        return callApi({ action : 'delete_log'})
      },
      getQueue: function () {
        return callApi({ action : 'get_queue'})
      },
      deleteQueue: function () {
        return callApi({ action : 'delete_queue'})
      },


      /**
       * Imports image into WP */
      importImage: function (data) {
        return callApi({ action: 'image_import', data: {
            key     : data.key,
            imageKey: data.uid,
            postId  : data.postId,
            featured: data.featured,
            size    : data.size
          }
        })
      },
    }
  })
