<div class="row">
  <div class="col-sm-8">
    <h2 ng-if="!edit.active">Add New Channel</h2>
    <h2 ng-if="edit.active">Edit Channel: {{edit.channel.name}}</h2>
  </div>
  <div class="col-sm-4 text-right">
    <a class="btn btn-default" ui-sref="manage">Cancel</a>
  </div>
</div>
<div ui-view></div>
