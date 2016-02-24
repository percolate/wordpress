<?php
  global $post_ID;
?>

<div id="percolate-backdrop" class="perc-backdrop" style="display:none;"></div>

<div id="percolate-media-library" data-id="<?php echo $post_ID; ?>" class="percolate-app" ng-app="percolateMedia" ng-controller="MediaCtr" style="display:none;" ng-cloak>
  <div class="library-modal">
    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Percolate Media Library</h4>
      </div>

      <div class="main-view">

        <loader></loader>

        <!-- Render images here -->
        <div class="browser">
          <!-- <pre>
            folder tree: {{folderTree}}
          </pre> -->
          <div class="row">

            <div class="col col-sm-6 col-md-3 col-lg-2" ng-show="folderTree.length > 1">
              <a  href="#" class="item-box"
                  ng-click="goBack()">

                  <div class="folder-container">
                    <div class="icon-folder">
                      Go back...
                    </div>
                  </div>
                  <div class="meta-container"></div>

              </a>
            </div>

            <div class="col col-sm-6 col-md-3 col-lg-2" ng-repeat="item in items">
              <a  href="#" class="item-box"
                  ng-class="{'selected': activeItem.id === item.id}"
                  ng-click="openItem(item)">

                <div ng-if="item.type === 'image'">
                  <div class="img-container" >
                    <div ng-if="item.images.medium.url" class="img-holder" style="background-image: url({{item.images.medium.url}});"></div>
                    <div ng-if="!item.images.medium.url" class="img-holder" style="background-image: url({{item.src}});"></div>
                  </div>
                  <div class="meta-container">
                    {{item.metadata.original_filename}}
                  </div>
                </div>

                <div ng-if="item.type === 'folder'">
                  <div class="folder-container">
                    <div class="icon-folder">
                      {{item.metadata.item_count}} item
                    </div>
                  </div>
                  <div class="meta-container">
                    {{item.metadata.name}}
                  </div>
                </div>

              </a>
            </div>
          </div>
        </div>

        <aside class="details-bar">
          <h5>MEDIA DETAILS</h5>
          <div class="details" ng-show="activeItem.id">
            <div class="image">
              <img ng-if="activeItem.images.medium.url" ng-src="{{activeItem.images.medium.url}}" alt="{{activeItem.id}} image" />
              <img ng-if="!activeItem.images.medium.url" ng-src="{{activeItem.src}}" alt="{{activeItem.id}} image" />
            </div>
            <div class="meta-container">
              <h5>{{activeItem.metadata.original_filename}}</h5>
              <hr>
              <form nng-submit="submitForm( mediaForm )" name="mediaForm" novalidate>
                <!-- <div class="form-group">
                  <label for="caption">Caption</label>
                  <textarea id="caption" name="caption" ng-model="formData.caption" class="form-control" rows="3"></textarea>
                </div> -->
                <div class="form-group">
                  <label for="imageSize">Image size</label>
                  <select name="imageSize" id="imageSize" class="form-control"
                          ng-model="formData.imageSize"
                          ng-options="option.id as option.name for option in imageSizes" required></select>
                </div>
                <div class="form-group">
                  <label for="alt">Alt Text</label>
                  <input type="text" id="alt" name="alt" ng-model="formData.alt" class="form-control">
                </div>
                <button type="submit" ng-click="importImage( mediaForm )" class="btn btn-default btn-primary btn-sm">Import to WordPress</button>
                <button type="submit" ng-click="insertFeatured( mediaForm )" class="btn btn-default btn-primary btn-sm">Insert as featured</button>
              </form>
            </div>

          </div>
        </aside>
      </div>

    </div>
  </div>
</div>
