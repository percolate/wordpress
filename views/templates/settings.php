<div class="row">
  <div class="col-sm-8">
    <h2>Settings</h2>
  </div>
  <div class="col-sm-4 text-right">
    <a class="btn btn-default" ui-sref="manage">Cancel</a>
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

<!-- LOGS -->

<hr>

<div class="row">
  <div class="col-sm-8">
    <h2>Percolate Log</h2>
  </div>
</div>

<div class="row">
  <div class="col-sm-12">
    <pre class="log" ng-bind-html="log | trustedHtml"></pre>
  </div>
</div>

<div class="row">
  <div class="col-sm-12">
    <a href="" class="btn" ng-click="refreshLog()">Refresh log</a>
    <a href="" class="btn" ng-click="deleteLog()">Clear log</a>
  </div>
</div>

<hr>

<p>
  <small>Plugin Version 4.0.5</small>
</p>
