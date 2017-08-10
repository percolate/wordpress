<div class="progress">
  <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="66" aria-valuemin="0" aria-valuemax="100" style="width: 66%;">
    2 / 3
  </div>
</div>

<form ng-submit="submitForm( topicsForm )" name="topicsForm" class="row topics" novalidate>
  <div class="col-sm-6 col-sm-offset-3 col-lg-4 col-lg-offset-4">


    <div class="form-group" ng-if="edit.active">
      <label for="name">Name of the channel</label>
      <input  type="text" name="name" id="name" class="form-control"
              ng-model="formData.name"
              ng-class="{ 'has-error' : setupForm.name.$invalid && (!setupForm.name.$pristine || submitted) }" required>
    </div>


    <hr>


    <!-- WP settings -->
    <h4 class="text-center">User mapping</h4>

    <div class="row form-group">
      <div class="col-sm-6">
        <label for="user">Default Wordpress user</label>
      </div>
      <div class="col-sm-6">
        <select name="wpUser" id="wpUser" class="form-control"
                ng-model="formData.wpUser"
                ng-disabled="!wpUsers"
                ng-options="option.ID as option.data.user_nicename for option in wpUsers" required></select>
      </div>
    </div>

    <div class="row form-group">
      <div class="col-sm-12">
        <h4>Selective mapping for Percolate users</h4>
      </div>
    </div>

    <div class="form-group row" ng-repeat="percolateUser in percolateUsers">
      <div class="col-sm-6">
        <label for="userMapping-{{$index}}">{{percolateUser.user.name}}</label>
      </div>
      <div class="col-sm-6">
        <select name="userMapping[percolateUser.id]"
                id="userMapping-{{$index}}"
                class="form-control"
                ng-model="formData.userMapping['user:' + percolateUser.id]">
          <option value="">Use default user</option>
          <option value="{{option.ID}}" ng-repeat="option in wpUsers">{{option.data.user_nicename}}</option>
        </select>
      </div>
    </div>

    <div ng-if="!percolateUsers">
      <p>Loading users from Percolate...</p>
    </div>

    <nav aria-label="Page navigation" ng-if="userPagination && userPagination.pages > 1" class="text-center">
      <ul class="pagination">
        <li ng-if="userPagination.prev">
          <a href="#page-{{userPagination.prev.label}}" ng-click="fetchPercolatUsers(userPagination.prev)" aria-label="Previous">
            <span aria-hidden="true">&laquo;</span>
          </a>
        </li>
        <li ng-repeat="offset in userPagination.offsets" ng-class="{'active': offset.active}">
          <a href="#page-{{offset.label}}" ng-click="fetchPercolatUsers(offset)">{{offset.label}}</a>
        </li>
        <li ng-if="userPagination.next">
          <a href="#page-{{userPagination.next.label}}#" ng-click="fetchPercolatUsers(userPagination.next)" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
          </a>
        </li>
      </ul>
    </nav>



    <hr>



    <h4 class="text-center">Configure how the posts will appear in WordPress</h4>

    <div class="form-group">
      <div class="checkbox">
        <label for="tab">
          <input  type="checkbox"
                  name="tab" id="tab"
                  ng-model="formData.tab"> Open links in new tab / window
        </label>
      </div>
    </div>

    <div class="row-group text-center" ng-show="topicsForm.$invalid && (!topicsForm.$pristine || submitted)">
        <p class="error">Please fill out all the required fields!</p>
    </div>



    <hr>



    <div class="form-group text-center">
      <button type="submit" class="btn btn-primary btn-block">Continue</button>
    </div>

  </div>
</form>

<!-- <pre>
  {{formData.userMapping}}
</pre> -->
