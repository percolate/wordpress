<div class="progress">
  <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 100%;">
    3 / 3
  </div>
</div>

<div class="row">
  <div class="col-sm-12 text-center">
    <h3>Map templates</h3>
    <p class="info">Here you can map your Percolate custom creative templates</p>
  </div>
</div>

<form ng-submit="submitForm( templatesForm )" name="templatesForm" class="row topics" novalidate>
  <div class="col-sm-8 col-sm-offset-2 col-lg-6 col-lg-offset-3">

    <!-- Templates -->
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

      <div class="template panel panel-default" ng-repeat="template in templates">

        <div class="panel-heading" role="tab" id="heading-{{template.name | safeName}}">
          <h4 class="panel-title">
            <a role="button" data-toggle="collapse" data-parent="#accordion" href="#{{template.name | safeName}}">
              {{template.name}}
            </a>
          </h4>
        </div>

        <div id="{{template.name | safeName}}" ng-init="formData[template.id].version = template.version.version_id" class="panel-collapse collapse" ng-class='{in:$first}'8 role="tabpanel">
          <div class="panel-body">

            <div class="row form-group">
              <div class="col-sm-6">
                <label for="{{template.id}}-postType">Wordpress Post Type</label>
              </div>
              <div class="col-sm-6">
                <select name="{{template.id}}-postType" id="{{template.id}}-postType" class="form-control"
                        ng-model="formData[template.id].postType"
                        ng-init="formData[template.id].postType = edit.active ? formData[template.id].postType : postTypes[0].name"
                        ng-options="option.name as option.label for option in postTypes"></select>
              </div>
            </div>

            <div class="row form-group">
              <div class="col-sm-6">
                <label for="{{template.id}}-safety">Set status to Draft in WP</label>
              </div>
              <div class="col-sm-6">
                <div class="switch">
                  <input type="radio" id="{{template.id}}-safety-on" name="{{template.id}}-safety" value="on" ng-model="formData[template.id].safety">
                  <input type="radio" id="{{template.id}}-safety-off" name="{{template.id}}-safety" value="off" ng-model="formData[template.id].safety" ng-checked="true" ng-init="formData[template.id].safety = edit.active ? formData[template.id].safety : 'off'">
                  <span class="toggle"></span>
                </div>
              </div>
            </div>

            <!--div class="row form-group">
              <div class="col-sm-6">
                <label for="{{template.id}}-approved">Import approved drafts</label>
              </div>
              <div class="col-sm-6">
                <div class="switch">
                  <input type="radio" id="{{template.id}}-approved-on" name="{{template.id}}-approved" value="on" ng-model="formData[template.id].approved">
                  <input type="radio" id="{{template.id}}-approved-off" name="{{template.id}}-approved" value="off" ng-model="formData[template.id].approved" ng-checked="true" ng-init="formData[template.id].approved = edit.active ? formData[template.id].approved : 'off'">
                  <span class="toggle"></span>
                </div>
              </div>
            </div-->
            <div class="row form-group">
              <div class="col-sm-6">
                <label for="{{template.id}}-import">Earliest import</label>
              </div>
              <div class="col-sm-6">
                <select name="{{template.id}}-import" id="{{template.id}}-import" class="form-control"
                        ng-model="formData[template.id].import"
                        ng-init="formData[template.id].import = edit.active ? formData[template.id].import : earliestImport[2].key"
                        ng-options="option.key as option.label for option in earliestImport"></select>
              </div>
            </div>

            <div class="row form-group">
              <div class="col-sm-6">
                <label for="{{template.id}}-postTitle">Wordpress Post Title</label>
              </div>
              <div class="col-sm-6">
                <select name="{{template.id}}-postTitle" id="{{template.id}}-postTitle" class="form-control"
                        ng-model="formData[template.id].postTitle"
                        ng-init="formData[template.id].postTitle = edit.active ? formData[template.id].postTitle : template.fields[0].key"
                        ng-options="option.key as option.label for option in template.fields"></select>
              </div>
            </div>

            <div class="row form-group">
              <div class="col-sm-6">
                <label for="{{template.id}}-postBody">Wordpress Post Body</label>
              </div>
              <div class="col-sm-6">
                <select name="{{template.id}}-postBody" id="{{template.id}}-postBody" class="form-control"
                        ng-model="formData[template.id].postBody"
                        ng-init="formData[template.id].postBody = edit.active ? formData[template.id].postBody : template.fields[0].key"
                        ng-options="option.key as option.label for option in template.fields"></select>
              </div>
            </div>

            <div class="row form-group">
              <div class="col-sm-6">
                <label for="{{template.id}}-image">Import Images</label>
              </div>
              <div class="col-sm-6">
                <div class="switch">
                  <input type="radio" id="{{template.id}}-image-on" name="{{template.id}}-image" value="on" ng-model="formData[template.id].image">
                  <input type="radio" id="{{template.id}}-image-off" name="{{template.id}}-image" value="off" ng-model="formData[template.id].image" ng-checked="true" ng-init="formData[template.id].image = edit.active ? formData[template.id].image : 'off'">
                  <span class="toggle"></span>
                </div>
              </div>
            </div>
            <div class="row form-group" ng-show="formData[template.id].image === 'on'">
              <div class="col-sm-6">
                <label for="{{template.id}}-postImage">Wordpress Featured Image</label>
              </div>
              <div class="col-sm-6">
                <select name="{{template.id}}-postImage" id="{{template.id}}-postImage" class="form-control"
                        ng-model="formData[template.id].postImage"
                        ng-options="option.key as option.label for option in template.fields | filterAsset "></select>
              </div>
            </div>

            <div class="row form-group" ng-show="isAcfActive">
              <div class="col-sm-6">
                <label for="{{template.id}}-acf">Use Advanced Custom Fields</label>
              </div>
              <div class="col-sm-6">
                <div class="switch">
                  <input type="radio" id="{{template.id}}-acf-on" name="{{template.id}}-acf" value="on" ng-model="formData[template.id].acf">
                  <input type="radio" id="{{template.id}}-acf-off" name="{{template.id}}-acf" value="off" ng-model="formData[template.id].acf" ng-checked="true" ng-init="formData[template.id].acf = edit.active ? formData[template.id].acf : 'off'">
                  <span class="toggle"></span>
                </div>
              </div>
            </div>

            <!-- ::::: ACF :::::: -->
            <div class="acf" ng-show="formData[template.id].acf === 'on'">
              <div class="row form-group">
                <div class="col-sm-6">
                  <label for="{{template.id}}-acfSet">Field Group</label>
                </div>
                <div class="col-sm-6">
                  <select name="{{template.id}}-acfSet" id="{{template.id}}-acfSet" class="form-control"
                          ng-model="formData[template.id].acfSet",
                          ng-init="formData[template.id].acfSet = edit.active ? formData[template.id].acfSet : acfGroups.ID"
                          ng-options="option.ID as option.post_title for option in acfGroups"></select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-sm-12">
                <hr>
                <h4>Map the fields</h4>
                <p class="small">
                  You can give a custom name to the meta fields, if you leave a field blank, the percolate field key will be used.
                </p>
              </div>
            </div>

            <!-- ::::: CPM :::::  -->
            <div class="form-group"
                  ng-repeat="field in template.fields"
                  ng-hide="field.key == formData[template.id].postBody || field.key == formData[template.id].postImage || field.key == formData[template.id].postTitle">
              <div class="input-group">
                <span class="input-group-addon">{{field.label}}</span>
                <span class="input-group-addon">{{field.type}}</span>
                <input  type="text" name="key" class="form-control"
                        ng-model="formData[template.id].mapping[field.key]"
                        ng-if="formData[template.id].acf !== 'on'">
                <select name="acfkey" class="form-control"
                        ng-model="formData[template.id].mapping[field.key]"
                        ng-if="formData[template.id].acf == 'on'"
                        ng-options="option.key as option.label for option in acfFields[formData[template.id].acfSet]"></select>
              </div>
              <p class="small" ng-show="field.description">{{field.description}}</p>
            </div>


            <!-- <div class="row form-group">
              <div class="col-sm-6">
                <label for="{{topic.slug}}">{{topic.name}}</label>
              </div>
              <div class="col-sm-6">
                <select name="{{topic.slug}}" id="{{topic.slug}}" class="form-control"
                        ng-model="formData[topic.slug]",
                        ng-init="formData[topic.slug] = categories[0].slug"
                        ng-options="option.slug as option.name for option in WP.cpts"></select>
              </div>
            </div> -->

          </div>
        </div>

      </div>

    </div>

    <div class="row-group" ng-show="templatesForm.$invalid && (!templatesForm.$pristine || submitted)">
        <p class="error">Please fill out all the required fields!</p>
    </div>

    <div class="form-group">
      <button type="submit" class="btn btn-success btn-block">Finish</button>
    </div>

  </div>
</form>

<pre>
  {{formData}}
</pre>
