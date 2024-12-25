<?php
/**
 * WLP Upgrade API
 *
 * Most of the functions are pluggable and can be overwritten.
 *
 * @package WLP
 * @subpackage Administration
 */

/** Include user installation customization script. */
if ( file_exists( WP_CONTENT_DIR . '/install.php' ) ) {
	require WP_CONTENT_DIR . '/install.php';
}

/** WLP Administration API */
require_once ABSPATH . 'wp-admin/includes/admin.php';

/** WLP Schema API */
require_once ABSPATH . 'wp-admin/includes/schema.php';

/**
 * Maybe enable pretty permalinks on installation.
 *
 * If after enabling pretty permalinks don't work, fallback to query-string permalinks.
 *
 * @since 4.2.0
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @return bool Whether pretty permalinks are enabled. False otherwise.
 */
function wp_install_maybe_enable_pretty_permalinks() {
	global $wp_rewrite;

	// Bail if a permalink structure is already enabled.
	if ( get_option( 'permalink_structure' ) ) {
		return true;
	}

	/*
	 * The Permalink structures to attempt.
	 *
	 * The first is designed for mod_rewrite or nginx rewriting.
	 *
	 * The second is PATHINFO-based permalinks for web server configurations
	 * without a true rewrite module enabled.
	 */
	$permalink_structures = array(
		'/%year%/%monthnum%/%day%/%postname%/',
		'/index.php/%year%/%monthnum%/%day%/%postname%/',
	);

	foreach ( (array) $permalink_structures as $permalink_structure ) {
		$wp_rewrite->set_permalink_structure( $permalink_structure );

		/*
		 * Flush rules with the hard option to force refresh of the web-server's
		 * rewrite config file (e.g. .htaccess or web.config).
		 */
		$wp_rewrite->flush_rules( true );

		$test_url = '';

		// Test against a real WordPress post.
		$first_post = get_page_by_path( sanitize_title( _x( 'hello-world', 'Default post slug' ) ), OBJECT, 'post' );
		if ( $first_post ) {
			$test_url = get_permalink( $first_post->ID );
		}

		/*
		 * Send a request to the site, and check whether
		 * the 'X-Pingback' header is returned as expected.
		 *
		 * Uses wp_remote_get() instead of wp_remote_head() because web servers
		 * can block head requests.
		 */
		$response          = wp_remote_get( $test_url, array( 'timeout' => 5 ) );
		$x_pingback_header = wp_remote_retrieve_header( $response, 'X-Pingback' );
		$pretty_permalinks = $x_pingback_header && get_bloginfo( 'pingback_url' ) === $x_pingback_header;

		if ( $pretty_permalinks ) {
			return true;
		}
	}

	/*
	 * If it makes it this far, pretty permalinks failed.
	 * Fallback to query-string permalinks.
	 */
	$wp_rewrite->set_permalink_structure( '' );
	$wp_rewrite->flush_rules( true );

	return false;
}

if ( ! function_exists( 'wp_new_blog_notification' ) ) :
	/**
	 * Notifies the site admin that the installation of WordPress is complete.
	 *
	 * Sends an email to the new administrator that the installation is complete
	 * and provides them with a record of their login credentials.
	 *
	 * @since 2.1.0
	 *
	 * @param string $blog_title Site title.
	 * @param string $blog_url   Site URL.
	 * @param int    $user_id    Administrator's user ID.
	 * @param string $password   Administrator's password. Note that a placeholder message is
	 *                           usually passed instead of the actual password.
	 */
	function wp_new_blog_notification( $blog_title, $blog_url, $user_id, $password ) {
		$user      = new WP_User( $user_id );
		$email     = $user->user_email;
		$name      = $user->user_login;
		$login_url = wp_login_url();

		$message = sprintf(
			/* translators: New site notification email. 1: New site URL, 2: User login, 3: User password or password reset link, 4: Login URL. */
			__(
				'Your new WLP site has been successfully set up at:

%1$s

You can log in to the administrator account with the following information:

Username: %2$s
Password: %3$s
Log in here: %4$s

We hope you enjoy your new site. Thanks!

--The WLP Team
https://whitelabelpress.org/
'
			),
			$blog_url,
			$name,
			$password,
			$login_url
		);

		$installed_email = array(
			'to'      => $email,
			'subject' => __( 'New WLP Site' ),
			'message' => $message,
			'headers' => '',
		);

		/**
		 * Filters the contents of the email sent to the site administrator when WordPress is installed.
		 *
		 * @since 5.6.0
		 *
		 * @param array $installed_email {
		 *     Used to build wp_mail().
		 *
		 *     @type string $to      The email address of the recipient.
		 *     @type string $subject The subject of the email.
		 *     @type string $message The content of the email.
		 *     @type string $headers Headers.
		 * }
		 * @param WP_User $user          The site administrator user object.
		 * @param string  $blog_title    The site title.
		 * @param string  $blog_url      The site URL.
		 * @param string  $password      The site administrator's password. Note that a placeholder message
		 *                               is usually passed instead of the user's actual password.
		 */
		$installed_email = apply_filters( 'wp_installed_email', $installed_email, $user, $blog_title, $blog_url, $password );

		wp_mail(
			$installed_email['to'],
			$installed_email['subject'],
			$installed_email['message'],
			$installed_email['headers']
		);
	}
endif;
//
// General functions we use to actually do stuff.
//

/**
 * Creates a table in the database, if it doesn't already exist.
 *
 * This method checks for an existing database and creates a new one if it's not
 * already present. It doesn't rely on MySQL's "IF NOT EXISTS" statement, but chooses
 * to query all tables first and then run the SQL statement creating the table.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $table_name Database table name.
 * @param string $create_ddl SQL statement to create table.
 * @return bool True on success or if the table already exists. False on failure.
 */
function maybe_create_table( $table_name, $create_ddl ) {
	global $wpdb;

	$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

	if ( $wpdb->get_var( $query ) === $table_name ) {
		return true;
	}

	// Didn't find it, so try to create it.
	$wpdb->query( $create_ddl );

	// We cannot directly tell that whether this succeeded!
	if ( $wpdb->get_var( $query ) === $table_name ) {
		return true;
	}

	return false;
}

/**
 * Drops a specified index from a table.
 *
 * @since 1.0.1
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $table Database table name.
 * @param string $index Index name to drop.
 * @return true True, when finished.
 */
function drop_index( $table, $index ) {
	global $wpdb;

	$wpdb->hide_errors();

	$wpdb->query( "ALTER TABLE `$table` DROP INDEX `$index`" );

	// Now we need to take out all the extra ones we may have created.
	for ( $i = 0; $i < 25; $i++ ) {
		$wpdb->query( "ALTER TABLE `$table` DROP INDEX `{$index}_$i`" );
	}

	$wpdb->show_errors();

	return true;
}

/**
 * Adds an index to a specified table.
 *
 * @since 1.0.1
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $table Database table name.
 * @param string $index Database table index column.
 * @return true True, when done with execution.
 */
function add_clean_index( $table, $index ) {
	global $wpdb;

	drop_index( $table, $index );
	$wpdb->query( "ALTER TABLE `$table` ADD INDEX ( `$index` )" );

	return true;
}

/**
 * Adds column to a database table, if it doesn't already exist.
 *
 * @since 1.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $table_name  Database table name.
 * @param string $column_name Table column name.
 * @param string $create_ddl  SQL statement to add column.
 * @return bool True on success or if the column already exists. False on failure.
 */
function maybe_add_column( $table_name, $column_name, $create_ddl ) {
	global $wpdb;

	foreach ( $wpdb->get_col( "DESC $table_name", 0 ) as $column ) {
		if ( $column === $column_name ) {
			return true;
		}
	}

	// Didn't find it, so try to create it.
	$wpdb->query( $create_ddl );

	// We cannot directly tell that whether this succeeded!
	foreach ( $wpdb->get_col( "DESC $table_name", 0 ) as $column ) {
		if ( $column === $column_name ) {
			return true;
		}
	}

	return false;
}

/**
 * If a table only contains utf8 or utf8mb4 columns, convert it to utf8mb4.
 *
 * @since 4.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $table The table to convert.
 * @return bool True if the table was converted, false if it wasn't.
 */
function maybe_convert_table_to_utf8mb4( $table ) {
	global $wpdb;

	$results = $wpdb->get_results( "SHOW FULL COLUMNS FROM `$table`" );
	if ( ! $results ) {
		return false;
	}

	foreach ( $results as $column ) {
		if ( $column->Collation ) {
			list( $charset ) = explode( '_', $column->Collation );
			$charset         = strtolower( $charset );
			if ( 'utf8' !== $charset && 'utf8mb4' !== $charset ) {
				// Don't upgrade tables that have non-utf8 columns.
				return false;
			}
		}
	}

	$table_details = $wpdb->get_row( "SHOW TABLE STATUS LIKE '$table'" );
	if ( ! $table_details ) {
		return false;
	}

	list( $table_charset ) = explode( '_', $table_details->Collation );
	$table_charset         = strtolower( $table_charset );
	if ( 'utf8mb4' === $table_charset ) {
		return true;
	}

	return $wpdb->query( "ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" );
}

/**
 * Retrieve all options as it was for 1.2.
 *
 * @since 1.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return stdClass List of options.
 */
function get_alloptions_110() {
	global $wpdb;
	$all_options = new stdClass();
	$options     = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options" );
	if ( $options ) {
		foreach ( $options as $option ) {
			if ( 'siteurl' === $option->option_name || 'home' === $option->option_name || 'category_base' === $option->option_name ) {
				$option->option_value = untrailingslashit( $option->option_value );
			}
			$all_options->{$option->option_name} = stripslashes( $option->option_value );
		}
	}
	return $all_options;
}

/**
 * Utility version of get_option that is private to installation/upgrade.
 *
 * @ignore
 * @since 1.5.1
 * @access private
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $setting Option name.
 * @return mixed
 */
function __get_option( $setting ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	global $wpdb;

	if ( 'home' === $setting && defined( 'WP_HOME' ) ) {
		return untrailingslashit( WP_HOME );
	}

	if ( 'siteurl' === $setting && defined( 'WP_SITEURL' ) ) {
		return untrailingslashit( WP_SITEURL );
	}

	$option = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", $setting ) );

	if ( 'home' === $setting && ! $option ) {
		return __get_option( 'siteurl' );
	}

	if ( in_array( $setting, array( 'siteurl', 'home', 'category_base', 'tag_base' ), true ) ) {
		$option = untrailingslashit( $option );
	}

	return maybe_unserialize( $option );
}

/**
 * Filters for content to remove unnecessary slashes.
 *
 * @since 1.5.0
 *
 * @param string $content The content to modify.
 * @return string The de-slashed content.
 */
function deslash( $content ) {
	// Note: \\\ inside a regex denotes a single backslash.

	/*
	 * Replace one or more backslashes followed by a single quote with
	 * a single quote.
	 */
	$content = preg_replace( "/\\\+'/", "'", $content );

	/*
	 * Replace one or more backslashes followed by a double quote with
	 * a double quote.
	 */
	$content = preg_replace( '/\\\+"/', '"', $content );

	// Replace one or more backslashes with one backslash.
	$content = preg_replace( '/\\\+/', '\\', $content );

	return $content;
}

/**
 * Modifies the database based on specified SQL statements.
 *
 * Useful for creating new tables and updating existing tables to a new structure.
 *
 * @since 1.5.0
 * @since 6.1.0 Ignores display width for integer data types on MySQL 8.0.17 or later,
 *              to match MySQL behavior. Note: This does not affect MariaDB.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string[]|string $queries Optional. The query to run. Can be multiple queries
 *                                 in an array, or a string of queries separated by
 *                                 semicolons. Default empty string.
 * @param bool            $execute Optional. Whether or not to execute the query right away.
 *                                 Default true.
 * @return array Strings containing the results of the various update queries.
 */
function dbDelta( $queries = '', $execute = true ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	global $wpdb;

	if ( in_array( $queries, array( '', 'all', 'blog', 'global', 'ms_global' ), true ) ) {
		$queries = wp_get_db_schema( $queries );
	}

	// Separate individual queries into an array.
	if ( ! is_array( $queries ) ) {
		$queries = explode( ';', $queries );
		$queries = array_filter( $queries );
	}

	/**
	 * Filters the dbDelta SQL queries.
	 *
	 * @since 3.3.0
	 *
	 * @param string[] $queries An array of dbDelta SQL queries.
	 */
	$queries = apply_filters( 'dbdelta_queries', $queries );

	$cqueries   = array(); // Creation queries.
	$iqueries   = array(); // Insertion queries.
	$for_update = array();

	// Create a tablename index for an array ($cqueries) of queries.
	foreach ( $queries as $qry ) {
		if ( preg_match( '|CREATE TABLE ([^ ]*)|', $qry, $matches ) ) {
			$cqueries[ trim( $matches[1], '`' ) ] = $qry;
			$for_update[ $matches[1] ]            = 'Created table ' . $matches[1];
		} elseif ( preg_match( '|CREATE DATABASE ([^ ]*)|', $qry, $matches ) ) {
			array_unshift( $cqueries, $qry );
		} elseif ( preg_match( '|INSERT INTO ([^ ]*)|', $qry, $matches ) ) {
			$iqueries[] = $qry;
		} elseif ( preg_match( '|UPDATE ([^ ]*)|', $qry, $matches ) ) {
			$iqueries[] = $qry;
		} else {
			// Unrecognized query type.
		}
	}

	/**
	 * Filters the dbDelta SQL queries for creating tables and/or databases.
	 *
	 * Queries filterable via this hook contain "CREATE TABLE" or "CREATE DATABASE".
	 *
	 * @since 3.3.0
	 *
	 * @param string[] $cqueries An array of dbDelta create SQL queries.
	 */
	$cqueries = apply_filters( 'dbdelta_create_queries', $cqueries );

	/**
	 * Filters the dbDelta SQL queries for inserting or updating.
	 *
	 * Queries filterable via this hook contain "INSERT INTO" or "UPDATE".
	 *
	 * @since 3.3.0
	 *
	 * @param string[] $iqueries An array of dbDelta insert or update SQL queries.
	 */
	$iqueries = apply_filters( 'dbdelta_insert_queries', $iqueries );

	$text_fields = array( 'tinytext', 'text', 'mediumtext', 'longtext' );
	$blob_fields = array( 'tinyblob', 'blob', 'mediumblob', 'longblob' );
	$int_fields  = array( 'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint' );

	$global_tables  = $wpdb->tables( 'global' );
	$db_version     = $wpdb->db_version();
	$db_server_info = $wpdb->db_server_info();

	foreach ( $cqueries as $table => $qry ) {
		// Upgrade global tables only for the main site. Don't upgrade at all if conditions are not optimal.
		if ( in_array( $table, $global_tables, true ) && ! wp_should_upgrade_global_tables() ) {
			unset( $cqueries[ $table ], $for_update[ $table ] );
			continue;
		}

		// Fetch the table column structure from the database.
		$suppress    = $wpdb->suppress_errors();
		$tablefields = $wpdb->get_results( "DESCRIBE {$table};" );
		$wpdb->suppress_errors( $suppress );

		if ( ! $tablefields ) {
			continue;
		}

		// Clear the field and index arrays.
		$cfields                  = array();
		$indices                  = array();
		$indices_without_subparts = array();

		// Get all of the field names in the query from between the parentheses.
		preg_match( '|\((.*)\)|ms', $qry, $match2 );
		$qryline = trim( $match2[1] );

		// Separate field lines into an array.
		$flds = explode( "\n", $qryline );

		// For every field line specified in the query.
		foreach ( $flds as $fld ) {
			$fld = trim( $fld, " \t\n\r\0\x0B," ); // Default trim characters, plus ','.

			// Extract the field name.
			preg_match( '|^([^ ]*)|', $fld, $fvals );
			$fieldname            = trim( $fvals[1], '`' );
			$fieldname_lowercased = strtolower( $fieldname );

			// Verify the found field name.
			$validfield = true;
			switch ( $fieldname_lowercased ) {
				case '':
				case 'primary':
				case 'index':
				case 'fulltext':
				case 'unique':
				case 'key':
				case 'spatial':
					$validfield = false;

					/*
					 * Normalize the index definition.
					 *
					 * This is done so the definition can be compared against the result of a
					 * `SHOW INDEX FROM $table_name` query which returns the current table
					 * index information.
					 */

					// Extract type, name and columns from the definition.
					// phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
					preg_match(
						'/^'
						.   '(?P<index_type>'             // 1) Type of the index.
						.       'PRIMARY\s+KEY|(?:UNIQUE|FULLTEXT|SPATIAL)\s+(?:KEY|INDEX)|KEY|INDEX'
						.   ')'
						.   '\s+'                         // Followed by at least one white space character.
						.   '(?:'                         // Name of the index. Optional if type is PRIMARY KEY.
						.       '`?'                      // Name can be escaped with a backtick.
						.           '(?P<index_name>'     // 2) Name of the index.
						.               '(?:[0-9a-zA-Z$_-]|[\xC2-\xDF][\x80-\xBF])+'
						.           ')'
						.       '`?'                      // Name can be escaped with a backtick.
						.       '\s+'                     // Followed by at least one white space character.
						.   ')*'
						.   '\('                          // Opening bracket for the columns.
						.       '(?P<index_columns>'
						.           '.+?'                 // 3) Column names, index prefixes, and orders.
						.       ')'
						.   '\)'                          // Closing bracket for the columns.
						. '$/im',
						$fld,
						$index_matches
					);
					// phpcs:enable

					// Uppercase the index type and normalize space characters.
					$index_type = strtoupper( preg_replace( '/\s+/', ' ', trim( $index_matches['index_type'] ) ) );

					// 'INDEX' is a synonym for 'KEY', standardize on 'KEY'.
					$index_type = str_replace( 'INDEX', 'KEY', $index_type );

					// Escape the index name with backticks. An index for a primary key has no name.
					$index_name = ( 'PRIMARY KEY' === $index_type ) ? '' : '`' . strtolower( $index_matches['index_name'] ) . '`';

					// Parse the columns. Multiple columns are separated by a comma.
					$index_columns                  = array_map( 'trim', explode( ',', $index_matches['index_columns'] ) );
					$index_columns_without_subparts = $index_columns;

					// Normalize columns.
					foreach ( $index_columns as $id => &$index_column ) {
						// Extract column name and number of indexed characters (sub_part).
						// phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
						preg_match(
							'/'
							.   '`?'                      // Name can be escaped with a backtick.
							.       '(?P<column_name>'    // 1) Name of the column.
							.           '(?:[0-9a-zA-Z$_-]|[\xC2-\xDF][\x80-\xBF])+'
							.       ')'
							.   '`?'                      // Name can be escaped with a backtick.
							.   '(?:'                     // Optional sub part.
							.       '\s*'                 // Optional white space character between name and opening bracket.
							.       '\('                  // Opening bracket for the sub part.
							.           '\s*'             // Optional white space character after opening bracket.
							.           '(?P<sub_part>'
							.               '\d+'         // 2) Number of indexed characters.
							.           ')'
							.           '\s*'             // Optional white space character before closing bracket.
							.       '\)'                  // Closing bracket for the sub part.
							.   ')?'
							. '/',
							$index_column,
							$index_column_matches
						);
						// phpcs:enable

						// Escape the column name with backticks.
						$index_column = '`' . $index_column_matches['column_name'] . '`';

						// We don't need to add the subpart to $index_columns_without_subparts
						$index_columns_without_subparts[ $id ] = $index_column;

						// Append the optional sup part with the number of indexed characters.
						if ( isset( $index_column_matches['sub_part'] ) ) {
							$index_column .= '(' . $index_column_matches['sub_part'] . ')';
						}
					}

					// Build the normalized index definition and add it to the list of indices.
					$indices[]                  = "{$index_type} {$index_name} (" . implode( ',', $index_columns ) . ')';
					$indices_without_subparts[] = "{$index_type} {$index_name} (" . implode( ',', $index_columns_without_subparts ) . ')';

					// Destroy no longer needed variables.
					unset( $index_column, $index_column_matches, $index_matches, $index_type, $index_name, $index_columns, $index_columns_without_subparts );

					break;
			}

			// If it's a valid field, add it to the field array.
			if ( $validfield ) {
				$cfields[ $fieldname_lowercased ] = $fld;
			}
		}

		// For every field in the table.
		foreach ( $tablefields as $tablefield ) {
			$tablefield_field_lowercased = strtolower( $tablefield->Field );
			$tablefield_type_lowercased  = strtolower( $tablefield->Type );

			$tablefield_type_without_parentheses = preg_replace(
				'/'
				. '(.+)'       // Field type, e.g. `int`.
				. '\(\d*\)'    // Display width.
				. '(.*)'       // Optional attributes, e.g. `unsigned`.
				. '/',
				'$1$2',
				$tablefield_type_lowercased
			);

			// Get the type without attributes, e.g. `int`.
			$tablefield_type_base = strtok( $tablefield_type_without_parentheses, ' ' );

			// If the table field exists in the field array...
			if ( array_key_exists( $tablefield_field_lowercased, $cfields ) ) {

				// Get the field type from the query.
				preg_match( '|`?' . $tablefield->Field . '`? ([^ ]*( unsigned)?)|i', $cfields[ $tablefield_field_lowercased ], $matches );
				$fieldtype            = $matches[1];
				$fieldtype_lowercased = strtolower( $fieldtype );

				$fieldtype_without_parentheses = preg_replace(
					'/'
					. '(.+)'       // Field type, e.g. `int`.
					. '\(\d*\)'    // Display width.
					. '(.*)'       // Optional attributes, e.g. `unsigned`.
					. '/',
					'$1$2',
					$fieldtype_lowercased
				);

				// Get the type without attributes, e.g. `int`.
				$fieldtype_base = strtok( $fieldtype_without_parentheses, ' ' );

				// Is actual field type different from the field type in query?
				if ( $tablefield->Type != $fieldtype ) {
					$do_change = true;
					if ( in_array( $fieldtype_lowercased, $text_fields, true ) && in_array( $tablefield_type_lowercased, $text_fields, true ) ) {
						if ( array_search( $fieldtype_lowercased, $text_fields, true ) < array_search( $tablefield_type_lowercased, $text_fields, true ) ) {
							$do_change = false;
						}
					}

					if ( in_array( $fieldtype_lowercased, $blob_fields, true ) && in_array( $tablefield_type_lowercased, $blob_fields, true ) ) {
						if ( array_search( $fieldtype_lowercased, $blob_fields, true ) < array_search( $tablefield_type_lowercased, $blob_fields, true ) ) {
							$do_change = false;
						}
					}

					if ( in_array( $fieldtype_base, $int_fields, true ) && in_array( $tablefield_type_base, $int_fields, true )
						&& $fieldtype_without_parentheses === $tablefield_type_without_parentheses
					) {
						/*
						 * MySQL 8.0.17 or later does not support display width for integer data types,
						 * so if display width is the only difference, it can be safely ignored.
						 * Note: This is specific to MySQL and does not affect MariaDB.
						 */
						if ( version_compare( $db_version, '8.0.17', '>=' )
							&& ! str_contains( $db_server_info, 'MariaDB' )
						) {
							$do_change = false;
						}
					}

					if ( $do_change ) {
						// Add a query to change the column type.
						$cqueries[] = "ALTER TABLE {$table} CHANGE COLUMN `{$tablefield->Field}` " . $cfields[ $tablefield_field_lowercased ];

						$for_update[ $table . '.' . $tablefield->Field ] = "Changed type of {$table}.{$tablefield->Field} from {$tablefield->Type} to {$fieldtype}";
					}
				}

				// Get the default value from the array.
				if ( preg_match( "| DEFAULT '(.*?)'|i", $cfields[ $tablefield_field_lowercased ], $matches ) ) {
					$default_value = $matches[1];
					if ( $tablefield->Default != $default_value ) {
						// Add a query to change the column's default value
						$cqueries[] = "ALTER TABLE {$table} ALTER COLUMN `{$tablefield->Field}` SET DEFAULT '{$default_value}'";

						$for_update[ $table . '.' . $tablefield->Field ] = "Changed default value of {$table}.{$tablefield->Field} from {$tablefield->Default} to {$default_value}";
					}
				}

				// Remove the field from the array (so it's not added).
				unset( $cfields[ $tablefield_field_lowercased ] );
			} else {
				// This field exists in the table, but not in the creation queries?
			}
		}

		// For every remaining field specified for the table.
		foreach ( $cfields as $fieldname => $fielddef ) {
			// Push a query line into $cqueries that adds the field to that table.
			$cqueries[] = "ALTER TABLE {$table} ADD COLUMN $fielddef";

			$for_update[ $table . '.' . $fieldname ] = 'Added column ' . $table . '.' . $fieldname;
		}

		// Index stuff goes here. Fetch the table index structure from the database.
		$tableindices = $wpdb->get_results( "SHOW INDEX FROM {$table};" );

		if ( $tableindices ) {
			// Clear the index array.
			$index_ary = array();

			// For every index in the table.
			foreach ( $tableindices as $tableindex ) {
				$keyname = strtolower( $tableindex->Key_name );

				// Add the index to the index data array.
				$index_ary[ $keyname ]['columns'][]  = array(
					'fieldname' => $tableindex->Column_name,
					'subpart'   => $tableindex->Sub_part,
				);
				$index_ary[ $keyname ]['unique']     = ( 0 == $tableindex->Non_unique ) ? true : false;
				$index_ary[ $keyname ]['index_type'] = $tableindex->Index_type;
			}

			// For each actual index in the index array.
			foreach ( $index_ary as $index_name => $index_data ) {

				// Build a create string to compare to the query.
				$index_string = '';
				if ( 'primary' === $index_name ) {
					$index_string .= 'PRIMARY ';
				} elseif ( $index_data['unique'] ) {
					$index_string .= 'UNIQUE ';
				}
				if ( 'FULLTEXT' === strtoupper( $index_data['index_type'] ) ) {
					$index_string .= 'FULLTEXT ';
				}
				if ( 'SPATIAL' === strtoupper( $index_data['index_type'] ) ) {
					$index_string .= 'SPATIAL ';
				}
				$index_string .= 'KEY ';
				if ( 'primary' !== $index_name ) {
					$index_string .= '`' . $index_name . '`';
				}
				$index_columns = '';

				// For each column in the index.
				foreach ( $index_data['columns'] as $column_data ) {
					if ( '' !== $index_columns ) {
						$index_columns .= ',';
					}

					// Add the field to the column list string.
					$index_columns .= '`' . $column_data['fieldname'] . '`';
				}

				// Add the column list to the index create string.
				$index_string .= " ($index_columns)";

				// Check if the index definition exists, ignoring subparts.
				$aindex = array_search( $index_string, $indices_without_subparts, true );
				if ( false !== $aindex ) {
					// If the index already exists (even with different subparts), we don't need to create it.
					unset( $indices_without_subparts[ $aindex ] );
					unset( $indices[ $aindex ] );
				}
			}
		}

		// For every remaining index specified for the table.
		foreach ( (array) $indices as $index ) {
			// Push a query line into $cqueries that adds the index to that table.
			$cqueries[] = "ALTER TABLE {$table} ADD $index";

			$for_update[] = 'Added index ' . $table . ' ' . $index;
		}

		// Remove the original table creation query from processing.
		unset( $cqueries[ $table ], $for_update[ $table ] );
	}

	$allqueries = array_merge( $cqueries, $iqueries );
	if ( $execute ) {
		foreach ( $allqueries as $query ) {
			$wpdb->query( $query );
		}
	}

	return $for_update;
}

/**
 * Updates the database tables to a new schema.
 *
 * By default, updates all the tables to use the latest defined schema, but can also
 * be used to update a specific set of tables in wp_get_db_schema().
 *
 * @since 1.5.0
 *
 * @uses dbDelta
 *
 * @param string $tables Optional. Which set of tables to update. Default is 'all'.
 */
function make_db_current( $tables = 'all' ) {
	$alterations = dbDelta( $tables );
	echo "<ol>\n";
	foreach ( $alterations as $alteration ) {
		echo "<li>$alteration</li>\n";
	}
	echo "</ol>\n";
}

/**
 * Updates the database tables to a new schema, but without displaying results.
 *
 * By default, updates all the tables to use the latest defined schema, but can
 * also be used to update a specific set of tables in wp_get_db_schema().
 *
 * @since 1.5.0
 *
 * @see make_db_current()
 *
 * @param string $tables Optional. Which set of tables to update. Default is 'all'.
 */
function make_db_current_silent( $tables = 'all' ) {
	dbDelta( $tables );
}

/**
 * Creates a site theme from an existing theme.
 *
 * {@internal Missing Long Description}}
 *
 * @since 1.5.0
 *
 * @param string $theme_name The name of the theme.
 * @param string $template   The directory name of the theme.
 * @return bool
 */
function make_site_theme_from_oldschool( $theme_name, $template ) {
	$home_path = get_home_path();
	$site_dir  = WP_CONTENT_DIR . "/themes/$template";

	if ( ! file_exists( "$home_path/index.php" ) ) {
		return false;
	}

	/*
	 * Copy files from the old locations to the site theme.
	 * TODO: This does not copy arbitrary include dependencies. Only the standard WP files are copied.
	 */
	$files = array(
		'index.php'             => 'index.php',
		'wp-layout.css'         => 'style.css',
		'wp-comments.php'       => 'comments.php',
		'wp-comments-popup.php' => 'comments-popup.php',
	);

	foreach ( $files as $oldfile => $newfile ) {
		if ( 'index.php' === $oldfile ) {
			$oldpath = $home_path;
		} else {
			$oldpath = ABSPATH;
		}

		// Check to make sure it's not a new index.
		if ( 'index.php' === $oldfile ) {
			$index = implode( '', file( "$oldpath/$oldfile" ) );
			if ( str_contains( $index, 'WP_USE_THEMES' ) ) {
				if ( ! copy( WP_CONTENT_DIR . '/themes/' . WP_DEFAULT_THEME . '/index.php', "$site_dir/$newfile" ) ) {
					return false;
				}

				// Don't copy anything.
				continue;
			}
		}

		if ( ! copy( "$oldpath/$oldfile", "$site_dir/$newfile" ) ) {
			return false;
		}

		chmod( "$site_dir/$newfile", 0777 );

		// Update the blog header include in each file.
		$lines = explode( "\n", implode( '', file( "$site_dir/$newfile" ) ) );
		if ( $lines ) {
			$f = fopen( "$site_dir/$newfile", 'w' );

			foreach ( $lines as $line ) {
				if ( preg_match( '/require.*wp-blog-header/', $line ) ) {
					$line = '//' . $line;
				}

				// Update stylesheet references.
				$line = str_replace( "<?php echo __get_option('siteurl'); ?>/wp-layout.css", "<?php bloginfo('stylesheet_url'); ?>", $line );

				// Update comments template inclusion.
				$line = str_replace( "<?php include(ABSPATH . 'wp-comments.php'); ?>", '<?php comments_template(); ?>', $line );

				fwrite( $f, "{$line}\n" );
			}
			fclose( $f );
		}
	}

	// Add a theme header.
	$header = "/*\nTheme Name: $theme_name\nTheme URI: " . __get_option( 'siteurl' ) . "\nDescription: A theme automatically created by the update.\nVersion: 1.0\nAuthor: Moi\n*/\n";

	$stylelines = file_get_contents( "$site_dir/style.css" );
	if ( $stylelines ) {
		$f = fopen( "$site_dir/style.css", 'w' );

		fwrite( $f, $header );
		fwrite( $f, $stylelines );
		fclose( $f );
	}

	return true;
}

/**
 * Creates a site theme from the default theme.
 *
 * {@internal Missing Long Description}}
 *
 * @since 1.5.0
 *
 * @param string $theme_name The name of the theme.
 * @param string $template   The directory name of the theme.
 * @return void|false
 */
function make_site_theme_from_default( $theme_name, $template ) {
	$site_dir    = WP_CONTENT_DIR . "/themes/$template";
	$default_dir = WP_CONTENT_DIR . '/themes/' . WP_DEFAULT_THEME;

	// Copy files from the default theme to the site theme.
	// $files = array( 'index.php', 'comments.php', 'comments-popup.php', 'footer.php', 'header.php', 'sidebar.php', 'style.css' );

	$theme_dir = @opendir( $default_dir );
	if ( $theme_dir ) {
		while ( ( $theme_file = readdir( $theme_dir ) ) !== false ) {
			if ( is_dir( "$default_dir/$theme_file" ) ) {
				continue;
			}
			if ( ! copy( "$default_dir/$theme_file", "$site_dir/$theme_file" ) ) {
				return;
			}
			chmod( "$site_dir/$theme_file", 0777 );
		}

		closedir( $theme_dir );
	}

	// Rewrite the theme header.
	$stylelines = explode( "\n", implode( '', file( "$site_dir/style.css" ) ) );
	if ( $stylelines ) {
		$f = fopen( "$site_dir/style.css", 'w' );

		foreach ( $stylelines as $line ) {
			if ( str_contains( $line, 'Theme Name:' ) ) {
				$line = 'Theme Name: ' . $theme_name;
			} elseif ( str_contains( $line, 'Theme URI:' ) ) {
				$line = 'Theme URI: ' . __get_option( 'url' );
			} elseif ( str_contains( $line, 'Description:' ) ) {
				$line = 'Description: Your theme.';
			} elseif ( str_contains( $line, 'Version:' ) ) {
				$line = 'Version: 1';
			} elseif ( str_contains( $line, 'Author:' ) ) {
				$line = 'Author: You';
			}
			fwrite( $f, $line . "\n" );
		}
		fclose( $f );
	}

	// Copy the images.
	umask( 0 );
	if ( ! mkdir( "$site_dir/images", 0777 ) ) {
		return false;
	}

	$images_dir = @opendir( "$default_dir/images" );
	if ( $images_dir ) {
		while ( ( $image = readdir( $images_dir ) ) !== false ) {
			if ( is_dir( "$default_dir/images/$image" ) ) {
				continue;
			}
			if ( ! copy( "$default_dir/images/$image", "$site_dir/images/$image" ) ) {
				return;
			}
			chmod( "$site_dir/images/$image", 0777 );
		}

		closedir( $images_dir );
	}
}

/**
 * Creates a site theme.
 *
 * {@internal Missing Long Description}}
 *
 * @since 1.5.0
 *
 * @return string|false
 */
function make_site_theme() {
	// Name the theme after the blog.
	$theme_name = __get_option( 'blogname' );
	$template   = sanitize_title( $theme_name );
	$site_dir   = WP_CONTENT_DIR . "/themes/$template";

	// If the theme already exists, nothing to do.
	if ( is_dir( $site_dir ) ) {
		return false;
	}

	// We must be able to write to the themes dir.
	if ( ! is_writable( WP_CONTENT_DIR . '/themes' ) ) {
		return false;
	}

	umask( 0 );
	if ( ! mkdir( $site_dir, 0777 ) ) {
		return false;
	}

	if ( file_exists( ABSPATH . 'wp-layout.css' ) ) {
		if ( ! make_site_theme_from_oldschool( $theme_name, $template ) ) {
			// TODO: rm -rf the site theme directory.
			return false;
		}
	} else {
		if ( ! make_site_theme_from_default( $theme_name, $template ) ) {
			// TODO: rm -rf the site theme directory.
			return false;
		}
	}

	// Make the new site theme active.
	$current_template = __get_option( 'template' );
	if ( WP_DEFAULT_THEME == $current_template ) {
		update_option( 'template', $template );
		update_option( 'stylesheet', $template );
	}
	return $template;
}

/**
 * Translate user level to user role name.
 *
 * @since 2.0.0
 *
 * @param int $level User level.
 * @return string User role name.
 */
function translate_level_to_role( $level ) {
	switch ( $level ) {
		case 10:
		case 9:
		case 8:
			return 'administrator';
		case 7:
		case 6:
		case 5:
			return 'editor';
		case 4:
		case 3:
		case 2:
			return 'author';
		case 1:
			return 'contributor';
		case 0:
		default:
			return 'subscriber';
	}
}

/**
 * Checks the version of the installed MySQL binary.
 *
 * @since 2.1.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function wp_check_mysql_version() {
	global $wpdb;
	$result = $wpdb->check_database_version();
	if ( is_wp_error( $result ) ) {
		wp_die( $result );
	}
}

/**
 * Disables the Automattic widgets plugin, which was merged into core.
 *
 * @since 2.2.0
 */
function maybe_disable_automattic_widgets() {
	$plugins = __get_option( 'active_plugins' );

	foreach ( (array) $plugins as $plugin ) {
		if ( 'widgets.php' === basename( $plugin ) ) {
			array_splice( $plugins, array_search( $plugin, $plugins, true ), 1 );
			update_option( 'active_plugins', $plugins );
			break;
		}
	}
}

/**
 * Disables the Link Manager on upgrade if, at the time of upgrade, no links exist in the DB.
 *
 * @since 3.5.0
 *
 * @global int  $wp_current_db_version The old (current) database version.
 * @global wpdb $wpdb                  WordPress database abstraction object.
 */
function maybe_disable_link_manager() {
	global $wp_current_db_version, $wpdb;

	if ( $wp_current_db_version >= 22006 && get_option( 'link_manager_enabled' ) && ! $wpdb->get_var( "SELECT link_id FROM $wpdb->links LIMIT 1" ) ) {
		update_option( 'link_manager_enabled', 0 );
	}
}

/**
 * Runs before the schema is upgraded.
 *
 * @since 2.9.0
 *
 * @global int  $wp_current_db_version The old (current) database version.
 * @global wpdb $wpdb                  WordPress database abstraction object.
 */
function pre_schema_upgrade() {
	global $wp_current_db_version, $wpdb;

	// Upgrade versions prior to 2.9.
	if ( $wp_current_db_version < 11557 ) {
		// Delete duplicate options. Keep the option with the highest option_id.
		$wpdb->query( "DELETE o1 FROM $wpdb->options AS o1 JOIN $wpdb->options AS o2 USING (`option_name`) WHERE o2.option_id > o1.option_id" );

		// Drop the old primary key and add the new.
		$wpdb->query( "ALTER TABLE $wpdb->options DROP PRIMARY KEY, ADD PRIMARY KEY(option_id)" );

		// Drop the old option_name index. dbDelta() doesn't do the drop.
		$wpdb->query( "ALTER TABLE $wpdb->options DROP INDEX option_name" );
	}

	// Multisite schema upgrades.
	if ( $wp_current_db_version < 25448 && is_multisite() && wp_should_upgrade_global_tables() ) {

		// Upgrade versions prior to 3.7.
		if ( $wp_current_db_version < 25179 ) {
			// New primary key for signups.
			$wpdb->query( "ALTER TABLE $wpdb->signups ADD signup_id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST" );
			$wpdb->query( "ALTER TABLE $wpdb->signups DROP INDEX domain" );
		}

		if ( $wp_current_db_version < 25448 ) {
			// Convert archived from enum to tinyint.
			$wpdb->query( "ALTER TABLE $wpdb->blogs CHANGE COLUMN archived archived varchar(1) NOT NULL default '0'" );
			$wpdb->query( "ALTER TABLE $wpdb->blogs CHANGE COLUMN archived archived tinyint(2) NOT NULL default 0" );
		}
	}

	// Upgrade versions prior to 4.2.
	if ( $wp_current_db_version < 31351 ) {
		if ( ! is_multisite() && wp_should_upgrade_global_tables() ) {
			$wpdb->query( "ALTER TABLE $wpdb->usermeta DROP INDEX meta_key, ADD INDEX meta_key(meta_key(191))" );
		}
		$wpdb->query( "ALTER TABLE $wpdb->terms DROP INDEX slug, ADD INDEX slug(slug(191))" );
		$wpdb->query( "ALTER TABLE $wpdb->terms DROP INDEX name, ADD INDEX name(name(191))" );
		$wpdb->query( "ALTER TABLE $wpdb->commentmeta DROP INDEX meta_key, ADD INDEX meta_key(meta_key(191))" );
		$wpdb->query( "ALTER TABLE $wpdb->postmeta DROP INDEX meta_key, ADD INDEX meta_key(meta_key(191))" );
		$wpdb->query( "ALTER TABLE $wpdb->posts DROP INDEX post_name, ADD INDEX post_name(post_name(191))" );
	}

	// Upgrade versions prior to 4.4.
	if ( $wp_current_db_version < 34978 ) {
		// If compatible termmeta table is found, use it, but enforce a proper index and update collation.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->termmeta}'" ) && $wpdb->get_results( "SHOW INDEX FROM {$wpdb->termmeta} WHERE Column_name = 'meta_key'" ) ) {
			$wpdb->query( "ALTER TABLE $wpdb->termmeta DROP INDEX meta_key, ADD INDEX meta_key(meta_key(191))" );
			maybe_convert_table_to_utf8mb4( $wpdb->termmeta );
		}
	}
}

/**
 * Determine if global tables should be upgraded.
 *
 * This function performs a series of checks to ensure the environment allows
 * for the safe upgrading of global WordPress database tables. It is necessary
 * because global tables will commonly grow to millions of rows on large
 * installations, and the ability to control their upgrade routines can be
 * critical to the operation of large networks.
 *
 * In a future iteration, this function may use `wp_is_large_network()` to more-
 * intelligently prevent global table upgrades. Until then, we make sure
 * WordPress is on the main site of the main network, to avoid running queries
 * more than once in multi-site or multi-network environments.
 *
 * @since 4.3.0
 *
 * @return bool Whether to run the upgrade routines on global tables.
 */
function wp_should_upgrade_global_tables() {

	// Return false early if explicitly not upgrading.
	if ( defined( 'DO_NOT_UPGRADE_GLOBAL_TABLES' ) ) {
		return false;
	}

	// Assume global tables should be upgraded.
	$should_upgrade = true;

	// Set to false if not on main network (does not matter if not multi-network).
	if ( ! is_main_network() ) {
		$should_upgrade = false;
	}

	// Set to false if not on main site of current network (does not matter if not multi-site).
	if ( ! is_main_site() ) {
		$should_upgrade = false;
	}

	/**
	 * Filters if upgrade routines should be run on global tables.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $should_upgrade Whether to run the upgrade routines on global tables.
	 */
	return apply_filters( 'wp_should_upgrade_global_tables', $should_upgrade );
}
