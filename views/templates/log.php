<div class="row">
  <div class="col-sm-8">
    <h2>Percolate Log</h2>
  </div>
  <div class="col-sm-4 text-right">
    <a class="btn btn-default" ui-sref="manage">Back</a>
  </div>
</div>

<div class="row">
  <div class="col-sm-12">
    <pre class="log" ng-bind-html="log | trustedHtml"></pre>
  </div>
</div>
