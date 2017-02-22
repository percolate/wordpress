<div class="progress">
  <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100" style="width: 33%;">
    1 / 3
  </div>
</div>

<div class="row">
  <div class="col-sm-12 text-center">
    <h3>Select a license / channel</h3>
  </div>
</div>

<form ng-submit="submitForm( setupForm )" name="setupForm" class="row setup" novalidate>
  <div class="col-sm-6 col-sm-offset-3 col-lg-4 col-lg-offset-4 text-center">

    <div class="form-group">
      <label for="key">First, please enter your Percolate API key</label>
      <input  type="text" name="key" id="key" class="form-control"
              ng-model="formData.key"
              ng-change="checkKey()"
              ng-class="{ 'has-error' : setupForm.key.$invalid && (!setupForm.key.$pristine || submitted) }" required>
    </div>

    <div class="form-group">
      <label for="name">Name of the channel</label>
      <input  type="text" name="name" id="name" class="form-control"
              ng-model="formData.name"
              ng-class="{ 'has-error' : setupForm.name.$invalid && (!setupForm.name.$pristine || submitted) }" required>
    </div>

    <div class="form-group">
      <label for="license">License</label>
      <select name="license" id="license" class="form-control"
              ng-model="formData.license"
              ng-disabled="!licenses"
              ng-change="changeLicense()"
              ng-options="option.id as option.name for option in licenses" required></select>
    </div>

    <div class="form-group">
      <label for="platform">Platform</label>
      <select name="platform" id="platform" class="form-control"
              ng-model="formData.platform"
              ng-disabled="!platforms"
              ng-change="changePlatform()"
              ng-options="option.id as option.name for option in platforms" required></select>
    </div>

    <div class="form-group">
      <label for="channel">Channel</label>
      <select name="channel" id="channel" class="form-control"
              ng-model="formData.channel"
              ng-disabled="!channels"
              ng-options="option.id as option.name for option in channels" required></select>
    </div>

    <div class="form-group">
      <p class="info">
        You can repeat these steps and add multiple licenses and channels later on.
      </p>
    </div>

    <div class="row-group" ng-show="setupForm.$invalid && (!setupForm.$pristine || submitted)">
        <p class="text-danger">Please fill out all the required fields!</p>
    </div>

    <div class="form-group">
      <button type="submit" class="btn btn-primary btn-block">Continue</button>
    </div>

  </div>
</form>

<!-- <pre>
  {{formData}}
</pre> -->
