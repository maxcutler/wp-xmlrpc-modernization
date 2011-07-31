WordPress XML-RPC Modernization Plug-in
=======================================

This plugin updates the WordPress XML-RPC API to leverage the latest features of WordPress
and move beyond the historical Blogger/metaWeblog/MT APIs.

It is derived from Prasath Nadarajah's GSoC '11 project to expand WordPress' web services.

Methods
=======
wp.newUser - allows to create a new user
wp.editUser - edit user information
wp.deleteUser - delete a specfic user
wp.getUser - get information about a specific user
wp.getUsers - retrieve a list of users
wp.newPost  - create a new post in any post type
wp.editPost  - edit any post type
wp.deletePost - delete a specific post
wp.getPost  - get any post from any post type
wp.getPosts  - get a list of posts in the blog
wp.getPostType - get information about a specific post type
wp.getPostTypes - get a list of registered taxonomies
wp.getPostTerms - get terms associated with a post
wp.setPostTerms - set terms associated with a post
wp.getTaxonomy - get information about a specific taxonomy
wp.getTaxonomies  - get a list of registered taxonomies
wp.newTerm  - create a new term in a taxonomy
wp.editTerm  - edit a term in a taxonomy
wp.deleteTerm  - delete a term in a taxonomy
wp.getTerm  - get information about a specific term in a taxonomy
wp.getTerms - get a list of term associated with a taxonomy
wp.getSettings - get blog settings
wp.updateSettings - update blog settings