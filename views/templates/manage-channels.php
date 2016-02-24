<div class="row">
  <div class="col-sm-12">
    <h2>Manage Channels</h2>
  </div>
</div>

<div class="row">
  <div class="col-sm-12 text-right">
    <table class="table table-striped table-hover">
      <tbody>
        <tr ng-repeat="(uuid, channel) in Percolate.channels" data-uuid="{{uuid}}">
          <td class="text-left">
            <h4>{{channel.name}}</h4>
          </td>
          <td class="text-right">
            <a href="" ng-click="deleteChannel(uuid)" class="btn btn-default">Delete</a>
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
  </div>
</div>
