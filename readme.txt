=== XML-RPC Modernization ===
Contributors: maxcutler
Tags: xmlrpc, xml-rpc, api
Requires at least: 3.3
Tested up to: 3.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin updates the WordPress XML-RPC API to leverage the latest features of WordPress
and move beyond the historical Blogger/metaWeblog/MT APIs.

== Description ==

This plugin brings XML-RPC API enhancements from the WordPress 3.4 release to previous versions
of WordPress (3.3 and earlier). It also adds new user management methods.

It is derived from Prasath Nadarajah's GSoC '11 project to expand WordPress' web services,
although the exposed API methods are not compatible (different parameter names/types/orders).

= Methods =

New Methods:

* wp.newUser - create a new user
* wp.editUser - edit user information
* wp.deleteUser - delete a specfic user

3.5 Methods for pre-3.5 sites:

* wp.getUser - get information about a specific user
* wp.getUsers - retrieve a list of users
* wp.getProfile - retrieve information about the requesting user
* wp.editProfile - edit the profile of the requesting user
* wp.getRevisions - retrieve revisions for a specific post
* wp.restoreRevision - restore a post revision

3.4 Methods for pre-3.4 sites:

* wp.newPost - create a new post (of any post type)
* wp.editPost - edit a post (of any post type)
* wp.deletePost - delete a post (of any post type)
* wp.getPost  - get a specific post (of any post type)
* wp.getPosts  - get a list of posts
* wp.getPostType - get information about a specific post type
* wp.getPostTypes - get a list of registered post types
* wp.getTaxonomy - get information about a specific taxonomy
* wp.getTaxonomies  - get a list of registered taxonomies
* wp.newTerm  - create a new term in a taxonomy
* wp.editTerm  - edit a term in a taxonomy
* wp.deleteTerm  - delete a term in a taxonomy
* wp.getTerm  - get information about a specific term in a taxonomy
* wp.getTerms - get a list of term associated with a taxonomy

== Changelog ==

= 0.9 =
* Alignment with WordPress core version of wp.getUser and wp.getUsers.
* Renamed wp.getUserInfo to wp.getProfile to match 3.5 core.
* Added wp.editProfile to match WordPress core.
* Added wp.getRevisions and wp.restoreRevision methods to match 3.5 core.
* Added 'post_id" parameter to wp.uploadFile.
* Added 's' parameter to wp.getPosts.
* Added 'if_not_modified_since' parameter to wp.editPost.
* Added 'post_parent', 'guid', 'post_mime_type' and 'menu_order' to _prepare_post.
* Fixed several small bugs in wp.editPost.

= 0.8.2 =
* Added 'attachment_id' to wp.getMediaLibrary and wp.getMediaItem to match 3.4 core.

= 0.8.1 =
* Fixed broken 'number' filter parameter behavior for wp.getPosts.
* Fixed broken 'id' return value of wp.uploadFile and metaWeblog.newMediaObject.

= 0.8 =
* Alignment with WordPress core progress (RC1).
* Removed wp.getPostTerms and wp.setPostTerms.
* Added 'id' to wp.uploadFile return value.
* Added new options for wp.getOptions and wp.setOptions to match 3.4 core.
* Added minimum argument count guards to users methods.
* Added additional fields to wp.newUser and wp.editUser.

= 0.7.5 =
* Alignment with WordPress core progress on post and taxonomy methods.
* Added `filter` parameter to wp.getTerms.
* Added `featured_image` field support for post methods.
* Fixed date-related bugs for draft posts.

= 0.7.1 =
* PHP 5.2.x compatibility fix.
* Typo corrections.
* Alignment with WordPress core version of post methods.

= 0.7 =
* Added wp.newPost, wp.editPost, and wp.deletePost methods.

= 0.6 =
* Revised implementations of many methods.
* Added `fields` parameter to wp.getUser and wp.getUsers.
* Updated method docstrings.
* Moved new XML-RPC server class implementation to its own file.
* Added new wp.getUserInfo method.
* Added `group_by_taxonomy` parameter to wp.getPostTerms.

= 0.5 =
* Initial release containing most of the anticipated methods.