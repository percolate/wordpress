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

    <div class="row">
      <div class="col-sm-12 text-center">
        <h3>Map topics / subtopics</h3>
        <p class="info">Here you can map your Percolate topics and subtopics to WordPress categories</p>
      </div>
    </div>

    <!-- Main topics -->
    <div class="main-topic" ng-repeat="topic in topics">
      <div class="row form-group">
        <div class="col-sm-4">
          <label for="license">{{topic.name}}</label>
        </div>
        <div class="col-sm-8">
          <select name="{{topic.id}}" id="{{topic.id}}" class="form-control"
                  ng-model="formData.topics[topic.id]",
                  ng-init="formData.topics[topic.id] = (edit.active && +formData.topics[topic.id]) ? +formData.topics[topic.id] : categories[0].term_id"
                  ng-options="option.term_id as option.cat_name for option in categories"></select>
        </div>
      </div>

      <!-- Sub topics -->
      <div class="subtopics" ng-repeat="subtopic in topic.subtopics">
        <div class="row form-group">
          <div class="col-sm-4">
            <label for="license">– {{subtopic.name}}</label>
          </div>
          <div class="col-sm-8">
            <select name="{{subtopic.id}}" id="{{subtopic.id}}" class="form-control"
                  ng-model="formData.topics[subtopic.id]"
                  ng-init="formData.topics[subtopic.id] =  edit.active ? formData.topics[subtopic.id] : categories[0].term_id"
                  ng-options="option.term_id as option.cat_name for option in categories"></select>
          </div>
        </div>
      </div>

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
  {{formData}}
</pre> -->
