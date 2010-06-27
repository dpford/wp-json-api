=== JSON API ===
Contributors: dphiffer
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=DH4MEG99JR2WE
Tags: json, api, ajax, cms, admin, integration, moma
Requires at least: 2.8
Tested up to: 3.0
Stable tag: 0.9.6

A RESTful API for WordPress

== Description ==

JSON API offers a means of retrieving and manipulating WordPress content using HTTP requests. There are two main goals with JSON API:

1. Provide a simple, consistent external interface
2. Create a stable, understandable internal implementation

This plugin was created for [The Museum of Modern Art](http://moma.org/), whose weblog [Inside/Out](http://moma.org/explore/inside_out) is served from Ruby on Rails. Instead of reimplementing the site templates as a WordPress theme, we opted for a Rails front-end that displays content served from a WordPress back-end. JSON API provides the necessary interface for retrieving content and accepting comment submissions.

See the *Other Notes* section for complete API documentation.

== Installation ==

1. Upload the `json-api` folder to the `/wp-content/plugins/` directory or install directly through the plugin installer.
1. Activate the plugin through the 'Plugins' menu in WordPress or by using the link provided by the plugin installer.

== Screenshots ==

1. Our old friend, in JSON format

== Requests ==

Requests use a simple REST-style HTTP GET or POST. To invoke the API, include a non-empty query value for `json` in the URL.

JSON API operates in two modes:

1. *Implicit mode* is triggered by setting the `json` query var to a non-empty value on any WordPress page. The content that would normally appear on that page is returned in JSON format.
1. *Explicit mode* is triggered by setting `json` to a known method string. See the *API Reference* section below for a complete method listing.

= Implicit mode examples: =

 * `http://www.example.org/?json=1`
 * `http://www.example.org/?p=47&json=1`
 * `http://www.example.org/tag/banana/?json=1`

= Explicit mode examples: =

* `http://www.example.org/?json=get_recent_posts`
* `http://www.example.org/?json=get_post&post_id=47`
* `http://www.example.org/?json=get_tag_posts&tag_slug=banana`

= With user-friendly permalinks configured: =

* `http://www.example.org/api/get_recent_posts/`
* `http://www.example.org/api/get_post/?post_id=47`
* `http://www.example.org/api/get_tag_posts/?tag_slug=banana`

== Responses ==

The standard response format for JSON API is (as you may have guessed) [JSON](http://json.org/).

Here is an example response from `http://localhost/wordpress/?json=1` called on a default WordPress installation (formatted for readability):

    {
      "status": "ok",
      "count": 1,
      "count_total": 1,
      "pages": 1,
      "posts": [
        {
          "id": 1,
          "type": "post",
          "slug": "hello-world",
          "url": "http:\/\/localhost\/wordpress\/?p=1",
          "title": "Hello world!",
          "title_plain": "Hello world!",
          "content": "<p>Welcome to WordPress. This is your first post. Edit or delete it, then start blogging!<\/p>\n",
          "excerpt": "Welcome to WordPress. This is your first post. Edit or delete it, then start blogging!\n",
          "date": "2009-11-11 12:50:19",
          "modified": "2009-11-11 12:50:19",
          "categories": [],
          "tags": [],
          "author": {
            "id": 1,
            "slug": "admin",
            "name": "admin",
            "first_name": "",
            "last_name": "",
            "nickname": "",
            "url": "",
            "description": ""
          },
          "comments": [
            {
              "id": 1,
              "name": "Mr WordPress",
              "url": "http:\/\/wordpress.org\/",
              "date": "2009-11-11 12:50:19",
              "content": "<p>Hi, this is a comment.<br \/>To delete a comment, just log in and view the post&#039;s comments. There you will have the option to edit or delete them.<\/p>\n",
              "parent": 0
            }
          ],
          "comment_count": 1,
          "comment_status": "open"
        }
      ]
    }

== API Reference ==

The JSON API reference is split into four sections:

1. Core methods
1. Response objects
1. Request arguments
1. Plugin hooks
1. Extending JSON API


== 1. Core methods ==

The Core controller offers a mostly-complete set of introspection methods for retrieving content from WordPress and two very basic data manipulation methods.


== Method: info ==

Returns information about JSON API.

= Optional arguments =

* `controller` - returns detailed information about a specific controller

= Response =

    {
      "status": "ok",
      "json_api_version": "1.0",
      "controllers": "core"
    }

= Response =

    {
      "status": "ok",
      "name": "Core",
      "description": "Basic introspection methods",
      "methods": [
        "info",
        "get_recent_posts",
        "get_post",
        "get_page",
        "get_date_posts",
        "get_category_posts",
        "get_tag_posts",
        "get_author_posts",
        "get_search_results",
        "get_date_index",
        "get_category_index",
        "get_tag_index",
        "get_author_index",
        "create_post",
        "submit_comment"
      ],
      "url": "http:\/\/wordpress.org\/extend\/plugins\/json-api\/other_notes\/"
    }
    

== Method: get_recent_posts ==

Returns an array of recent posts. You can invoke this from the WordPress home page either by setting `json` to a non-empty value (i.e., `json=1`) or from any page by setting `json=get_recent_posts`.

= Optional arguments =

* `page` - return a specific page number from the results

= Response =

    {
      "status": "ok",
      "count": 10,
      "count_total": 79,
      "pages": 7,
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }


== Method: get_post ==

Returns a single post object.

= One of the following is required =

* Invoking the JSON API implicitly (i.e., `?json=1`) on a post URL
* `post_id` - set to the post's ID
* `post_slug` - set to the post's URL slug

= Response =

    {
      "status": "ok",
      "post": { ... }
    }


== Method: get_page ==

Returns a single page object.

= One of the following is required =

* Invoking the JSON API implicitly (i.e., `?json=1`) on a page URL
* `page_id` - set to the page's ID
* `page_slug` - set to the page's URL slug
* `children` - set to a non-empty value to include a recursive hierarchy of child pages

= Response =

    {
      "status": "ok",
      "page": { ... }
    }

== Method: get_date_posts ==

Returns an array of posts/pages in a specific category.

= One of the following is required =

* Invoking the JSON API implicitly (i.e., `?json=1`) on a date archive page
* `date` - set to a date in the format `YYYY` or `YYYYMM` or `YYYYMMDD`

= Optional arguments =

* `page` - return a specific page number from the results

= Response =

    {
      "status": "ok",
      "count": 10,
      "count_total": 79,
      "pages": 7,
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }

== Method: get_category_posts ==

Returns an array of posts/pages in a specific category.

= One of the following is required =

* Invoking the JSON API implicitly (i.e., `?json=1`) on a category archive page
* `category_id` - set to the category's ID
* `category_slug` - set to the category's URL slug

= Optional arguments =

* `page` - return a specific page number from the results

= Response =

    {
      "status": "ok",
      "count": 10,
      "count_total": 79,
      "pages": 7,
      "category": { ... }
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }


== Method: get_tag_posts ==

Returns an array of posts/pages with a specific tag.

= One of the following is required =

* Invoking the JSON API implicitly (i.e., `?json=1`) on a tag archive page
* `tag_id` - set to the tag's ID
* `tag_slug` - set to the tag's URL slug

= Optional arguments =

* `page` - return a specific page number from the results

= Response =

    {
      "status": "ok",
      "count": 10,
      "count_total": 79,
      "pages": 7,
      "tag": { ... }
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }


== Method: get_author_posts ==

Returns an array of posts/pages written by a specific author.

= One of the following is required =

* Invoking the JSON API implicitly (i.e., `?json=1`) on an author archive page
* `author_id` - set to the author's ID
* `author_slug` - set to the author's URL slug

= Optional arguments =

* `page` - return a specific page number from the results

= Response =

    {
      "status": "ok",
      "count": 10,
      "count_total": 79,
      "pages": 7,
      "author": { ... }
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }


== Method: get_search_results ==

Returns an array of posts/pages in response to a search query.

= One of the following is required =

* Invoking the JSON API implicitly (i.e., `?json=1`) on a search results page
* `search` - set to the desired search query

= Optional arguments =

* `page` - return a specific page number from the results

= Response =

    {
      "status": "ok",
      "count": 10,
      "count_total": 79,
      "pages": 7,
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }


== Method: get_date_index ==

Returns both an array of date page permalinks and a tree structure representation of the archive.

= Response =

    {
      "status": "ok",
      "permalinks": [
        "...",
        "...",
        "..."
      ],
      "tree": {
        "2009": {
          "09": 17,
          "10": 20,
          "11": 7
        }
      }

Note: the tree is arranged by `response.tree.[year].[month].[number of posts]`.


== Method: get_category_index ==

Returns an array of active categories.

= Response =

    {
      "status": "ok",
      "count": 3,
      "categories": [
        { ... },
        { ... },
        { ... }
      ]
    }


== Method: get_tag_index ==

Returns an array of active tags.

= Response =

    {
      "status": "ok",
      "count": 3
      "tags": [
        { ... },
        { ... },
        { ... }
      ]
    }


== Method: get_author_index ==

Returns an array of active blog authors.

= Response =

    {
      "status": "ok",
      "count": 3,
      "authors": [
        { ... },
        { ... },
        { ... }
      ]
    }


== Method: get_page_index ==

Returns a hierarchical tree of `page` posts.

= Response =

    {
      "status": "ok",
      "pages": [
        { ... },
        { ... },
        { ... }
      ]
    }


== Method: create_post ==

Creates a new post.

= Optional arguments =

* `status` - sets the post status ("draft" or "publish"), default is "draft"
* `title` - the post title
* `content` - the post content
* `author` - the post's author (login name), default is the current logged in user
* `categories` - a comma-separated list of categories (URL slugs)
* `tags` - a comma-separated list of tags (URL slugs)

Note: including a file upload field called `attachment` will cause an attachment to be stored with your new post.

== Method: submit_comment ==

Submits a comment to a WordPress post.

= Required arguments =

* `post_id` - which post to comment on
* `name` - the commenter's name
* `email` - the commenter's email address
* `content` - the comment content

= Optional arguments =

* `redirect` - redirect instead of returning a JSON object
* `redirect_ok` - redirect to a specific URL when the status value is `ok`
* `redirect_error` - redirect to a specific URL when the status value is `error`
* `redirect_pending` - redirect to a specific URL when the status value is `pending`

= Custom status values =

* `pending` - assigned if the comment submission is pending moderation


== 2. Response objects ==

This section describes data objects you can retrieve from WordPress and the optional URL redirects.

__Status values__  
All JSON API requests result in a status value. The two basic status values are `ok` and `error`. Additional status values are available for certain methods (such as `pending` in the case of the `submit_comment` method). API methods that result in custom status values include a *custom status values* section in their documentation.

__Naming compatibility__  
Developers familiar with WordPress may notice that many names for properties and arguments have been changed. This was a stylistic choice that intends to provide more clarity and consistency in the interface.

= Post response object =

* `id` - Integer
* `type` - String (e.g., `post` or `page`)
* `slug` - String
* `url` - String
* `title` - String
* `title_plain` - String
* `content` - String (modified by the `read_more` argument)
* `excerpt` - String
* `date` - String (modified by the `date_format` argument)
* `modified` - String (modified by the `date_format` argument)
* `categories` - Array of category objects
* `tags` - Array of tag objects
* `author` Author object
* `comments` - Array of comment objects
* `attachments` - Array of attachment objects
* `comment_count` - Integer
* `comment_status` - String (`"open"` or `"closed"`)
* `thumbnail` - String (only included if a post thumbnail has been specified)
* `custom_fields` - Object (included by setting the `custom_fields` argument to a comma-separated list of custom field names)

= Category response object =

* `id` - Integer
* `slug` - String
* `title` - String
* `description` - String
* `parent` - Integer
* `post_count` - Integer

= Tag response object =

* `id` - Integer
* `slug` - String
* `title` - String
* `description` - String
* `post_count` - Integer

= Author response object =

* `id` - Integer
* `slug` - String
* `name` - String
* `first_name` - String
* `last_name` - String
* `nickname` - String
* `url` - String
* `description` - String
  
Note: You can include additional values by setting the `author_meta` argument to a comma-separated list of metadata fields.

= Comment response object =

* `id` - Integer
* `name` - String
* `url` - String
* `date` - String
* `content` - String
* `parent` - Integer
* `author` - Object (only set if the comment author was registered & logged in)

= Attachment response object =

* `id` - Integer
* `url` - String
* `slug` - String
* `title` - String
* `description` - String
* `caption` - String
* `parent` - Integer
* `mime_type` - String
* `images` - Object with values `thumbnail`, `medium`, `large`, `full`, each of which are objects with values `url`, `width` and `height` (only set if the attachment is an image)


== 3. Request arguments ==

The following arguments modify how you get results back from the API. The redirect response styles are intended for use with the data manipulation methods.

* Setting `callback` to a JavaScript function name will trigger a JSONP-style callback.
* Setting `redirect` to a URL will cause the user's browser to redirect to the specified URL with a `status` value appended to the query vars (see the *Response objects* section below for an explanation of status values).
* Setting `redirect_[status]` allows you to control the resulting browser redirection depending on the `status` value.
* Setting `dev` to a non-empty value adds whitespace for readability and responds with `text/plain`
* Not setting any of the above argument values will result in a standard JSON response.

These arguments are available to modify all introspection methods:

* `date_format` - Changes the format of date values. Uses the same syntax as PHP's date() function. Default value is `Y-m-d H:i:s`.
* `read_more` - Changes the 'read more' link text in post content.
* `include` - Specifies which post data fields to include. Expects a comma-separated list of post fields. Leaving this empty includes *all* fields.
* `exclude` - Specifies which post data fields to exclude. Expects a comma-separated list of post fields.
* `custom_fields` - Includes values from posts' Custom Fields. Expects a comma-separated list of custom field keys.
* `author_meta` - Includes additional author metadata. Should be a comma-separated list of metadata fields.
* `count` - Controls the number of posts to include (defaults to the number specified by WordPress)
* `order` - Controls the order of post results ('DESC' or 'ASC'). Default value is 'DESC'.
* `order_by` - Controls which field to order results by. Expects one of the following values:
  * `author`
  * `date` (default value)
  * `title`
  * `modified`
  * `menu_order` (only works with Pages)
  * `parent`
  * `ID`
  * `rand`
  * `meta_value` (`meta_key` must also be set)
  * `none`
  * `comment_count`
* `meta_key`, `meta_value`, `meta_compare` - Retrieve posts (or Pages) based on a custom field key or value.

__Additional arguments__
JSON API is based on the same rules as the [`query_posts` template tag](http://codex.wordpress.org/Template_Tags/query_posts). Any query arguments that can be used to augment a call to `query_posts` can also be passed as a URL query variable to achieve the same results.

__About `include`/`exclude` arguments__  
By default you get all values included with each post object. Specify a list of `include` values will cause the post object to filter out the values absent from the list. Specifying `exclude` causes post objects to include all values except the fields you list. For example, the query `exclude=comments` includes everything *except* the comments.

__About the `redirect` argument__
The `redirect` response style is useful for when you need the user's browser to make a request directly rather than making proxy requests using a tool like cURL. Setting a `redirect` argument causes the user's browser to redirect back to the specified URL instead of returning a JSON object. The resulting `status` value is included as an extra query variable.

For example calling an API method with `redirect` set to `http://www.example.com/foo` will result in a redirection to one of the following:

* `http://www.example.com/foo?status=ok`
* `http://www.example.com/foo?status=error`

You can also set separate URLs to handle status values differently. You could set `redirect_ok` to `http://www.example.com/handle_ok` and `redirect_error` to `http://www.example.com/handle_error` in order to have more fine-tuned control over the method result.


== 4. Plugin hooks ==

JSON API exposes several [action and filter hooks](http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters) to augment its behavior.

== Filter: json_api_controllers ==

This filter controls the array of controllers available to JSON API. The callback function is passed a single argument, an array of strings.

= Example =
    
    // Add a custom controller
    add_filter('json_api_controllers', 'add_my_controller');
    
    function add_my_controller($controllers) {
      // Corresponds to the class JSON_API_MyController_Controller
      $controllers[] = 'MyController';
      return $controllers;
    }


== Filter: json_api_*controller*_controller_path

Allows you to specify the PHP source file for a given controller, overriding the default location `wp-content/plugins/json_api/controllers`.

__Note__  
If you place a file called `mycontroller.php` in the controllers, folder JSON API will find it automatically.

= Example =

    // Add a custom controller file for MyController
    add_filter('json_api_mycontroller_controller', 'my_controller_path');
    
    function my_controller_path($default_path) {
      return '/path/to/mycontroller.php';
    }


== Filter: json_api_method ==

The return value of this filter determines which API method will be called.

= Example =
    
    // Restrict to a single IP address
    if ($_SERVER['REMOTE_ADDR'] != '1.2.3.4') {
      add_filter('json_api_method', 'disable_json_api');
    }
    
    function disable_json_api($method) {
      // Returning false as the method disables JSON API
      return false;
    }


== Filter: json_api_encode ==

This is called just before the output is encoded into JSON format. The value passed will always be an associative array, according to the format described in each method's documentation. Those items described in the *Response objects* section are passed as PHP objects, not associative arrays.

= Example =

    add_filter('json_api_encode', 'my_encode_kittens');
    
    function my_encode_kittens($response) {
      if (isset($response['posts'])) {
        foreach ($response['posts'] as $post) {
          my_add_kittens($post); // Add kittens to each post
        }
      } else if (isset($response['post'])) {
        my_add_kittens($response['post']); // Add a kittens property
      }
      return $response;
    }
    
    function my_add_kittens(&$post) {
      $post->kittens = 'Kittens!';
    }

== Action: json_api-*controller*-*method* ==

Each JSON API method invokes an action when called.

= Example =
    
    // Disable get_author_index method (e.g., for security reasons)
    add_action('json_api-core-get_author_index', 'my_disable_author_index');
    
    function my_disable_author_index() {
      // Stop execution
      exit;
    }

== Changelog ==

= 0.9.6 (2010-05-27): =
* Fixed a bug introduced in 0.9.5

= 0.9.5 (2010-05-27): =
* Added a `thumbnail` property to Post response objects

= 0.9.4 (2010-04-28): =
* Fixed a bug where any non-authenticated user could create a draft blog post through `create_post`. Thanks to user futtta for posting about this.

= 0.9.3 (2010-03-19): =
* Fixed a bug where child pages were being ignored by the API. See also: https://core.trac.wordpress.org/ticket/12647

= 0.9.2 (2010-03-18): =
* Fixed a bug where the /api/ rewrite rules would be lost

= 0.9 (2010-02-04): =
* Added a `create_post` method

= 0.8.3 (2010-01-27): =
* Fixed the stable tag version

= 0.8.2 (2010-01-27): =
* Fixed a typo in the changelog

= 0.8.1 (2010-01-27): =
* Fixed a bug that was making JSONP support non-functional

= 0.8 (2010-01-18): =
* Added an attachment model and instance variable for post objects

= 0.7.3 (2010-01-15): =
* Added a `count` request parameter to control the number of posts returned

= 0.7.2 (2010-01-14): =
* Removed the version number from the description text

= 0.7.1 (2010-01-14): =
* Fixed another subtle bug with `get_author_index`

= 0.7 (2010-01-08): =
* Added a `post_count` response to tag objects
* Fixed a bug with `get_author_index`

= 0.6 (2009-11-30): =
* Added `count_total` response
* Added `json_api_encode` filter
* Fixed bugs in the introspector's `get_current_category` and `get_current_tag`

= 0.5 (2009-11-17): =
* Initial Public Release

== Upgrade Notice ==

= 0.9.6 =
Bugfix release for something added in 0.9.5.

= 0.9.5 =
Feature addition: post thumbnails now included in response objects.

= 0.9.4 =
Security fix: all users are strongly encouraged to upgrade. (See Changelog.)

= 0.9.3 =
Fixed a bug where child pages could not be introspected by the API.

= 0.9.2 =
Fixed a bug where the /api/ path would stop working upon publishing new pages.

= 0.9 =
Added a new data manipulation method: `create_post`.

= 0.8.3 =
Oh dear, I didn't tag 0.8.2 in the stable tags thing.

= 0.8.2 =
Just fixing a mislabeled 0.8.1 release in the changelog.

= 0.8.1 =
This is a bug fix release for JSONP support. Thanks to Ben Wilson for reporting it!

= 0.8 =
Added what may be the last introspection feature: post attachments. You can now see images and other media that have been added to posts.
