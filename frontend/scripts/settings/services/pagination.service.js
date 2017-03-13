'use strict';

angular.module('myApp')
  .service('Pagination', function() {

    function build(paginationData) {
      if (!paginationData.total) { return false }
      var _pagination = {
        pages: Math.floor(paginationData.total/paginationData.limit) + 1,
        offsets: [],
        activePage: paginationData.offset / paginationData.limit,
      }

      if (_pagination.pages > 10) {
        var _start = 0
        if (_pagination.activePage - 4 > 0) {
          _start = _pagination.activePage - 4
        }

        var _end = _start + 8
        if (_end > _pagination.pages - 1) {
          _end = _pagination.pages - 1
        }

        for (var i = _start; i < _end; i++) {
           _pagination.offsets.push({
             label: i+1,
             offset: paginationData.limit * i,
             limit: paginationData.limit,
             active: paginationData.offset === paginationData.limit * i ? true : false
           })
         }

         if ( _pagination.activePage > 4) {
           _pagination.prev = {
             label: _pagination.activePage - 1,
             offset: _start,
             limit: paginationData.limit
           }
         }
         if (_pagination.activePage < _pagination.pages - 5) {
           _pagination.next = {
             label: _pagination.activePage + 1,
             offset: _end,
             limit: paginationData.limit
           }
         }

      } else {

        for (var i = 0; i <_pagination.pages; i++) {
         _pagination.offsets.push({
           label: i+1,
           offset: paginationData.limit * i,
           limit: paginationData.limit,
           active: paginationData.offset === paginationData.limit * i ? true : false
         })
        }

      }
      console.log(_pagination);
      return _pagination
    }

    return {
      build : build
    }
  })
