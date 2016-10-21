<div class="row">
  <div class="col-sm-12">
    <h2>Manage Channels</h2>
  </div>
</div>

<div class="row">
  <div class="col-sm-12 text-right">
    <table class="table table-striped table-hover">
      <tbody>
        <tr ng-repeat="(uuid, channel) in Percolate.channels" data-uuid="{{uuid}}" ng-show="channel.active == 'true' || showAll == true">
          <td class="text-left">
            <h4>{{channel.name}}<span ng-show="channel.active == 'false'"> <i>(trashed)</i></span></h4>
          </td>
          <td class="text-right">
            <a href="" ng-click="deleteChannel(uuid, channel.active == 'false' ? true : false)" class="btn btn-default">Delete<span ng-show="channel.active == 'false'"> trashed</span></a>
            <a href="" ng-click="restoreChannel(uuid)" class="btn btn-default" ng-show="channel.active == 'false'">Restore</a>
            <a href="" ng-click="editChannel(uuid)" class="btn btn-default">Edit</a>
            <a href="" ng-click="importChannel(uuid)" class="btn btn-success">Import</a>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="row">
  <div class="col-sm-12">
    <a ui-sref="add.setup" class="btn btn-primary">Add New</a>
    <a href="" class="btn" ng-init="showAll = false" ng-click="showAll = !showAll"><span ng-show="showAll">Hide</span><span ng-show="!showAll">Show</span> Deleted</a>
  </div>
</div>
