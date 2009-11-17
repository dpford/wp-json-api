=== JSON API ===
Contributors: dphiffer
Tags: json, api, ajax, cms, admin, integration
Requires at least: 2.8
Tested up to: 2.8
Stable tag: 0.5

A RESTful API for WordPress

== Description ==

This plugin was created for The Museum of Modern Art, whose weblog [Inside/Out](http://moma.org/explore/inside_out) appears within an existing website structure built with Ruby on Rails. Instead of reimplementing the site templates as a WordPress theme, we opted for a Rails front-end that displays content served from a WordPress back-end. This plugin provides the necessary interface for retrieving content and accepting comment submissions.

The current release (0.5) implements a mostly-complete set of introspection methods and a method for submitting comments. I plan on offering a complete set of authentication & data manipulation methods, but my current focus is on features we're actually using at MoMA.org.

See the Other Notes section for complete API documentation.

== Installation ==

1. Upload the `json-api` folder to the `/wp-content/plugins/` directory or install directly through the plugin installer.
1. Activate the plugin through the 'Plugins' menu in WordPress or by using the link provided by the plugin installer.

== Screenshots ==

1. Our old friend, in JSON format

== Requests ==

Requests use a simple REST-style HTTP GET or POST. To invoke the API, include a non-empty query value for `json` in the URL.

JSON API operates in two modes:

1. *Implicit mode* is triggered by setting the `json` query var to a non-empty value on any WordPress page. The content that would normally appear on that page is returned in JSON format.

2. *Explicit mode* is triggered by setting `json` to a known method string. See the API Reference section below for a complete method listing.

== Example requests ==

Implicit mode:

* `http://www.example.org/?json=1`
* `http://www.example.org/?p=47&json=1`
* `http://www.example.org/tag/banana/?json=1`
   
Explicit mode:

* `http://www.example.org/?json=get_recent_posts`
* `http://www.example.org/?json=get_post&post_id=47`
* `http://www.example.org/?json=get_tag_posts&tag_slug=banana`

You can also use a different URL syntax for explicit-mode requests if your weblog is configured to use `mod_rewrite` for permalinks:

* `http://www.example.org/api/get_recent_posts/`
* `http://www.example.org/api/get_post/?post_id=47`
* `http://www.example.org/api/get_tag_posts/?tag_slug=banana`

== Responses ==

The standard response format for JSON API is (as you may have guessed) JSON. For more information about the JSON format, see http://json.org/.

Here is an example response from `http://localhost/wordpress/?json=1` called on a default WordPress installation (formatted for readability):

    {
      "status": "ok",
      "count": 1,
      "pages": 1,
      "posts": [
        {
          "id": 1,
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

1. Request arguments
1. Response objects
1. Introspection methods
1. Data manipulation methods

__About API changes__

All methods are currently subject to change until the plugin reaches maturity. Please read the the changelog carefully before updating to subsequent releases.

== 1. Request arguments ==

The following arguments modify how you get results back from the API. The redirect response styles are intended for use with the data manipulation methods.

* Setting `callback` to a JavaScript function name will trigger a JSONP-style callback.
* Setting `redirect` to a URL will cause the user's browser to redirect to the specified URL with a `status` value appended to the query vars (see the *Response objects* section below for an explanation of status values).
* Setting `redirect_[status]` allows you to control the resulting browser redirection depending on the `status` value.
* Setting `dev` to a non-empty value formats a plain text response using PHP's `print_r()` function.
* Not setting any of the above argument values will result in a standard JSON response.

These arguments are available to modify all introspection methods:

* `date_format` - Changes the format of date values. Uses the same syntax as PHP's date() function. Default value is `Y-m-d H:i:s`.
* `read_more` - Changes the 'read more' link text in post content.
* `include` - Specifies which post data fields to include. Expects a comma-separated list of post fields. Leaving this empty includes *all* fields.
* `exclude` - Specifies which post data fields to exclude. Expects a comma-separated list of post fields.
* `custom_fields` - Includes values from posts' Custom Fields. Expects a comma-separated list of custom field keys.
* `author_meta` - Includes additional author metadata. Should be a comma-separated list of metadata fields.

__About `include`/`exclude` arguments__

The default behavior includes all post values. You only need to specify one of `include` or `exclude` — the former will implicitly leave out those fields you haven't specified and the latter will implicitly include them. For example, a query of `exclude=comments` will include everything *except* the comments, so there's no need to also specify anything with the `include` argument.

== 2. Response types ==

This section describes data objects you can retrieve from WordPress as well as the behavior of URL redirects.

__Status values__

All API requests will result in a status value. The basic values are `ok` and `error`. For certain data manipulation methods, additional status values are available (such as `pending` in the case of a comment submission). Each API method listed below includes its possible status values.

__Naming compatibility__

Developers familiar with WordPress may notice that many names for properties and arguments have been changed. This was a stylistic choice that intends to provide more clarity and consistency in the interface.

__Post response object__

* `id` - Integer
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
* `comment_count` - Integer
* `comment_status` - String (`"open"` or `"closed"`)
* `custom_fields` - Object (included by setting the `custom_fields` argument to a comma-separated list of custom field names)

__Category response object__

* `id` - Integer
* `slug` - String
* `title` - String
* `description` - String
* `parent` - Integer
* `post_count` - Integer

__Tag response object__

* `id` - Integer
* `slug` - String
* `title` - String
* `description` - String

__Author response object__

* `id` - Integer
* `slug` - String
* `name` - String
* `first_name` - String
* `last_name` - String
* `nickname` - String
* `url` - String
* `description` - String
  
Note: You can include additional values by setting the `author_meta` argument to a comma-separated list of metadata fields.

__Comment response object__

* `id` - Integer
* `name` - String
* `url` - String
* `date` - String
* `content` - String
* `parent` - Integer
* `author` - Object (only set if the comment author was registered & logged in)

__Redirects__

Setting the `redirect` argument to `http://www.example.com/foo` will result in one of the following URLs depending on the resulting status value:

* `http://www.example.com/foo?status=ok`
* `http://www.example.com/foo?status=error`

You can alternatively set `redirect_ok` to `http://www.example.com/handle_ok` and `redirect_error` to `http://www.example.com/handle_error` to have more control over the redirect behavior.

== 3. Introspection methods ==

Introspection methods are used to retrieve data from WordPress.


__Method: `get_recent_posts`__

Returns an array of recent posts. You can invoke this from the WordPress home page either by setting `json` to a non-empty value (i.e., `json=1`) or from any page by setting `json=get_recent_posts`.

Optional arguments:

* `page` - return a specific page number from the results

Response format:

    {
      "status": "ok",
      "count": 10,
      "pages": 7,
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }

Status values: `ok`, `error`


__Method: `get_post`__

Returns a single post object.

One of the following is required:
* Invoking the JSON API implicitly (i.e., `?json=1`) on a post URL
* `post_id` - set to the post's ID
* `post_slug` - set to the post's URL slug

Response format:

    {
      "status": "ok",
      "post": { ... }
    }

Status values: `ok`, `error`


__Method: `get_page`__

Returns a single page object.

One of the following is required:

* Invoking the JSON API implicitly (i.e., `?json=1`) on a page URL
* `page_id` - set to the page's ID
* `page_slug` - set to the page's URL slug

Response format:

    {
      "status": "ok",
      "page": { ... }
    }

Status values: `ok`, `error`


__Method: `get_date_posts`__

Returns an array of posts/pages in a specific category.

One of the following is required:

* Invoking the JSON API implicitly (i.e., `?json=1`) on a date archive page
* `date` - set to a date in the format `YYYY` or `YYYYMM` or `YYYYMMDD`

Optional arguments:

* `page` - return a specific page number from the results

Response format:

    {
      "status": "ok",
      "count": 10,
      "pages": 7,
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }

Status values: `ok`, `error`


__Method: `get_category_posts`__

Returns an array of posts/pages in a specific category.

One of the following is required:

* Invoking the JSON API implicitly (i.e., `?json=1`) on a category archive page
* `category_id` - set to the category's ID
* `category_slug` - set to the category's URL slug

Optional arguments:

* `page` - return a specific page number from the results

Response format:

    {
      "status": "ok",
      "count": 10,
      "pages": 7,
      "category": { ... }
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }

Status values: `ok`, `error`


__Method: `get_tag_posts`__

Returns an array of posts/pages with a specific tag.

One of the following is required:

* Invoking the JSON API implicitly (i.e., `?json=1`) on a tag archive page
* `tag_id` - set to the tag's ID
* `tag_slug` - set to the tag's URL slug

Optional arguments:

* `page` - return a specific page number from the results

Response format:

    {
      "status": "ok",
      "count": 10,
      "pages": 7,
      "tag": { ... }
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }

Status values: `ok`, `error`


__Method: `get_author_posts`__

Returns an array of posts/pages written by a specific author.

One of the following is required:

* Invoking the JSON API implicitly (i.e., `?json=1`) on an author archive page
* `author_id` - set to the author's ID
* `author_slug` - set to the author's URL slug

Optional arguments:

* `page` - return a specific page number from the results

Response format:

    {
      "status": "ok",
      "count": 10,
      "pages": 7,
      "author": { ... }
      "posts": [
        { ... },
        { ... },
        { ... }
      ]
    }

Status values: `ok`, `error`


__Method: `get_search_results`__

Returns an array of posts/pages in response to a search query.

One of the following is required:

* Invoking the JSON API implicitly (i.e., `?json=1`) on a search results page
* `search` - set to the desired search query

Optional arguments:

* `page` - return a specific page number from the results

Response format:

    {
      "status": "ok",
      "count": 10,
      "pages": 7,
      "posts": [
        { ... },
        { ... },
        ...
      ]
    }

Status values: `ok`, `error`


__Method: `get_date_index`__

Returns both an array of date page permalinks and a tree structure representation of the archive.

Response format:

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

Note: the tree is arranged by [year] > [month] > [number of posts].

Status values: `ok`, `error`


__Method: `get_category_index`__

Returns an array of active categories.

Response format:

    {
      "status": "ok",
      "count": 3,
      "categories": [
        { ... },
        { ... },
        { ... }
      ]
    }

Status values: `ok`, `error`


== Method: get_tag_index ==

Returns an array of active tags.

Response format:

    {
      "status": "ok",
      "count": 3
      "tags": [
        { ... },
        { ... },
        { ... }
      ]
    }

Status values: `ok`, `error`


== Method: get_author_index ==

Returns an array of active blog authors.

Response format:

    {
      "status": "ok",
      "count": 3,
      "authors": [
        { ... },
        { ... },
        { ... }
      ]
    }

Status values: `ok`, `error`


== 4. Data manipulation methods ==

Data manipulation methods are used for saving content back to WordPress.

**Incomplete**

The data manipulation methods are still very incomplete.


__Method: `submit_comment`__

Submits a comment to a WordPress post.

Required arguments:

* `post_id` - which post to comment on
* `name` - the commenter's name
* `email` - the commenter's email address
* `content` - the comment content

Optional arguments:

* `redirect` - redirect instead of returning a JSON object
* `redirect_ok` - redirect to a specific URL when the status value is `ok`
* `redirect_error` - redirect to a specific URL when the status value is `error`
* `redirect_pending` - redirect to a specific URL when the status value is `pending` (comment pending review)

Status values: `ok`, `error`, `pending`


== Changelog ==

= 0.5 (2009-11-17): =
* Initial Public Release
