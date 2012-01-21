<?php

include_once( ABSPATH . WPINC . '/class-IXR.php' );
include_once( ABSPATH . WPINC . '/class-wp-xmlrpc-server.php' );

class wp_xmlrpc_server_ext extends wp_xmlrpc_server {

	function __construct() {
		// hook filter to add the new methods after the existing ones are added in the parent constructor
		add_filter( 'xmlrpc_methods' , array( &$this, 'xmlrpc_methods' ) );

		parent::__construct();
	}

	function xmlrpc_methods( $methods ) {
		$new_methods = array();

		// user management
		$new_methods['wp.newUser']          = array( &$this, 'wp_newUser' );
		$new_methods['wp.editUser']         = array( &$this, 'wp_editUser' );
		$new_methods['wp.deleteUser']       = array( &$this, 'wp_deleteUser' );
		$new_methods['wp.getUser']          = array( &$this, 'wp_getUser' );
		$new_methods['wp.getUsers']         = array( &$this, 'wp_getUsers' );

		// custom post type management
		$new_methods['wp.getPost']          = array( &$this, 'wp_getPost' );
		$new_methods['wp.getPosts']         = array( &$this, 'wp_getPosts' );
		$new_methods['wp.getPostTerms']     = array( &$this, 'wp_getPostTerms' );
		$new_methods['wp.setPostTerms']     = array( &$this, 'wp_setPostTerms' );
		$new_methods['wp.getPostType']      = array( &$this, 'wp_getPostType' );
		$new_methods['wp.getPostTypes']     = array( &$this, 'wp_getPostTypes' );

		// custom taxonomy management
		$new_methods['wp.newTerm']          = array( &$this, 'wp_newTerm' );
		$new_methods['wp.editTerm']         = array( &$this, 'wp_editTerm' );
		$new_methods['wp.deleteTerm']       = array( &$this, 'wp_deleteTerm' );
		$new_methods['wp.getTerm']          = array( &$this, 'wp_getTerm' );
		$new_methods['wp.getTerms']         = array( &$this, 'wp_getTerms' );
		$new_methods['wp.getTaxonomy']      = array( &$this, 'wp_getTaxonomy' );
		$new_methods['wp.getTaxonomies']    = array( &$this, 'wp_getTaxonomies' );

		// array_merge will take the values defined in later arguments, so
		// the plugin will not overwrite any methods defined by WP core
		// (i.e., plugin will be forward-compatible with future releases of WordPress
		//  that include these methods built-in)
		return array_merge( $new_methods, $methods );
	}

	/**
	 * Prepares user data for return in an XML-RPC object
	 *
	 * @param WP_User $user The unprepared user object
	 * @param array $fields The subset of user fields to return
	 * @return array The prepared user data
	 */
	function prepare_user( $user, $fields ) {
		$contact_methods = _wp_get_user_contactmethods();

		$user_contacts = array();
		foreach ( $contact_methods as $key => $value ) {
			$user_contacts[$key] = $user->$key;
		}

		$_user = array( 'user_id' => $user->ID );

		$user_fields = array(
			'username'          => $user->user_login,
			'first_name'        => $user->user_firstname,
			'last_name'         => $user->user_lastname,
			'registered'        => new IXR_Date( mysql2date( 'Ymd\TH:i:s', $user->user_registered, false ) ),
			'bio'               => $user->user_description,
			'email'             => $user->user_email,
			'nick_name'         => $user->nickname,
			'nice_name'         => $user->user_nicename,
			'url'               => $user->user_url,
			'display_name'      => $user->display_name,
			'capabilities'      => $user->wp_capabilities,
			'user_level'        => $user->wp_user_level,
			'user_contacts'     => $user_contacts
		);

		if ( in_array( 'all', $fields ) ) {
			$_user = array_merge( $_user, $user_fields);
		}
		else {
			if ( in_array( 'basic', $fields ) ) {
				$basic_fields = array( 'username', 'email', 'registered', 'display_name', 'nice_name' );
				$fields = array_merge( $fields, $basic_fields );
			}
			$requested_fields = array_intersect_key( $user_fields, array_flip( $fields ) );
			$_user = array_merge( $_user, $requested_fields );
		}

		return apply_filters( 'xmlrpc_prepare_user', $_user, $user, $fields );
	}

	/**
	 * Prepares post data for return in an XML-RPC object
	 *
	 * @param array $post The unprepared post data
	 * @param array $fields The subset of post fields to return
	 * @return array The prepared post data
	 */
	function prepare_post( $post, $fields ) {
		// holds the data for this post. built up based on $fields
		$_post = array( 'post_id' => $post['ID'] );

		// prepare common post fields
		$post_fields = array(
			'post_title'        => $post['post_title'],
			'post_date'         => new IXR_Date( mysql2date( 'Ymd\TH:i:s', $post['post_date'], false ) ),
			'post_date_gmt'     => new IXR_Date( mysql2date( 'Ymd\TH:i:s', $post['post_date_gmt'], false ) ),
			'post_modified'     => new IXR_Date( mysql2date( 'Ymd\TH:i:s', $post['post_modified'], false ) ),
			'post_modified_gmt' => new IXR_Date( mysql2date( 'Ymd\TH:i:s', $post['post_modified_gmt'], false ) ),
			'post_status'       => $post['post_status'],
			'post_type'         => $post['post_type'],
			'post_slug'         => $post['post_name'],
			'post_author'       => $post['post_author'],
			'post_password'     => $post['post_password'],
			'post_excerpt'      => $post['post_excerpt'],
			'post_content'      => $post['post_content'],
			'link'              => post_permalink( $post['ID'] ),
			'comment_status'    => $post['comment_status'],
			'ping_status'       => $post['ping_status'],
			'sticky'            => ($post['post_type'] === 'post' && is_sticky( $post['ID'] ) ),
		);

		// Consider future posts as published
		if ( $post_fields['post_status'] === 'future' )
			$post_fields['post_status'] = 'publish';

		// Fill in blank post format
		$post_fields['post_format'] = get_post_format( $post['ID'] );
		if ( empty( $post_fields['post_format'] ) )
			$post_fields['post_format'] = 'standard';

		// Merge requested $post_fields fields into $_post
		if ( in_array( 'post', $fields ) ) {
			$_post = array_merge( $_post, $post_fields );
		} else {
			$requested_fields = array_intersect_key( $post_fields, array_flip( $fields ) );
			$_post = array_merge( $_post, $requested_fields );
		}

		$all_taxonomy_fields = in_array( 'taxonomies', $fields );

		if ( $all_taxonomy_fields || in_array( 'terms', $fields ) ) {
			$post_type_taxonomies = get_object_taxonomies( $post['post_type'], 'names' );
			$terms = wp_get_object_terms( $post['ID'], $post_type_taxonomies );
			$_post['terms'] = array();
			foreach ( $terms as $term ) {
				$_post['terms'][] = $this->prepare_term( $term );
			}
		}

		// backward compatiblity
		if ( $all_taxonomy_fields || in_array( 'tags', $fields ) ) {
			$tagnames = array();
			$tags = wp_get_post_tags( $post['ID'] );
			if ( ! empty( $tags ) ) {
				foreach ( $tags as $tag ) {
					$tagnames[] = $tag->name;
				}
				$tagnames = implode( ', ', $tagnames );
			} else {
				$tagnames = '';
			}
			$_post['tags'] = $tagnames;
		}

		// backward compatiblity
		if ( $all_taxonomy_fields || in_array( 'categories', $fields ) ) {
			$categories = array();
			$catids = wp_get_post_categories( $post['ID'] );
			foreach ( $catids as $catid ) {
				$categories[] = get_cat_name( $catid );
			}
			$_post['categories'] = $categories;
		}

		if ( in_array( 'custom_fields', $fields ) )
			$_post['custom_fields'] = $this->get_custom_fields( $post['ID'] );

		if ( in_array( 'enclosure', $fields ) ) {
			$_post['enclosure'] = array();
			$enclosures = (array) get_post_meta( $post['ID'], 'enclosure' );
			if ( ! empty( $enclosures ) ) {
				$encdata = explode( "\n", $enclosures[0] );
				$_post['enclosure']['url'] = trim( htmlspecialchars( $encdata[0] ) );
				$_post['enclosure']['length'] = (int) trim( $encdata[1] );
				$_post['enclosure']['type'] = trim( $encdata[2] );
			}
		}

		return apply_filters( 'xmlrpc_prepare_post', $_post, $post, $fields );
	}

	/**
	 * Prepares taxonomy data for return in an XML-RPC object
	 *
	 * @param array|object $taxonomy The unprepared taxonomy data
	 * @return array The prepared taxonomy data
	 */
	function prepare_taxonomy( $taxonomy ) {
		$_taxonomy = (array) $taxonomy;

		unset( $_taxonomy['update_count_callback'] );

		return apply_filters( 'xmlrpc_prepare_taxonomy', $_taxonomy, $taxonomy );
	}

	/**
	 * Prepares term data for return in an XML-RPC object
	 *
	 * @param array $term The unprepared term data
	 * @return array The prepared term data
	 */
	function prepare_term( $term ) {
		$_term = (array) $term;

		return apply_filters( 'xmlrpc_prepare_term', $_term, $term );
	}

	/**
	 * Prepares post type data for return in an XML-RPC object
	 *
	 * @param array|object $post_type The unprepared post type data
	 * @return array The prepared post type data
	 */
	function prepare_post_type( $post_type ) {
		$_post_type = (array) $post_type;

		$_post_type['taxonomies'] = get_object_taxonomies( $_post_type['name'] );

		return apply_filters( 'xmlrpc_prepare_post_type', $_post_type, $post_type );
	}

	/**
	 * Create a new user
	 *
	 * @uses wp_insert_user()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - array   $content_struct.
	 *      The $content_struct must contain:
	 *      - 'username'
	 *      - 'password'
	 *      - 'email'
	 *      Also, it can optionally contain:
	 *      - 'role'
	 *      - 'first_name'
	 *      - 'last_name'
	 *      - 'website'
	 *  - boolean $send_mail optional. Defaults to false
	 * @return int user_id
	 */
	function wp_newUser( $args ) {
		$this->escape($args);

		$blog_id        = (int) $args[0];
		$username       = $args[1];
		$password       = $args[2];
		$content_struct = $args[3];
		$send_mail      = isset( $args[4] ) ? $args[4] : false;

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.newUser' );

		if ( ! current_user_can( 'create_users' ) )
			return new IXR_Error( 401, __( 'You are not allowed to create users.' ) );

		// this hold all the user data
		$user_data = array();

		if ( empty( $content_struct['username'] ) )
			return new IXR_Error( 403, __( 'Username cannot be empty.' ) );
		$user_data['user_login'] = $content_struct['username'];

		if ( empty( $content_struct['password'] ) )
			return new IXR_Error( 403, __( 'Password cannot be empty.' ) );
		$user_data['user_pass'] = $content_struct['password'];

		if ( empty( $content_struct['email'] ) )
			return new IXR_Error( 403, __( 'Email cannot be empty.' ) );

		if ( ! is_email( $content_struct['email'] ) )
			return new IXR_Error( 403, __( 'This email address is not valid.' ) );

		if ( email_exists( $content_struct['email'] ) )
			return new IXR_Error( 403, __( 'This email address is already registered.' ) );

		$user_data['user_email'] = $content_struct['email'];

		if ( isset( $content_struct['role'] ) ) {
			if ( get_role( $content_struct['role'] ) === null )
				return new IXR_Error( 403, __( 'The role specified is not valid.' ) );

			$user_data['role'] = $content_struct['role'];
		}

		if ( isset( $content_struct['first_name'] ) )
			$user_data['first_name'] = $content_struct['first_name'];

		if ( isset( $content_struct['last_name'] ) )
			$user_data['last_name'] = $content_struct['last_name'];

		if ( isset( $content_struct['url'] ) )
			$user_data['user_url'] = $content_struct['url'];

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) )
			return new IXR_Error( 500, $user_id->get_error_message() );

		if ( ! $user_id )
			return new IXR_Error( 500, __( 'Sorry, the new user failed.' ) );

		if ( $send_mail ) {
			wp_new_user_notification( $user_id, $user_data['user_pass'] );
		}

		return $user_id;
	}

	/**
	 * Edit a new user
	 *
	 * @uses wp_update_user()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - int     $user_id
	 *  - array   $content_struct.
	 *      It can optionally contain:
	 *      - 'email'
	 *      - 'first_name'
	 *      - 'last_name'
	 *      - 'website'
	 *      - 'role'
	 *      - 'nickname'
	 *      - 'usernicename'
	 *      - 'bio'
	 *      - 'usercontacts'
	 *      - 'password'
	 *  - boolean $send_mail optional. Defaults to false
	 * @return int user_id
	 */
	function wp_editUser( $args ) {
		$this->escape( $args );

		$blog_id        = (int) $args[0];
		$username       = $args[1];
		$password       = $args[2];
		$user_id        = (int) $args[3];
		$content_struct = $args[4];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.editUser' );

		$user_info = get_userdata( $user_id );

		if ( ! $user_info )
			return new IXR_Error( 404, __( 'Invalid user ID.' ) );

		if ( ! ( $user_id == $user->ID || current_user_can( 'edit_users' ) ) )
			return new IXR_Error(401, __( 'Sorry, you cannot edit this user.' ) );

		// holds data of the user
		$user_data = array();
		$user_data['ID'] = $user_id;

		if ( isset( $content_struct['username'] ) && $content_struct['username'] !== $user_info->user_login )
			return new IXR_Error( 401, __( 'Username cannot be changed.' ) );

		if ( isset( $content_struct['email'] ) ) {
			if ( ! is_email( $content_struct['email'] ) )
				return new IXR_Error( 403, __( 'This email address is not valid.' ) );

			// check whether it is already registered
			if ( $content_struct['email'] !== $user_info->user_email && email_exists( $content_struct['email'] ) )
				return new IXR_Error( 403, __( 'This email address is already registered.' ) );

			$user_data['user_email'] = $content_struct['email'];
		}

		if ( isset( $content_struct['role'] ) ) {
			if ( ! current_user_can( 'edit_users' ) )
				return new IXR_Error( 401, __( 'You are not allowed to change roles for this user.' ) );

			if ( get_role( $content_struct['role'] ) === null )
				return new IXR_Error( 403, __( 'The role specified is not valid' ) );

			$user_data['role'] = $content_struct['role'];
		}

		// only set the user details if it was given
		if ( isset( $content_struct['first_name'] ) )
			$user_data['first_name'] = $content_struct['first_name'];

		if ( isset( $content_struct['last_name'] ) )
			$user_data['last_name'] = $content_struct['last_name'];

		if ( isset( $content_struct['website'] ) )
			$user_data['user_url'] = $content_struct['url'];

		if ( isset( $content_struct['nickname'] ) )
			$user_data['nickname'] = $content_struct['nickname'];

		if ( isset( $content_struct['usernicename'] ) )
			$user_data['user_nicename'] = $content_struct['nicename'];

		if ( isset( $content_struct['bio'] ) )
			$user_data['description'] = $content_struct['bio'];

		if ( isset( $content_struct['user_contacts'] ) ) {
			$user_contacts = _wp_get_user_contactmethods( $user_data );
			foreach ( $content_struct['user_contacts'] as $key => $value ) {
				if ( ! array_key_exists( $key, $user_contacts ) )
					return new IXR_Error( 403, __( 'One of the contact method specified is not valid' ) );

				$user_data[ $key ] = $value;
			}
		}

		if ( isset ( $content_struct['password'] ) )
			$user_data['user_pass'] = $content_struct['password'];

		$result = wp_update_user( $user_data );

		if ( is_wp_error( $result ) )
			return new IXR_Error( 500, $result->get_error_message() );

		if ( ! $result )
			return new IXR_Error( 500, __( 'Sorry, the user cannot be updated. Something wrong happened.' ) );

		return $result;
	}

	/**
	 * Delete a user
	 *
	 * @uses wp_delete_user()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - int     $user_id
	 * @return True when user is deleted.
	 */
	function wp_deleteUser( $args ) {
		$this->escape( $args );

		$blog_id    = (int) $args[0];
		$username   = $args[1];
		$password   = $args[2];
		$user_id    = (int) $args[3];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.deleteUser' );

		if ( ! current_user_can( 'delete_users' ) )
			return new IXR_Error( 401, __( 'You are not allowed to delete users.' ) );

		if ( ! get_userdata( $user_id ) )
			return new IXR_Error( 404, __('Invalid user ID.' ) );

		if ( $user->ID == $user_id )
			return new IXR_Error( 401, __( 'You cannot delete yourself.' ) );

		return wp_delete_user( $user_id );
	}

	/**
	 * Retrieve a user.
	 *
	 * The optional $fields parameter specifies what fields will be included
	 * in the response array. This should be a list of field names. 'user_id' will
	 * always be included in the response regardless of the value of $fields.
	 *
	 * Instead of, or in addition to, individual field names, conceptual group
	 * names can be used to specify multiple fields. The available conceptual
	 * groups are 'basic' and 'all'.
	 *
	 * @uses get_userdata()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - int     $user_id
	 *  - array   $fields optional
	 * @return array contains (based on $fields parameter):
	 *  - 'user_id'
	 *  - 'username'
	 *  - 'first_name'
	 *  - 'last_name'
	 *  - 'registered'
	 *  - 'bio'
	 *  - 'email'
	 *  - 'nick_name'
	 *  - 'nice_name'
	 *  - 'url'
	 *  - 'display_name'
	 *  - 'capabilities'
	 *  - 'user_level'
	 *  - 'user_contacts'
	 */
	function wp_getUser( $args ) {
		$this->escape( $args );

		$blog_id    = (int) $args[0];
		$username   = $args[1];
		$password   = $args[2];
		$user_id    = (int) $args[3];

		if ( isset( $args[4] ) )
			$fields = $args[4];
		else
			$fields = apply_filters( 'xmlrpc_default_user_fields', array( 'all' ), 'wp.getUser' );

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getUser' );

		$user_data = get_userdata( $user_id );

		if ( ! $user_data )
			return new IXR_Error(404, __('Invalid user ID'));

		if ( ! ( $user_id == $user->ID || current_user_can( 'edit_users' ) ) )
			return new IXR_Error( 401, __( 'Sorry, you cannot edit users.' ) );

		$user = $this->prepare_user( $user_data, $fields );

		return $user;
	}

	/**
	 * Retrieve users.
	 *
	 * The optional $filter parameter modifies the query used to retrieve users.
	 * Accepted keys are 'number' (default: 50), 'offset' (default: 0), and 'role'.
	 *
	 * The optional $fields parameter specifies what fields will be included
	 * in the response array.
	 *
	 * @uses get_users()
	 * @see wp_getUser() for more on $fields and return values
	 *
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - array   $filter optional
	 *  - array   $fields optional
	 * @return array users data
	 */
	function wp_getUsers( $args ) {
		$this->escape( $args );

		$blog_id    = (int) $args[0];
		$username   = $args[1];
		$password   = $args[2];
		$filter     = isset( $args[3] ) ? $args[3] : array();

		if ( isset( $args[4] ) )
			$fields = $args[4];
		else
			$fields = apply_filters( 'xmlrpc_default_user_fields', array( 'basic' ), 'wp.getUsers' );

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getUsers' );

		if ( ! current_user_can( 'edit_users' ))
			return new IXR_Error( 401, __( 'Sorry, you cannot edit users.' ) );

		$query = array();

		// only retrieve IDs since wp_getUser will ignore anything else
		$query['fields'] = array( 'ID' );

		$query['number'] = ( isset( $filter['number'] ) ) ? absint( $filter['number'] ) : 50;
		$query['offset'] = ( isset( $filter['offset'] ) ) ? absint( $filter['offset'] ) : 0;

		if ( isset( $filter['role'] ) ) {
			if ( get_role( $filter['role'] ) === null )
				return new IXR_Error( 403, __( 'The role specified is not valid' ) );

			$query['role'] = $filter['role'];
		}

		$users = get_users( $query );

		$_users = array();
		foreach ( $users as $user_data ) {
			$_users[] = $this->prepare_user( get_userdata( $user_data->ID ), $fields );
		}

		return $_users;
	}

	/**
	 * Retrieve a post.
	 *
	 * The optional $fields parameter specifies what fields will be included
	 * in the response array. This should be a list of field names. 'post_id' will
	 * always be included in the response regardless of the value of $fields.
	 *
	 * Instead of, or in addition to, individual field names, conceptual group
	 * names can be used to specify multiple fields. The available conceptual
	 * groups are 'post' (all basic fields), 'taxonomies', 'custom_fields',
	 * and 'enclosure'.
	 *
	 * @uses wp_get_single_post()
	 * @param array $args Method parameters. Contains:
	 *  - int     $post_id
	 *  - string  $username
	 *  - string  $password
	 *  - array   $fields optional
	 * @return array contains (based on $fields parameter):
	 *  - 'post_id'
	 *  - 'post_title'
	 *  - 'post_date'
	 *  - 'post_date_gmt'
	 *  - 'post_modified'
	 *  - 'post_modified_gmt'
	 *  - 'post_status'
	 *  - 'post_type'
	 *  - 'post_slug'
	 *  - 'post_author'
	 *  - 'post_password'
	 *  - 'post_excerpt'
	 *  - 'post_content'
	 *  - 'link'
	 *  - 'comment_status'
	 *  - 'ping_status'
	 *  - 'sticky'
	 *  - 'custom_fields'
	 *  - 'terms'
	 *  - 'categories'
	 *  - 'tags'
	 *  - 'enclosure'
	 */
	function wp_getPost( $args ) {
		$this->escape( $args );

		$blog_id            = (int) $args[0];
		$username           = $args[1];
		$password           = $args[2];
		$post_id            = (int) $args[3];

		if ( isset( $args[4] ) )
			$fields = $args[4];
		else
			$fields = apply_filters( 'xmlrpc_default_post_fields', array( 'post', 'terms', 'custom_fields' ), 'wp.getPost' );

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getPost' );

		$post = wp_get_single_post( $post_id, ARRAY_A );

		if ( empty( $post["ID"] ) )
			return new IXR_Error( 404, __( 'Invalid post ID.' ) );

		$post_type = get_post_type_object( $post['post_type'] );
		if ( ! current_user_can( $post_type->cap->edit_posts, $post_id ) )
			return new IXR_Error( 401, __( 'Sorry, you cannot edit this post.' ));

		return $this->prepare_post( $post, $fields );
	}

	/**
	 * Retrieve posts.
	 *
	 * The optional $filter parameter modifies the query used to retrieve posts.
	 * Accepted keys are 'post_type', 'post_status', 'number', 'offset',
	 * 'orderby', and 'order'.
	 *
	 * The optional $fields parameter specifies what fields will be included
	 * in the response array.
	 *
	 * @uses wp_get_recent_posts()
	 * @see wp_getPost() for more on $fields
	 * @see get_posts() for more on $filter values
	 *
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - array   $filter optional
	 *  - array   $fields optional
	 * @return array cntains a collection of posts.
	 */
	function wp_getPosts( $args ) {
		$this->escape( $args );

		$blog_id    = (int) $args[0];
		$username   = $args[1];
		$password   = $args[2];
		$filter     = isset( $args[3] ) ? $args[3] : array();

		if ( isset( $args[4] ) )
			$fields = $args[4];
		else
			$fields = apply_filters( 'xmlrpc_default_post_fields', array( 'post', 'terms', 'custom_fields' ), 'wp.getPosts' );

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getPosts' );

		$query = array();

		if ( isset( $filter['post_type'] ) ) {
			$post_type = get_post_type_object( $filter['post_type'] );
			if ( ! ( (bool)$post_type ) )
				return new IXR_Error( 403, __( 'The post type specified is not valid' ) );

			if ( ! current_user_can( $post_type->cap->edit_posts ) )
				return new IXR_Error( 401, __( 'Sorry, you are not allowed to edit posts in this post type' ));

			$query['post_type'] = $filter['post_type'];
		}

		if ( isset( $filter['post_status'] ) )
			$query['post_status'] = $filter['post_status'];

		if ( isset( $filter['number'] ) )
			$query['number'] = absint( $filter['number'] );

		if ( isset( $filter['offset'] ) )
			$query['offset'] = absint( $filter['offset'] );

		if ( isset( $filter['orderby'] ) ) {
			$query['orderby'] = $filter['orderby'];

			if ( isset( $filter['order'] ) )
				$query['order'] = $filter['order'];
		}

		do_action('xmlrpc_call', 'wp.getPosts');

		$posts_list = wp_get_recent_posts( $query );

		if ( ! $posts_list )
			return array( );

		// holds all the posts data
		$struct = array();

		foreach ( $posts_list as $post ) {
			$post_type = get_post_type_object( $post['post_type'] );
			if ( ! current_user_can( $post_type->cap->edit_posts, $post['ID'] ) )
				continue;

			$struct[] = $this->prepare_post( $post, $fields );
		}

		return $struct;
	}

	/**
	 * Retrieve post terms
	 *
	 * @uses wp_get_object_terms()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - int     $post_id
	 * @return array term data
	 */
	function wp_getPostTerms( $args ) {
		$this->escape( $args );

		$blog_id            = (int) $args[0];
		$username           = $args[1];
		$password           = $args[2];
		$post_id            = (int) $args[3];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getPostTerms' );

		$post = wp_get_single_post( $post_id, ARRAY_A );
		if ( empty( $post['ID'] ) )
			return new IXR_Error( 404, __( 'Invalid post ID.' ) );

		$post_type = get_post_type_object( $post['post_type'] );

		if ( ! current_user_can( $post_type->cap->edit_post , $post_id ) )
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to edit this post.' ) );

		$taxonomies = get_taxonomies( '' );

		$terms = wp_get_object_terms( $post_id , $taxonomies );

		if ( is_wp_error( $terms ) )
			return new IXR_Error( 500 , $terms->get_error_message() );

		$struct = array();

		foreach ( $terms as $term ) {
			$struct[] = $this->prepare_term( $term );
		}

		return $struct;
	}

	/**
	 * Set post terms
	 *
	 * @uses wp_set_object_terms()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - int     $post_id
	 *  - array   $content_struct contains term_ids with taxonomy as keys
	 *  - bool    $append
	 * @return boolean true
	 */
	function wp_setPostTerms( $args ) {
		$this->escape($args);

		$blog_id            = (int) $args[0];
		$username           = $args[1];
		$password           = $args[2];
		$post_ID            = (int) $args[3];
		$content_struct     = $args[4];
		$append             = $args[5] ? true : false;

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.setPostTerms' );

		$post = wp_get_single_post( $post_ID, ARRAY_A );
		if ( empty( $post['ID'] ) )
			return new IXR_Error( 404, __( 'Invalid post ID.' ) );

		$post_type = get_post_type_object( $post['post_type'] );

		if ( ! current_user_can( $post_type->cap->edit_post , $post_ID ) )
			return new IXR_Error( 401, __( 'Sorry, You are not allowed to edit this post.' ) );

		$post_type_taxonomies = get_object_taxonomies( $post['post_type'] );

		$taxonomies = array_keys( $content_struct );

		// validating term ids
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! in_array( $taxonomy , $post_type_taxonomies ) )
				return new IXR_Error( 401, __( 'Sorry, one of the given taxonomy is not supported by the post type.' ) );

			$term_ids = $content_struct[$taxonomy];
			foreach ( $term_ids as $term_id ) {

				$term = get_term( $term_id, $taxonomy );

				if ( is_wp_error( $term ) )
					return new IXR_Error( 500, $term->get_error_message() );

				if ( ! $term )
					return new IXR_Error( 403, __( 'Invalid term ID' ) );
			}
		}

		foreach ( $taxonomies as $taxonomy ) {
			$term_ids = $content_struct[$taxonomy];
			$term_ids = array_map( 'intval', $term_ids );
			$term_ids = array_unique( $term_ids );
			wp_set_object_terms( $post_ID , $term_ids, $taxonomy , $append );
		}

		return true;
	}

	/**
	 * Retrieves a post type
	 *
	 * @uses get_post_type_object()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - string  $post_type_name
	 * @return array contains:
	 *  - 'labels'
	 *  - 'description'
	 *  - 'capability_type'
	 *  - 'cap'
	 *  - 'map_meta_cap'
	 *  - 'hierarchical'
	 *  - 'menu_position'
	 *  - 'taxonomies'
	 */
	function wp_getPostType( $args ) {
		$this->escape( $args );

		$blog_id        = (int) $args[0];
		$username       = $args[1];
		$password       = $args[2];
		$post_type_name = $args[3];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getPostType' );

		if ( ! post_type_exists( $post_type_name ) )
			return new IXR_Error( 403, __( 'Invalid post type.' ) );

		$post_type = get_post_type_object( $post_type_name );

		if ( ! current_user_can( $post_type->cap->edit_posts ) )
			return new IXR_Error( 401, __( 'Sorry, you are not allowed to edit this post type.' ) );

		return $this->prepare_post_type( $post_type );
	}

	/**
	 * Retrieves a post types
	 *
	 * @uses get_post_types()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 * @return array
	 */
	function wp_getPostTypes( $args ) {
		$this->escape( $args );

		$blog_id            = (int) $args[0];
		$username           = $args[1];
		$password           = $args[2];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getPostTypes' );

		$post_types = get_post_types( '', 'objects' );

		$struct = array();

		foreach ( $post_types as $post_type ) {
			if ( ! current_user_can( $post_type->cap->edit_posts ) )
				continue;

			$struct[$post_type->name] = $this->prepare_post_type( $post_type );
		}

		return $struct;
	}

	/**
	 * Create a new term
	 *
	 * @uses wp_insert_term()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - array   $content_struct.
	 *      The $content_struct must contain:
	 *      - 'name'
	 *      - 'taxonomy'
	 *      Also, it can optionally contain:
	 *      - 'parent'
	 *      - 'description'
	 *      - 'slug'
	 * @return int term_id
	 */
	function wp_newTerm( $args ) {
		$this->escape( $args );

		$blog_id            = (int) $args[0];
		$username           = $args[1];
		$password           = $args[2];
		$content_struct     = $args[3];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.newTerm' );

		if ( ! taxonomy_exists( $content_struct['taxonomy'] ) )
			return new IXR_Error( 403, __( 'Invalid taxonomy.' ) );

		$taxonomy = get_taxonomy( $content_struct['taxonomy'] );

		if ( ! current_user_can( $taxonomy->cap->manage_terms ) )
			return new IXR_Error( 401, __( 'You are not allowed to create terms in this taxonomy.' ) );

		$taxonomy = (array) $taxonomy;

		// hold the data of the term
		$term_data = array();

		$term_data['name'] = trim( $content_struct['name'] );
		if ( empty( $term_data['name'] ) )
			return new IXR_Error( 403, __( 'The term name cannot be empty.' ) );

		if ( isset( $content_struct['parent'] ) ) {
			if ( ! $taxonomy['hierarchical'] )
				return new IXR_Error( 403, __( 'This taxonomy is not hierarchical.' ) );

			$parent_term_id = (int) $content_struct['parent'];
			$parent_term = get_term( $parent_term_id , $taxonomy['name'] );

			if ( is_wp_error( $parent_term ) )
				return new IXR_Error( 500, $parent_term->get_error_message() );

			if ( ! $parent_term )
				return new IXR_Error( 500, __('Parent term does not exist.') );

			$term_data['parent'] = $content_struct['parent'];
		}

		if ( isset( $content_struct['description'] ) )
			$term_data['description'] = $content_struct['description'];

		if ( isset( $content_struct['slug'] ) )
			$term_data['slug'] = $content_struct['slug'];

		$term = wp_insert_term( $term_data['name'] , $taxonomy['name'] , $term_data );

		if ( is_wp_error( $term ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $term )
			return new IXR_Error( 500, __('Sorry, your term could not be created. Something wrong happened.') );

		return $term['term_id'];
	}

	/**
	 * Edit a term
	 *
	 * @uses wp_update_term()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - int     $term_id
	 *  - array   $content_struct.
	 *      The $content_struct must contain:
	 *      - 'taxonomy'
	 *      Also, it can optionally contain:
	 *      - 'name'
	 *      - 'parent'
	 *      - 'description'
	 *      - 'slug'
	 * @return int term_id
	 */
	function wp_editTerm( $args ) {
		$this->escape( $args );

		$blog_id            = (int) $args[0];
		$username           = $args[1];
		$password           = $args[2];
		$term_id            = (int) $args[3];
		$content_struct     = $args[4];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.editTerm' );

		if ( ! taxonomy_exists( $content_struct['taxonomy'] ) )
			return new IXR_Error( 403, __( 'Invalid taxonomy.' ) );

		$taxonomy = get_taxonomy( $content_struct['taxonomy'] );

		if ( ! current_user_can( $taxonomy->cap->edit_terms ) )
			return new IXR_Error( 401, __( 'You are not allowed to edit terms in this taxonomy.' ) );

		$taxonomy = (array) $taxonomy;

		// hold the data of the term
		$term_data = array();

		$term = get_term( $term_id , $content_struct['taxonomy'] );

		if ( is_wp_error( $term ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $term )
			return new IXR_Error( 404, __( 'Invalid term ID.' ) );

		if ( isset( $content_struct['name'] ) ) {
			$term_data['name'] = trim( $content_struct['name'] );

			if ( empty( $term_data['name'] ) )
				return new IXR_Error( 403, __( 'The term name cannot be empty.' ) );
		}

		if ( isset( $content_struct['parent'] ) ) {
			if ( ! $taxonomy['hierarchical'] )
				return new IXR_Error( 403, __( 'This taxonomy is not hierarchical.' ) );

			$parent_term_id = (int) $content_struct['parent'];
			$parent_term = get_term( $parent_term_id , $taxonomy['name'] );

			if ( is_wp_error( $parent_term) )
				return new IXR_Error( 500, $term->get_error_message() );

			if ( ! $parent_term )
				return new IXR_Error( 403, __( 'Invalid parent term ID.' ) );

			$term_data['parent'] = $content_struct['parent'];
		}

		if ( isset( $content_struct['description'] ) )
			$term_data['description'] = $content_struct['description'];

		if ( isset( $content_struct['slug'] ) )
			$term_data['slug'] = $content_struct['slug'];

		$term = wp_update_term( $term_id , $taxonomy['name'] , $term_data );

		if ( is_wp_error( $term ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $term )
			return new IXR_Error( 500, __('Sorry, editing the term failed.') );

		return $term['term_id'];
	}

	/**
	 * Delete a  term
	 *
	 * @uses wp_delete_term()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - string  $taxnomy_name
	 *  - int     $term_id
	 * @return boolean true
	 */
	function wp_deleteTerm( $args ) {
		$this->escape( $args );

		$blog_id            = (int) $args[0];
		$username           = $args[1];
		$password           = $args[2];
		$taxonomy_name      = $args[3];
		$term_id            = (int) $args[4];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.editTerm' );

		if ( ! taxonomy_exists( $taxonomy_name ) )
			return new IXR_Error( 403, __( 'Invalid taxonomy.' ) );

		$taxonomy = get_taxonomy( $taxonomy_name );

		if ( ! current_user_can( $taxonomy->cap->delete_terms ) )
			return new IXR_Error( 401, __( 'You are not allowed to delete terms in this taxonomy.' ) );

		$term = get_term( $term_id, $taxonomy_name );

		if ( is_wp_error( $term ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $term )
			return new IXR_Error( 404, __('Invalid term ID.') );

		$result = wp_delete_term( $term_id, $taxonomy_name );

		if ( is_wp_error( $result ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $result )
			return new IXR_Error( 500, __('Sorry, deleting the term failed.') );

		return $result;
	}

	/**
	 * Retrieve a term
	 *
	 * @uses get_term()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - string  $taxonomy_name
	 *  - int     $term_id
	 * @return array contains:
	 *  - 'term_id'
	 *  - 'name'
	 *  - 'slug'
	 *  - 'term_group'
	 *  - 'term_taxonomy_id'
	 *  - 'taxonomy'
	 *  - 'description'
	 *  - 'parent'
	 *  - 'count'
	 */
	function wp_getTerm( $args ) {
		$this->escape( $args );

		$blog_id            = (int) $args[0];
		$username           = $args[1];
		$password           = $args[2];
		$taxonomy_name      = $args[3];
		$term_id            = (int)$args[4];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getTerm' );

		if ( ! taxonomy_exists( $taxonomy_name ) )
			return new IXR_Error( 403, __( 'Invalid taxonomy name.' ) );

		$taxonomy = get_taxonomy( $taxonomy_name );

		if ( ! current_user_can( $taxonomy->cap->assign_terms ) )
			return new IXR_Error( 401, __( 'You are not allowed to assign terms in this taxonomy.' ) );

		$term = get_term( $term_id , $taxonomy_name );

		if ( is_wp_error( $term ) )
			return new IXR_Error( 500, $term->get_error_message() );

		if ( ! $term )
			return new IXR_Error( 404, __( 'Invalid term ID.' ) );

		return $this->prepare_term( $term );
	}

	/**
	 * Retrieve terms
	 *
	 * @uses get_terms()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - string   $taxonomy_name
	 * @return array terms
	 */
	function wp_getTerms( $args ) {
		$this->escape( $args );

		$blog_id        = (int) $args[0];
		$username       = $args[1];
		$password       = $args[2];
		$taxonomy_name  = $args[3];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getTerms' );

		if ( ! taxonomy_exists( $taxonomy_name ) )
			return new IXR_Error( 403, __( 'Invalid taxonomy name.' ) );

		$taxonomy = get_taxonomy( $taxonomy_name );

		if ( ! current_user_can( $taxonomy->cap->assign_terms ) )
			return new IXR_Error( 401, __( 'You are not allowed to assign terms in this taxonomy.' ) );

		$terms = get_terms( $taxonomy_name , array( 'get' => 'all' ) );

		if ( is_wp_error( $terms ) )
			return new IXR_Error( 500, $terms->get_error_message() );

		$struct = array();

		foreach ( $terms as $term ) {
			$struct[] = $this->prepare_term( $term );
		}

		return $struct;
	}

	/**
	 * Retrieve a taxonomy
	 *
	 * @uses get_taxonomy()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 *  - string  $taxonomy_name
	 * @return array (@see get_taxonomy())
	 */
	function wp_getTaxonomy( $args ) {
		$this->escape( $args );

		$blog_id        = (int) $args[0];
		$username       = $args[1];
		$password       = $args[2];
		$taxonomy_name  = $args[3];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getTaxonomy' );

		if ( ! taxonomy_exists( $taxonomy_name ) )
			return new IXR_Error( 403, __( 'The taxonomy type specified is not valid' ) );

		$taxonomy = get_taxonomy( $taxonomy_name );

		if ( ! current_user_can( $taxonomy->cap->edit_terms ) )
			return new IXR_Error( 401, __( 'Sorry, You are not allowed to edit this post type' ) );

		return $this->prepare_taxonomy( $taxonomy );
	}

	/**
	 * Retrieve taxonomies
	 *
	 * @uses get_taxonomies()
	 * @param array $args Method parameters. Contains:
	 *  - int     $blog_id
	 *  - string  $username
	 *  - string  $password
	 * @return array taxonomies
	 */
	function wp_getTaxonomies($args) {
		$this->escape( $args );

		$blog_id            = (int) $args[0];
		$username           = $args[1];
		$password           = $args[2];

		if ( ! $user = $this->login( $username, $password ) )
			return $this->error;

		do_action( 'xmlrpc_call', 'wp.getTaxonomies' );

		$taxonomies = get_taxonomies( '', 'objects' );

		// holds all the taxonomy data
		$struct = array();

		foreach ( $taxonomies as $taxonomy ) {
			// capability check for post_types
			if ( ! current_user_can( $taxonomy->cap->edit_terms ) )
				continue;

			$struct[] = $this->prepare_taxonomy( $taxonomy );
		}

		return $struct;
	}
}

?>