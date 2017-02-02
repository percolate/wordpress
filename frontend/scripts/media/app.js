'use strict';

/**
*  Media app
*
*/
angular.module('percolateMedia', [
    'ngAnimate',
    'wpApi',
    'wpPercolate'
  ])
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


/* We're outside of the angular app,
 *   so let's register a handler opening the modal with jQuery
 */
jQuery(document).ready(function($) {

  var SPEED = 200
  var $activateBtn = $('#insert-percolate-button')
  var $modal = $('#percolate-media-library')
  var $backdrop = $('#percolate-backdrop')
  var $closeBtn = $('#percolate-media-library .close')

	$activateBtn.on('click', function(e) {
		e.preventDefault()
    fadeInModal()
	})

  $backdrop.on('click', function(e) {
		e.preventDefault()
    fadeOutModal()
	})

  $closeBtn.on('click', function(e) {
		e.preventDefault()
    fadeOutModal()
	})

  function fadeInModal () {
    $modal.velocity('fadeIn', { duration: SPEED })
    $backdrop.velocity('fadeIn', { duration: SPEED })
  }
  function fadeOutModal () {
    $modal.velocity('fadeOut', { duration: SPEED })
    $backdrop.velocity('fadeOut', { duration: SPEED })
  }

  // $modal.velocity('fadeIn', { duration: SPEED })
  // $backdrop.velocity('fadeIn', { duration: SPEED })

})
