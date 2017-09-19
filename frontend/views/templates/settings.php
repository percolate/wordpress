<div class="row">
  <div class="col-sm-8">
    <h2>Settings</h2>
  </div>
  <div class="col-sm-4 text-right">
    <a class="btn btn-default" ui-sref="manage">Back</a>
  </div>
</div>

<form ng-submit="submitForm( settingsForm )" name="settingsForm" class="settings" novalidate>
    <div class="form-group">
      <h3>Media Libary</h3>
    </div>

    <div class="row">
      <div class="col-sm-8 col-lg-6">

        <div class="row form-group" ng-class="{ 'has-error' : !userFound && (!settingsForm.key.$pristine || submitted), 'has-success' : userFound }">
          <div class="col-sm-4">
            <label for="key">Percolate API key</label>
          </div>
          <div class="col-sm-8">
              <input  type="text" name="key" id="key" class="form-control"
                      ng-model="formData.key"
                      ng-change="checkKey()">
          </div>
        </div>

        <div class="row form-group">
          <div class="col-sm-4">
            <label for="license">Licence</label>
          </div>
          <div class="col-sm-8">
            <select name="license" id="license" class="form-control"
                    ng-model="formData.license"
                    ng-disabled="!licenses"
                    ng-options="option.id as option.name for option in licenses" required></select>
          </div>
        </div>

      </div>
    </div>

    <div class="row-group" ng-show="settingsForm.$invalid && (!settingsForm.$pristine || submitted)">
      <p class="error">Please fill out all the required fields!</p>
    </div>

    <div class="form-group">
      <button type="submit" class="btn btn-primary">Submit</button>
    </div>

  </div>
</form>

<hr>

<div class="row">
  <div class="col-sm-12">
    <h3>Transition Queue</h3>
    <pre class="log">
      <span ng-repeat="item in queue.postToTransition">
        ID: {{item.ID}}
        ID Percolate: {{item.idPerc}}
        status Percolate: {{item.statusPerc}}
        status WP: {{item.statusWP}}
        sync: {{item.sync}}
        date UTM: {{item.dateUTM}}
        ------------------------
      </span>
    </pre>
  </div>
</div>

<div class="row">
  <div class="col-sm-12">
    <a href="" class="btn" ng-click="refreshQueue()">Refresh queue</a>
    <a href="" class="btn" ng-click="deleteQueue()">Clear queue</a>
  </div>
</div>


<hr>

<p>
  <small>
    Plugin version: 4.x-1.2.5 (<i>Supported WordPress version - Plugin version</i>)<br>
    PHP >= 5.6 required for the plugin to function properly
  </small>
</p>
