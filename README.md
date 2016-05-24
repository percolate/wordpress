# wordpress

[![Circle CI](https://circleci.com/gh/percolate/wordpress.svg?style=svg&circle-token=31cbc7d300a4aaa0396a12fffc722cda7a00dc7b)](https://circleci.com/gh/percolate/wordpress)
[![codecov.io](https://codecov.io/github/percolate/wordpress/coverage.svg?branch=master&token=D4xm32VsPP)](https://codecov.io/github/percolate/wordpress?branch=master)

* [User Guide](#user-installation-guide)
  * [Plugin overview](#plugin-overview)
  * [Installation and setup](#installation-and-setup)
    * [Initial configuration](#initial-configuration) 
    * [Custom template configuration](#custom-template-configuration)
    * [Browsing Percolate assets](#browsing-percolate-assets)
  * [Testing the plugin](#testing-the-plugin)
* [Changelog](#changelog)

**Document version:** 1.0

*Note:* The [legacy plugin](https://github.com/percolate/WP-Percolate)
is no longer being supported by Percolate.
While it may continue to function, continued functionality is not guaranteed.

In order to use this plugin you will need an API key issued to you from Percolate,
along with your user ID.
***

## User Installation Guide

### Plugin overview

The WordPress-Percolate connector is an installable WordPress plugin made
available to Percolate Customers. The plugin is designed specifically for
WordPress, and includes support for common WordPress concepts like
Custom Post Types, post categories, Media Library, and Featured Images.

It is tested up to WordPress 4.4.2, and is hosted on Github.

#### How it works

The plugin can be configured for Percolate licenses by using a
Percolate API key combined with License and Channel information, both configurable
via dropdown menu. For each configured license, custom templates in Percolate
can be mapped to Post Types in WordPress. The content from each post created
in Percolate is pushed to WordPress every 5 minutes, using the WordPress
built-in, PHP-driven cron. For each post, WordPress content is created
based on the field mappings and import rules.

Percolate custom creative templates support most commonly-used blog page elements.

#### Supported features

* Importing posts
* Importing post images (Featured Image and in-line in post body)
* Importing post tags as WordPress tags
* Importing and mapping Percolate Topics as WordPress categories
* Importing custom schemas
* Usage of custom blog entry templates
* Avoiding post duplication
* Configuration of import logic (import draft vs. queued posts)
* Search Percolate DAM in WordPress Media Library

#### Plugin components

| Component              | Description |
| ---------------------- | ----------- |
| Plugin core            | Core and model/view files |
| Public library support | Supporting libraries from Angular, Boostrap, et al. |
| Additional files       | For CSS compilation, markdown, testing, etc. |

### Installation and setup

This section describes how to install the Percolate-WordPress plugin
and perform initial configuration.

As a first step, please ensure you have the latest Percolate-WordPress
plugin distribution, available on Github.

#### Initial configuration

##### 1. In WordPress, navigate to Plugins

* Select "Add New"
* Add the Percolate-WordPress .zip file and select “Install Now”

##### 2. Click into the Percolate Plugin from the WordPress menu

* Under “Manage Channels,” select “Add New”

##### 3. Perform initial Channel configuration

* Add user API key to unlock list of available Licenses
* Provide your preferred name for configured channel in WordPress
  (e.g. “Percolate Posts”)
* Select Percolate License, Platform, and Channel
* “Continue”

##### 4. Map Channel Topics and Subtopics

* Percolate License Topics will appear on the left
* Select WordPress categories for each Percolate topic.
* Unmapped Topics will not import

**Note:** after this step, you may “Save” without configuring templates,
and return at any time.

#### Custom template configuration

This process requires that Custom Creative templates have been configured for
your License in Percolate. For assistance with Custom Creative templates, please
reach out to your Percolate Engagement Manager or Product Specialist.

In addition to template mapping, you can set specific import rules
to be set for each License:Channel configuration:

| Earliest import | Percolate statuses imported | Description |
| --- | --- | --- |
| Draft | "Publishing," "Queued," "Draft" | Import all approved drafts and posts
| Queued | "Publishing," "Queued," | Import all posts that are done being drafted
| On Schedule | "Publishing" | Import posts only at their scheduled publishing times

##### 1. Map custom templates: basic

* Each custom template associated with the configured Channel can be mapped to a
  WordPress Post Type
* For each of the default WordPress Post fields, select a Percolate field to be mapped
* In the “Earliest import” dropdown, select the workflow step at which you’d like
  Percolate content to import
* To avoid “Queued” posts automatically publishing at their scheduled times, select
  “Set status to Draft in WP”
  * By default, imports of “Queued” and “Publishing” posts will be scheduled for
    publishing in WP
  * Note: “Drafts” imported from Percolate will never publish automatically

##### 2. Map custom templates: advanced

* Additional Custom Creative fields can be mapped to WordPress shortcodes
* While using special markup for Percolate-importing fields is not required,
  it is recommended for clarity
  * All basic title/body/image mappings still apply

#### Browsing Percolate assets

This section describes the setup and functionality of the basic Percolate-WordPress
asset integration that comes bundled with the post import plugin.

##### 1. In the Percolate plugin homepage, select Settings

##### 2. Configure a License

* Enter a Percolate API Key to unlock a list of Licenses
* Select the License that will be used for asset search

##### 3. Confirm you can insert Percolate assets on new WordPress posts

* Select image size and alt text, and import

### Testing the plugin

Once Channel and template configuration are complete, you can test the module by
creating a Post in Percolate, and forcing the import job to run with the
“Import” button on the plugin homepage.

The following steps require that a custom WordPress Channel has been added to your
Percolate License. For assistance with custom Channels, please contact your Percolate
Engagement Manager or Product Specialist.

#### Testing imports

##### 1. Using the “Create” button, create a Post in the configured Channel

* Select desired WordPress Channel and Template
* Compose your post

##### 2. Run the Import job from the Plugin homepage

##### 3. Confirm your test Post has appeared in WordPress as expected

You should see that:

* All configured fields have imported
* All tags have been imported
* Topics are mapped correctly
* Images have imported and are available in Media Library

***

## Changelog

### 4.0.2

* Draft | Queued post status support
* Draft posts from Percolate will become drafts in WP
* Percolate's Select field can be mapped to ACF True/False field

### 4.0.1

* Custom approvals workflow support
* 5 minute CRON interval
* Bugfixes / improved compatibility with other plugins

### 4.0.0

* Initial release with support for custom platforms/templates.

For change history of the Percolate-Wordpress legacy plugin,
please refer to the original repository:
[WP-Percolate](https://github.com/percolate/WP-Percolate)

***

_Please do not remove this version declaration_
~Current Version:4.0.2~
