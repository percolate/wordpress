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
      <!-- taxonomy mapping -->
      <hr>
      <h4 class="text-center">Taxonomy mapping - metadata fields</h4>

      <div class="taxonomy-map list-group">

          <div class="list-group-item" ng-repeat="(key, map) in formData.taxonomyMapping" style="background: none;">

              <div class="row form-group">
                  <div class="col-sm-6">
                      <select class="form-control"
                              ng-model="formData.taxonomyMapping[key]['taxonomyPercoKey']"
                              ng-change="selectedPerco(key)"
                              ng-disabled="!taxonomiesPerco"
                              required>
                          <option value="">Select Percolate taxonomy</option>
                          <option value="{{option.key}}" ng-repeat="option in taxonomiesPerco">{{option.label}}</option>
                      </select>
                  </div>
                  <div class="col-sm-6">
                      <select class="form-control"
                              ng-model="formData.taxonomyMapping[key]['taxonomyWP']"
                              ng-disabled="!taxonomiesWP"
                              ng-change="selectedWPCategory()"
                              taxonomiesWPrequired>
                          <option value="">Select WordPress taxonomy</option>
                          <option value="{{option.name}}"
                                  ng-repeat="option in taxonomiesWP"
                                  ng-disabled="disableWPCategory(option, formData.taxonomyMapping[key]['taxonomyWP'])"
                          >
                              {{option.label}}
                          </option>
                      </select>
                  </div>
              </div>

              <div class="terms" ng-if="formData.taxonomyMapping[key]['taxonomyPerco'] && formData.taxonomyMapping[key]['taxonomyWP']">
                  <div class="row form-group" ng-repeat="term in getTermsForTaxonomy(formData.taxonomyMapping[key]['taxonomyPerco'])">
                      <div class="col-sm-6">
                          <label for="mapping-{{key}}-{{term.id}}" style="font-weight: normal;"> – {{term.name}}</label>
                      </div>
                      <div class="col-sm-6">
                          <select name="mapping-{{key}}-{{term.id}}" class="form-control"
                                  ng-model="formData.taxonomyMapping[key]['terms'][term.id]"
                                  ng-selected="edit.active ? formData.taxonomyMapping[key]['terms'][term.id] : false"
                          >
                              <option value="">Don't map</option>
                              <option value="{{option.term_id}}" ng-repeat="option in termsWP | filterByTaxonomy: formData.taxonomyMapping[key]['taxonomyWP']">{{option.name}}</option>
                          </select>
                      </div>
                  </div>
              </div>

              <div class="form-group text-center">
                  <a class="btn btn-default btn-block" ng-click="deleteMapping(key)">Remove mapping</a>
              </div>

          </div>

      </div>

      <div class="form-group text-center">
          <a class="btn btn-default btn-block" ng-click="addMapping()">Add taxonomy mapping</a>
      </div>

      <hr>
    <!-- Templates -->
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

      <div class="template panel panel-default" ng-repeat="template in templates">

        <div class="panel-heading" role="tab" id="heading-{{template.name | safeName}}">
          <h4 class="panel-title">
            {{template.name}}
          </h4>
        </div>

        <div id="{{template.name | safeName}}" ng-init="formData[template.id].version = template.version.version_id" class="panel-collapse" role="tabpanel">
          <div class="panel-body">

            <div class="row form-group">
              <div class="col-sm-6">
                <label for="{{template.id}}-postType">WordPress Post Type</label>
              </div>
              <div class="col-sm-6">
                <select name="{{template.id}}-postType" id="{{template.id}}-postType" class="form-control"
                        ng-model="formData[template.id].postType"
                        ng-init="formData[template.id].postType = edit.active && formData[template.id].postType ? formData[template.id].postType : 'false'">
                  <option value="false">Don't map</option>
                  <option value="{{option.name}}" ng-repeat="option in postTypes">{{option.label}}</option>
                </select>
              </div>
            </div>

            <div class="details" ng-show="formData[template.id].postType != 'false'">
              <div class="row form-group">
                <div class="col-sm-6">
                  <label for="{{template.id}}-safety">Set status to Draft in WordPress</label>
                </div>
                <div class="col-sm-6">
                  <div class="switch">
                    <input type="radio" id="{{template.id}}-safety-on" name="{{template.id}}-safety" value="on" ng-model="formData[template.id].safety">
                    <input type="radio" id="{{template.id}}-safety-off" name="{{template.id}}-safety" value="off" ng-model="formData[template.id].safety" ng-checked="true" ng-init="formData[template.id].safety = edit.active && formData[template.id].safety ? formData[template.id].safety : 'off'">
                    <span class="toggle"></span>
                  </div>
                </div>
              </div>

              <div class="row form-group">
                <div class="col-sm-6">
                  <label for="{{template.id}}-import">Earliest import</label>
                </div>
                <div class="col-sm-6">
                  <select name="{{template.id}}-import" id="{{template.id}}-import" class="form-control"
                          ng-model="formData[template.id].import"
                          ng-init="formData[template.id].import = edit.active && formData[template.id].import ? formData[template.id].import : postStatuses[2].key"
                          ng-options="option.key as option.label for option in postStatuses">
                    <!-- <option ng-repeat="option in postStatuses"
                            value="{{option.key}}">
                      {{option.label}}
                    </option> -->
                  </select>
                </div>
              </div>

              <div class="row form-group">
                <div class="col-sm-6">
                  <label for="{{template.id}}-import">Final handoff from Percolate</label>
                </div>
                <div class="col-sm-6">
                  <select name="{{template.id}}-handoff" id="{{template.id}}-handoff" class="form-control"
                          ng-model="formData[template.id].handoff"
                          ng-init="formData[template.id].handoff = edit.active && formData[template.id].handoff ? formData[template.id].handoff : postStatuses[2].key"
                          ng-options="option.key as option.label for option in getHandoffStatuses(formData[template.id].import, template.id)">
                    <!-- <option ng-repeat="option in postStatuses"
                            value="{{option.key}}"
                            ng-disabled="checkHandoff(option, formData[template.id].import)">
                      {{option.label}}
                    </option> -->
                  </select>
                </div>
              </div>

              <div class="row form-group">
                <div class="col-sm-6">
                  <label for="{{template.id}}-postTitle">WordPress Post Title</label>
                </div>
                <div class="col-sm-6">
                  <select name="{{template.id}}-postTitle" id="{{template.id}}-postTitle" class="form-control"
                          ng-model="formData[template.id].postTitle"
                          ng-init="formData[template.id].postTitle = edit.active && formData[template.id].postTitle ? formData[template.id].postTitle : template.fields[0].key"
                          ng-options="option.key as option.label for option in template.fields"></select>
                </div>
              </div>

              <div class="row form-group">
                <div class="col-sm-6">
                  <label for="{{template.id}}-postBody">WordPress Post Body</label>
                </div>
                <div class="col-sm-6">
                  <select name="{{template.id}}-postBody" id="{{template.id}}-postBody" class="form-control"
                          ng-model="formData[template.id].postBody"
                          ng-init="formData[template.id].postBody = edit.active && formData[template.id].postBody ? formData[template.id].postBody : template.fields[0].key"
                          ng-options="option.key as option.label for option in template.fields"></select>
                </div>
              </div>

              <!-- Images -->
              <div class="row form-group">
                <div class="col-sm-6">
                  <label for="{{template.id}}-image">Import Images</label>
                </div>
                <div class="col-sm-6">
                  <div class="switch">
                    <input type="radio" id="{{template.id}}-image-on" name="{{template.id}}-image" value="on" ng-model="formData[template.id].image">
                    <input type="radio" id="{{template.id}}-image-off" name="{{template.id}}-image" value="off" ng-model="formData[template.id].image" ng-checked="true" ng-init="formData[template.id].image = edit.active && formData[template.id].image ? formData[template.id].image : 'off'">
                    <span class="toggle"></span>
                  </div>
                </div>
              </div>
              <div class="row form-group" ng-show="formData[template.id].image === 'on'">
                <div class="col-sm-6">
                  <label for="{{template.id}}-postImage">WordPress Featured Image</label>
                </div>
                <div class="col-sm-6">
                  <select name="{{template.id}}-postImage" id="{{template.id}}-postImage" class="form-control"
                          ng-model="formData[template.id].postImage"
                          ng-options="option.key as option.label for option in template.fields | filterType: 'asset' "></select>
                </div>
              </div>


              <!-- WPML -->
              <div class="row form-group" ng-if="isWpmlActive">
                <div class="col-sm-6">
                  <label for="{{template.id}}-wpmlStatus">Define WPML language</label>
                </div>
                <div class="col-sm-6">
                  <div class="switch">
                    <input type="radio" id="{{template.id}}-wpmlStatus-on" name="{{template.id}}-wpmlStatus" value="on" ng-model="formData[template.id].wpmlStatus">
                    <input type="radio" id="{{template.id}}-wpmlStatus-off" name="{{template.id}}-wpmlStatus" value="off" ng-model="formData[template.id].wpmlStatus" ng-checked="true" ng-init="formData[template.id].wpmlStatus = edit.active && formData[template.id].wpmlStatus ? formData[template.id].wpmlStatus : 'off'">
                    <span class="toggle"></span>
                  </div>
                </div>
              </div>
              <div class="row form-group" ng-if="formData[template.id].wpmlStatus === 'on' && isWpmlActive">
                <div class="col-sm-6">
                  <label for="{{template.id}}-wpmlField">Field of the language</label>
                </div>
                <div class="col-sm-6">
                  <select name="{{template.id}}-wpmlField" id="{{template.id}}-wpmlField" class="form-control"
                          ng-model="formData[template.id].wpmlField"
                          ng-options="option.key as option.label for option in template.fields | filterType: 'select' "></select>
                </div>
              </div>

              <div class="row form-group">
                <div class="col-sm-6">
                  <label for="{{template.id}}-acf">Map Percolate fields to</label>
                </div>
                <div class="col-sm-6">
                  <select name="{{template.id}}-acf" id="{{template.id}}-acf" class="form-control"
                          ng-model="formData[template.id].acf"
                          ng-init="formData[template.id].acf = edit.active && formData[template.id].acf ? formData[template.id].acf : 'off'"
                          ng-change="formData[template.id].mapping[field.key] = ''">
                    <option value="off">WP custom fields</option>
                    <option value="on" ng-if="isAcfActive">ACF fields</option>
                    <option value="metabox" ng-if="isMetaBoxActive">Meta Box fields</option>
                  </select>
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
              <table class="table">
                <thead>
                  <td>Label</td>
                  <td>Type</td>
                  <td ng-if="formData[template.id].acf === 'off'">Custom field</td>
                  <td ng-if="formData[template.id].acf !== 'off'"> Field group</td>
                  <td ng-if="formData[template.id].acf !== 'off'"> Field (type)</td>
                </thead>

                <tbody>

                  <tr class="form-group"
                        ng-repeat="field in template.fields"
                        ng-hide="field.key == formData[template.id].postBody ||
                                field.key == formData[template.id].postImage ||
                                field.key == formData[template.id].postTitle ||
                                (field.key == formData[template.id].wpmlField && formData[template.id].wpmlStatus !== 'on')">
                    <div class="input-group">
                      <td><span cclass="input-group-addon">{{field.label}}</span></td>
                      <td><span cclass="input-group-addon">{{field.type}}</span></td>
                      <!-- Custom field -->
                      <td ng-if="formData[template.id].acf === 'off'">
                        <input  type="text" name="key" class="form-control"
                              ng-model="formData[template.id].mapping[field.key]">
                      </td>
                      <!-- Advanced Custom Fields -->
                      <td ng-if="formData[template.id].acf === 'on'">
                        <select name="{{template.id}}-acfSet" id="{{template.id}}-acfSet" class="form-control"
                                ng-model="formData[template.id].acfGroup[field.key]"
                                ng-change="formData[template.id].mapping[field.key] = ''"
                                ng-selected="edit.active ? formData[template.id].acfGroup[field.key] : false">
                          <option value="">Don't map</option>
                          <option value="{{option.ID}}" ng-repeat="option in acfGroups">{{option.post_title}}</option>
                        </select>
                      </td>
                      <td ng-if="formData[template.id].acf === 'on'">
                        <select name="acfkey" class="form-control"
                              ng-model="formData[template.id].mapping[field.key]"
                              ng-selected="edit.active ? formData[template.id].mapping[field.key] : false">
                          <option value="">Don't map</option>
                          <option value="{{option.key}}" ng-repeat="option in acfFields[formData[template.id].acfGroup[field.key]]">{{option.label}}  ({{option.data.type}})</option>
                        </select>
                      </td>
                      <!-- Meta Box -->
                      <td ng-if="formData[template.id].acf === 'metabox'">
                        <select class="form-control"
                                ng-model="formData[template.id].metaboxGroup[field.key]"
                                ng-change="formData[template.id].mapping[field.key] = ''"
                                ng-selected="edit.active ? formData[template.id].metaboxGroup[field.key] : false">
                          <option value="">Don't map</option>
                          <option value="{{key}}" ng-repeat="(key, option) in metaboxGroups">{{option.title}}</option>
                        </select>
                      </td>
                      <td ng-if="formData[template.id].acf === 'metabox'">
                        <select class="form-control"
                              ng-model="formData[template.id].mapping[field.key]"
                              ng-selected="edit.active ? formData[template.id].mapping[field.key] : false">
                          <option value="">Don't map</option>
                          <option value="{{option.id}}" ng-repeat="option in metaboxGroups[formData[template.id].metaboxGroup[field.key]].fields">{{option.name}}  ({{option.type}})</option>
                        </select>
                      </td>
                    </div>
                    <p class="small" ng-show="field.description">{{field.description}}</p>
                  </tr>

                </tbody>
              </table>
                <!-- taxonomy mapping -->
                <h4 class="text-left">Taxonomy mapping</h4>

                <div class="taxonomy-map list-group">

                    <div class="list-group-item" ng-repeat="(key, map) in formData[template.id].taxonomyMapping" style="background: none;">

                        <div class="row form-group">
                            <div class="col-sm-6">
                                <select class="form-control"
                                        ng-model="formData[template.id].taxonomyMapping[key]['taxonomyPercoKey']"
                                        ng-change="selectedPercoTemplate(template.id, key)"
                                        ng-disabled="!template.taxonomies"
                                        required>
                                    <option value="">Select Percolate taxonomy</option>
                                    <option value="{{option.keyId}}" ng-repeat="option in template.taxonomies">{{option.showName}}</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <select class="form-control"
                                        ng-model="formData[template.id].taxonomyMapping[key]['taxonomyWP']"
                                        ng-disabled="!template.taxonomiesWP"
                                        ng-change="selectedWPCategoryTemplate(template.id)"
                                        taxonomiesWPrequired>
                                    <option value="">Select WordPress taxonomy</option>
                                    <option value="{{option.name}}"
                                            ng-repeat="option in template.taxonomiesWP"
                                            ng-disabled="disableWPCategory(option, formData[template.id].taxonomyMapping[key]['taxonomyWP'])"
                                    >
                                        {{option.label}}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="terms" ng-if="formData[template.id].taxonomyMapping[key]['taxonomyPerco'] && formData[template.id].taxonomyMapping[key]['taxonomyWP']">
                            <div class="row form-group" ng-repeat="term in getTermsForTaxonomy(formData[template.id].taxonomyMapping[key]['taxonomyPerco'])">
                                <div class="col-sm-6">
                                    <label for="mapping-{{key}}-{{term.id}}" style="font-weight: normal;"> – {{term.name}}</label>
                                </div>
                                <div class="col-sm-6">
                                    <select name="mapping-{{key}}-{{term.id}}" class="form-control"
                                            ng-model="formData[template.id].taxonomyMapping[key]['terms'][term.id]"
                                            ng-selected="edit.active ? formData[template.id].taxonomyMapping[key]['terms'][term.id] : false"
                                    >
                                        <option value="">Don't map</option>
                                        <option value="{{option.term_id}}" ng-repeat="option in termsWP | filterByTaxonomy: formData[template.id].taxonomyMapping[key]['taxonomyWP']">{{option.name}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-center">
                            <a class="btn btn-default btn-block" ng-click="deleteMappingTemplate(key, template.id)">Remove mapping</a>
                        </div>

                    </div>

                </div>

                <div class="form-group text-center">
                    <a class="btn btn-default btn-block" ng-disabled="!template.taxonomies" ng-click="addMappingTemplate(template.id, template.taxonomies)">Add taxonomy mapping</a>
                </div>

                <hr>
                <!-- /taxonomy mapping -->
            </div><!-- Details -->
          </div><!-- Panel body -->
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

<!-- <pre>
  {{formData}}
</pre> -->
