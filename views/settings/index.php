<div id="percolate-app" class="percolate-app" ng-app="myApp" ng-controller="MainCtr" ng-cloak>
  <loader></loader>

  <div class="container-fluid">

    <div class="row perc-header">
      <div class="col-sm-6">
        <h1>Percolate WordPress Importer</h1>
      </div>
      <div class="col-sm-6 text-right">
        <a ui-sref="settings" class="btn btn-default">Settings</a>
      </div>
    </div>

    <div class="row perc-header hidden" ng-show="messages">
      <div class="col-sm-12">
        <div ng-repeat="message in messages.warning" class="alert alert-warning" role="alert">
          <button type="button" class="close" aria-label="Close" ng-click=dismissMessage($index)><span aria-hidden="true">&times;</span></button>
          <strong>Warning!</strong> {{message.message}}
        </div>
      </div>
    </div>

    <div class="row perc-header">
      <div class="col-sm-12">
        <hr>
      </div>
    </div>

    <div class="alert alert-danger ng-hide" role="alert" ng-show="error.active">{{error.message}}</div>

    <section class="main-view an-reveal" ui-view></section>

  </div>
</div>
