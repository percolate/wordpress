'use strict';

angular.module('percolateMedia')
  .controller('MediaCtr', function ($scope, Percolate, Api) {

    $scope.activeItem = null
    $scope.folderTree = ['topLevel']

    $scope.key = null
    $scope.license  = null

    $scope.loader = {
       errorClass: 'error'
    }

    $scope.imageSizes = [
      {
        id: 'thumbnail',
        name: 'Thumbnail'
      },
      {
        id: 'medium',
        name: 'Medium'
      },
      {
        id: 'large',
        name: 'Large'
      }
    ]

    $scope.formData = {
      imageSize: 'thumbnail'
    }

    // ---------------------------------------

    $scope.openItem = function (item) {
      if( item.type === 'folder' ) {
        $scope.folderTree.push( item.uid )
        $scope.items = null
        $scope.activeItem = null
        listFolder(item.uid)
      } else {
        $scope.activeItem = item
      }

    }

    $scope.goBack = function () {
      $scope.folderTree.splice(-1,1)
      listFolder($scope.folderTree[$scope.folderTree.length-1])
    }

    $scope.importImage = function ( form ) {
      importImageReq({featured: false})
    }

    $scope.insertFeatured = function ( form ) {
      importImageReq({featured: true})
    }

    /*
     * Start loader */
    $scope.loader.show = function (msg) {
      $scope.loader.hasError   = false
      $scope.loader.active  = true
      $scope.loader.message = msg
      return
    },

    /*
     * Start loader */
    $scope.loader.error = function (msg) {
      $scope.loader.active  = true
      $scope.loader.hasError   = true
      $scope.loader.message = msg
      return
    },

    /*
     * Reset loader */
    $scope.loader.reset = function () {
      $scope.loader.hasError   = false
      $scope.loader.active  = false
      $scope.loader.message = ''
      return
    }

    // ---------------------------------------

    function listFolder(uid) {
      $scope.items = null
      $scope.loader.show('Listing folder...')
      if( uid === 'topLevel' ) {

        Percolate.getMediaToplevel({
          key    : $scope.key,
          fields : {
            'content_type_order': 'application%2Fvnd.percolate.library.folder',
            'order_by': 'added_at',
            'order_direction': 'order_direction',
            'index_license_id': $scope.license,
            'license_id': $scope.license,
            'hide_facets': 'true',
            'limit': 100,
            'offset': 0
          }
        })
        .then(listFolderSucess, reqError)

      } else {

        Percolate.getFolderContent({
          key    : $scope.key,
          folder : uid,
          fields : {
            'content_type_order': 'application%2Fvnd.percolate.library.folder',
            'order_by': 'added_at',
            'order_direction': 'order_direction',
            'index_license_id': $scope.license,
            'license_id': $scope.license,
            'hide_facets': 'true',
            'limit': 100,
            'offset': 0
          }
        })
        .then(listFolderSucess, reqError)

      }
    }

    function listFolderSucess(res) {
      console.log('Res', res.data)
      $scope.loader.reset()

      if( !res.data || !res.data.data ) {
        $scope.loader.error('There was an error.')
        return
      }

      $scope.items = res.data.data
      return
    }

    function reqError(err) {
      $scope.loader.reset()
      $scope.loader.error(err.statusText)
      return
    }

    function importImageReq(data) {
      $scope.loader.show('Importing image...')
      Api.importImage({
        key     : $scope.key,
        uid     : $scope.activeItem.uid,
        postId  : angular.element('#percolate-media-library').data('id'),
        featured: data.featured,
        size    : $scope.formData.imageSize
      })
      .then(function (res) {
        console.log('Res', res.data)
        $scope.loader.reset()

        if( !res.data || !res.data.id ) {
          $scope.loader.error('There was an error.')
          return
        }

        // close modal
        angular.element('#percolate-media-library .close').trigger('click')

        if( data.featured ) {
          console.log('Setting featured image: ', $scope.activeItem.src)
          var _thumbImg = angular.element('#set-post-thumbnail img')
          if( _thumbImg.length > 0 ) {
            _thumbImg.attr('src', $scope.activeItem.src)
          } else {
            angular.element('#postimagediv .inside').prepend('<p class="hide-if-no-js"><a title="Set featured image" href="" id="set-post-thumbnail" class="thickbox"><img width="266" height="164" src="'+ $scope.activeItem.src +'" class="attachment-post-thumbnail" alt=""></a></p>')
          }

        } else {
          var _html = ''
          var _alt = $scope.formData.alt ? $scope.formData.alt : ''
          var _img = '<img src="'+ res.data.src +'" alt="'+ _alt +'" />'
          if( $scope.formData.caption && $scope.formData.caption.length > 0 ) {
            _html += '[caption id="attachment_'+ res.data.id +'" align="alignnone"]'
            _html += res.data.src
            _html += ' ' + $scope.formData.caption
            _html += '[/caption]'
          } else {
            _html = res.data.src
          }
          window.send_to_editor( _html )
        }
        return

      }, reqError)
    }

    // ---------------------------------------

    $scope.loader.show('Loading data...')
    Api.getData()
      .then(function (res) {
        $scope.loader.reset()
        console.log('Getting data', res);

        if(!res.data || !res.data.settings || !res.data.settings.license ) {
          $scope.loader.error('No key was found, please set the key / license in Percolate settings.')
          return false
        }
        $scope.key = res.data.settings.key
        $scope.license = res.data.settings.license

        $scope.loader.show('Loading images...')
        return Percolate.getMediaToplevel({
          key    : $scope.key,
          fields : {
            'content_type_order': 'application%2Fvnd.percolate.library.folder',
            'order_by': 'added_at',
            'order_direction': 'order_direction',
            'index_license_id': $scope.license,
            'license_id': $scope.license,
            'hide_facets': 'true',
            'limit': 100,
            'offset': 0
          }
        })
      }, function (err) {
        $scope.loader.reset()
        $scope.loader.error(err)
        console.log(err);
      })
      .then(function (res) {
        console.log('Res', res.data)
        $scope.loader.reset()

        if( !res.data || !res.data.data ) {
          $scope.loader.error('There was an error.')
          return
        }

        $scope.items = res.data.data
        return

      }, reqError)
  })
