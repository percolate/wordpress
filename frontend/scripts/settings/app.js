'use strict';

/**
*  My app
*
* provides routing
*/
angular.module('myApp', [
    'ngAnimate',
    'ui.router',
    'wpApi',
    'wpPercolate'
  ])
  .config(function($stateProvider, $urlRouterProvider, $locationProvider) {
    $locationProvider.html5Mode({
      enabled: true,
      requireBase: false // won't work in IE9
    })

    $stateProvider
      .state('manage', {
        // url: '/wp-admin/admin.php?page=percolate-settings',
        templateProvider: function (Api) {
          return Api.getTemplate('manage-channels').then(function (res) {
            return res.data
          })
        },
        controller: 'IndexCtr'
      })

      .state('settings', {
        templateProvider: function (Api) {
          return Api.getTemplate('settings').then(function (res) {
            return res.data
          })
        },
        controller: 'SettingsCtr'
      })

      .state('log', {
        templateProvider: function (Api) {
          return Api.getTemplate('log').then(function (res) {
            return res.data
          })
        },
        controller: 'LogCtr'
      })

      .state('add', {
        templateProvider: function (Api) {
          return Api.getTemplate('new-channel').then(function (res) {
            return res.data
          })
        },
        controller: 'AddCtr'
      })
        .state('add.setup', {
          templateProvider: function (Api) {
            return Api.getTemplate('new-channel-setup').then(function (res) {
              return res.data
            })
          },
          controller: 'AddSetupCtr'
        })
        .state('add.topics', {
          templateProvider: function (Api) {
            return Api.getTemplate('new-channel-topics').then(function (res) {
              return res.data
            })
          },
          controller: 'AddTopicsCtr'
        })
        .state('add.templates', {
          templateProvider: function (Api) {
            return Api.getTemplate('new-channel-templates').then(function (res) {
              return res.data
            })
          },
          controller: 'AddTemplatesCtr'
        })
  })
  .animation('.an-reveal', function () {
    return {
      enter: function(element, done) {

        element.velocity('fadeIn', { delay: 300, duration: 600, complete: done })

        return function() {
          element.stop()
        }
      },
      leave: function(element, done) {

        element.velocity('fadeOut', { duration: 300, complete: done })

        return function() {
          element.stop()
        }
      }
    }
  })
