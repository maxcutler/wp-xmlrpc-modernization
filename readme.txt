=== XML-RPC Modernization ===
Contributors: maxcutler
Tags: xmlrpc, xml-rpc, api
Requires at least: 3.3
Tested up to: 3.4
Stable tag: trunk

This plugin updates the WordPress XML-RPC API to leverage the latest features of WordPress
and move beyond the historical Blogger/metaWeblog/MT APIs.

== Description ==

This plugin updates the WordPress XML-RPC API to leverage the latest features of WordPress
and move beyond the historical Blogger/metaWeblog/MT APIs.

It is derived from Prasath Nadarajah's GSoC '11 project to expand WordPress' web services,
although the exposed API methods are not compatible (different parameter names/types/orders).

WARNING: This plugin should not be used in production, and is intended as a testing ground
for new methods before incorporation into WordPress core.

= Methods =

* wp.newUser - create a new user
* wp.editUser - edit user information
* wp.deleteUser - delete a specfic user
* wp.getUser - get information about a specific user
* wp.getUsers - retrieve a list of users
* wp.getPost  - get a specific post (from any post type)
* wp.getPosts  - get a list of posts
* wp.getPostType - get information about a specific post type
* wp.getPostTypes - get a list of registered post types
* wp.getPostTerms - get terms associated with a post
* wp.setPostTerms - set terms associated with a post
* wp.getTaxonomy - get information about a specific taxonomy
* wp.getTaxonomies  - get a list of registered taxonomies
* wp.newTerm  - create a new term in a taxonomy
* wp.editTerm  - edit a term in a taxonomy
* wp.deleteTerm  - delete a term in a taxonomy
* wp.getTerm  - get information about a specific term in a taxonomy
* wp.getTerms - get a list of term associated with a taxonomy

== Changelog ==

= 0.6 =
* Revised implementations of many methods.
* Added `fields` parameter to wp.getUser and wp.getUsers.
* Updated method docstrings.
* Moved new XML-RPC server class implementation to its own file.
* Added new wp.getUserInfo method.
* Added `group_by_taxonomy` parameter to wp.getPostTerms.

= 0.5 =
* Initial release containing most of the anticipated methods.