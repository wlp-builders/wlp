<?php
/**
 * Upgrade API: Core_Upgrader class
 *
 * @package WLP
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Core class used for updating core.
 *
 * It allows for WLP to upgrade itself in combination with
 * the wp-admin/includes/update-core.php file.
 *
 * @since 2.8.0
 * @since 4.6.0 Moved to its own file from wp-admin/includes/class-wp-upgrader.php.
 *
 * @see WP_Upgrader
 */
class Core_Upgrader extends WP_Upgrader {

	/**
	 * Initialize the upgrade strings.
	 *
	 * @since 2.8.0
	 */
	public function upgrade_strings() {
		$this->strings['up_to_date'] = __( 'WLP is at the latest version.' );
		$this->strings['locked']     = __( 'Another update is currently in progress.' );
		$this->strings['no_package'] = __( 'Update package not available.' );
		/* translators: %s: Package URL. */
		$this->strings['downloading_package']   = sprintf( __( 'Downloading update from %s&#8230;' ), '<span class="code">%s</span>' );
		$this->strings['unpack_package']        = __( 'Unpacking the update&#8230;' );
		$this->strings['copy_failed']           = __( 'Could not copy files.' );
		$this->strings['copy_failed_space']     = __( 'Could not copy files. You may have run out of disk space.' );
		$this->strings['start_rollback']        = __( 'Attempting to roll back to previous version.' );
		$this->strings['rollback_was_required'] = __( 'Due to an error during updating, WLP has rolled back to your previous version.' );
	}

	/**
	 * Upgrade WordPress core.
	 *
	 * For WordPress, this was always '/wordpress/'.  For WLP, since
	 * GitHub builds our zip packages for us, the zip file (and therefore the
	 * directory where it was unpacked) will contain a single directory entry whose
	 * name starts with 'WLP-'.
	 *
	 * We also need to allow the root directory to be called 'wordpress', since
	 * this is used when migrating from WordPress to WLP.  If the
	 * directory is named otherwise, the WordPress updater will reject the update
	 * package for the migration.
	 *
	 * NOTE: This function is duplicated in includes/update-core.php.  This
	 * duplication is intentional, as the load order during an upgrade is quite
	 * complicated and this is the simplest way to make sure that this code is
	 * always available.
	 *
	 * @since CP-1.0.0
	 *
	 * @param string $working_dir The directory where a WLP update package
	 *                            has been extracted.
	 *
	 * @return string|null The root directory entry that contains the new files, or
	 *                     `null` if this does not look like a valid update.
	 */
	public static function get_update_directory_root( $working_dir ) {
		global $wp_filesystem;

		$distro  = null;
		$entries = array_values( $wp_filesystem->dirlist( $working_dir ) );

		if (
			count( $entries ) === 1 &&
			(
				substr( $entries[0]['name'], 0, 13 ) === 'WLP-' ||
				$entries[0]['name'] === 'wordpress' // migration build
			) &&
			$entries[0]['type'] === 'd'
		) {
			$distro = '/' . $entries[0]['name'] . '/';
			$root   = $working_dir . $distro;
			if (
				! $wp_filesystem->exists( $root . 'readme.html' ) ||
				! $wp_filesystem->exists( $root . 'wp-includes/version.php' )
			) {
				$distro = null;
			}
		}

		return $distro;
	}

	/**
	 * Upgrade WLP core.
	 *
	 * @since 2.8.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem                WLP filesystem subclass.
	 * @global callable           $_wp_filesystem_direct_method
	 *
	 * @param object $current Response object for whether WLP is current.
	 * @param array  $args {
	 *     Optional. Arguments for upgrading WLP core. Default empty array.
	 *
	 *     @type bool $pre_check_md5    Whether to check the file checksums before
	 *                                  attempting the upgrade. Default true.
	 *     @type bool $attempt_rollback Whether to attempt to rollback the chances if
	 *                                  there is a problem. Default false.
	 *     @type bool $do_rollback      Whether to perform this "upgrade" as a rollback.
	 *                                  Default false.
	 * }
	 * @return string|false|WP_Error New WordPress version on success, false or WP_Error on failure.
	 */
	public function upgrade( $current, $args = array() ) {
		global $wp_filesystem;

		require_once ABSPATH . WPINC . '/version.php'; // $wp_version;

		$start_time = time();

		$defaults    = array(
			'pre_check_md5'                => true,
			'attempt_rollback'             => false,
			'do_rollback'                  => false,
			'allow_relaxed_file_ownership' => false,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->upgrade_strings();

		// Is an update available?
		if ( ! isset( $current->response ) || 'latest' === $current->response ) {
			return new WP_Error( 'up_to_date', $this->strings['up_to_date'] );
		}

		$res = $this->fs_connect( array( ABSPATH, WP_CONTENT_DIR ), $parsed_args['allow_relaxed_file_ownership'] );
		if ( ! $res || is_wp_error( $res ) ) {
			return $res;
		}

		$wp_dir = trailingslashit( $wp_filesystem->abspath() );

		$partial = true;
		if ( $parsed_args['do_rollback'] ) {
			$partial = false;
		} elseif ( $parsed_args['pre_check_md5'] && ! $this->check_files() ) {
			$partial = false;
		}

		/*
		 * If partial update is returned from the API, use that, unless we're doing
		 * a reinstallation. If we cross the new_bundled version number, then use
		 * the new_bundled zip. Don't though if the constant is set to skip bundled items.
		 * If the API returns a no_content zip, go with it. Finally, default to the full zip.
		 */
		if ( $parsed_args['do_rollback'] && $current->packages->rollback ) {
			$to_download = 'rollback';
		} elseif ( $current->packages->partial && 'reinstall' !== $current->response && $wp_version === $current->partial_version && $partial ) {
			$to_download = 'partial';
		} elseif ( $current->packages->new_bundled && version_compare( $wp_version, $current->new_bundled, '<' )
			&& ( ! defined( 'CORE_UPGRADE_SKIP_NEW_BUNDLED' ) || ! CORE_UPGRADE_SKIP_NEW_BUNDLED ) ) {
			$to_download = 'new_bundled';
		} elseif ( $current->packages->no_content ) {
			$to_download = 'no_content';
		} else {
			$to_download = 'full';
		}

		// Lock to prevent multiple Core Updates occurring.
		$lock = WP_Upgrader::create_lock( 'core_updater', 15 * MINUTE_IN_SECONDS );
		if ( ! $lock ) {
			return new WP_Error( 'locked', $this->strings['locked'] );
		}

		// WLP only supports the "full" upgrade package.
		$download = $this->download_package( $current->packages->full );
		if ( is_wp_error( $download ) ) {
			WP_Upgrader::release_lock( 'core_updater' );
			return $download;
		}

		$working_dir = $this->unpack_package( $download );
		if ( is_wp_error( $working_dir ) ) {
			WP_Upgrader::release_lock( 'core_updater' );
			return $working_dir;
		}

		// Copy update-core.php from the new version into place.
		$distro = self::get_update_directory_root( $working_dir );
		if ( ! $wp_filesystem->copy(
			$working_dir . $distro . 'wp-admin/includes/update-core.php',
			$wp_dir . 'wp-admin/includes/update-core.php',
			true
		) ) {
			$wp_filesystem->delete( $working_dir, true );
			WP_Upgrader::release_lock( 'core_updater' );
			return new WP_Error( 'copy_failed_for_update_core_file', __( 'The update cannot be installed because some files could not be copied. This is usually due to inconsistent file permissions.' ), 'wp-admin/includes/update-core.php' );
		}
		$wp_filesystem->chmod( $wp_dir . 'wp-admin/includes/update-core.php', FS_CHMOD_FILE );

		wp_opcache_invalidate( ABSPATH . 'wp-admin/includes/update-core.php' );
		require_once ABSPATH . 'wp-admin/includes/update-core.php';

		if ( ! function_exists( 'update_core' ) ) {
			WP_Upgrader::release_lock( 'core_updater' );
			return new WP_Error( 'copy_failed_space', $this->strings['copy_failed_space'] );
		}

		$result = update_core( $working_dir, $wp_dir );

		// In the event of an issue, we may be able to roll back.
		if ( $parsed_args['attempt_rollback'] && $current->packages->rollback && ! $parsed_args['do_rollback'] ) {
			$try_rollback = false;
			if ( is_wp_error( $result ) ) {
				$error_code = $result->get_error_code();
				/*
				 * Not all errors are equal. These codes are critical: copy_failed__copy_dir,
				 * mkdir_failed__copy_dir, copy_failed__copy_dir_retry, and disk_full.
				 * do_rollback allows for update_core() to trigger a rollback if needed.
				 */
				if ( str_contains( $error_code, 'do_rollback' ) ) {
					$try_rollback = true;
				} elseif ( str_contains( $error_code, '__copy_dir' ) ) {
					$try_rollback = true;
				} elseif ( 'disk_full' === $error_code ) {
					$try_rollback = true;
				}
			}

			if ( $try_rollback ) {
				/** This filter is documented in wp-admin/includes/update-core.php */
				apply_filters( 'update_feedback', $result );

				/** This filter is documented in wp-admin/includes/update-core.php */
				apply_filters( 'update_feedback', $this->strings['start_rollback'] );

				$rollback_result = $this->upgrade( $current, array_merge( $parsed_args, array( 'do_rollback' => true ) ) );

				$original_result = $result;
				$result          = new WP_Error(
					'rollback_was_required',
					$this->strings['rollback_was_required'],
					(object) array(
						'update'   => $original_result,
						'rollback' => $rollback_result,
					)
				);
			}
		}

		/** This action is documented in wp-admin/includes/class-wp-upgrader.php */
		do_action(
			'upgrader_process_complete',
			$this,
			array(
				'action' => 'update',
				'type'   => 'core',
			)
		);

		// Clear the current updates.
		delete_site_transient( 'update_core' );

		if ( ! $parsed_args['do_rollback'] ) {
			$stats = array(
				'update_type'      => $current->response,
				'success'          => true,
				'fs_method'        => $wp_filesystem->method,
				'fs_method_forced' => defined( 'FS_METHOD' ) || has_filter( 'filesystem_method' ),
				'fs_method_direct' => ! empty( $GLOBALS['_wp_filesystem_direct_method'] ) ? $GLOBALS['_wp_filesystem_direct_method'] : '',
				'time_taken'       => time() - $start_time,
				'reported'         => $wp_version,
				'attempted'        => $current->version,
			);

			if ( is_wp_error( $result ) ) {
				$stats['success'] = false;
				// Did a rollback occur?
				if ( ! empty( $try_rollback ) ) {
					$stats['error_code'] = $original_result->get_error_code();
					$stats['error_data'] = $original_result->get_error_data();
					// Was the rollback successful? If not, collect its error too.
					$stats['rollback'] = ! is_wp_error( $rollback_result );
					if ( is_wp_error( $rollback_result ) ) {
						$stats['rollback_code'] = $rollback_result->get_error_code();
						$stats['rollback_data'] = $rollback_result->get_error_data();
					}
				} else {
					$stats['error_code'] = $result->get_error_code();
					$stats['error_data'] = $result->get_error_data();
				}
			}

			wp_version_check( $stats );
		}

		WP_Upgrader::release_lock( 'core_updater' );

		return $result;
	}

	/**
	 * Determines if this WLP Core version should update to an offered version or not.
	 *
	 * @since 3.7.0
	 *
	 * @param string $offered_ver The offered version, of the format x.y.z.
	 * @return bool True if we should update to the offered version, otherwise false.
	 */
	public static function should_update_to_version( $offered_ver ) {
		require_once ABSPATH . WPINC . '/version.php'; // $cp_version; // x.y.z

		return self::_auto_update_enabled_for_versions(
			$cp_version,
			$offered_ver,
			defined( 'WP_AUTO_UPDATE_CORE' ) ? WP_AUTO_UPDATE_CORE : null
		);
	}

	/**
	 * Determines if an automatic update should be applied for a given set of
	 * versions and auto-update constants.
	 *
	 * @note This method is intended for internal use only!  It is only public
	 * so that it can be unit-tested.
	 *
	 * @since CP-1.0.0
	 *
	 * @param string $ver_current      The current version of WLP.
	 * @param string $ver_offered      The proposed version of WLP.
	 * @param mixed  $auto_update_core The automatic update settings (the value
	 *                                 of the WP_AUTO_UPDATE_CORE constant).
	 * @return bool Whether to apply the proposed update automatically.
	 */
	public static function _auto_update_enabled_for_versions(
		$ver_current,
		$ver_offered,
		$auto_update_core
	) {
		// Parse the version strings.
		$current = self::parse_version_string( $ver_current );
		$offered = self::parse_version_string( $ver_offered );

		// Ensure they are valid.
		if ( ! $current || ! $offered ) {
			return false;
		}

		$failure_data = get_site_option( 'auto_core_update_failed' );
		if ( $failure_data ) {
			// If this was a critical update failure, cannot update.
			if ( ! empty( $failure_data['critical'] ) ) {
				return false;
			}

			// Don't claim we can update on update-core.php if we have a
			// non-critical failure logged.
			if (
				$ver_current == $failure_data['current'] &&
				str_contains( $offered_ver, '.1.next.minor' )
			) {
				return false;
			}

			// Cannot update if we're retrying the same A to B update that
			// caused a non-critical failure.  Some non-critical failures do
			// allow retries, like download_failed.  3.7.1 => 3.7.2
			// resulted in files_not_writable, if we are still on 3.7.1 and
			// still trying to update to 3.7.2.
			if (
				empty( $failure_data['retry'] ) &&
				$ver_current == $failure_data['current'] &&
				$ver_offered == $failure_data['attempted']
			) {
				return false;
			}
		}

		// That concludes all the sanity checks, now we can do some work.

		// Default values for upgrade control flags.
		$upgrade_minor   = true;
		$upgrade_patch   = true;
		$upgrade_dev     = true;
		$upgrade_nightly = true;

		// Process the value from the WP_AUTO_UPDATE_CORE constant.
		if ( ! is_null( $auto_update_core ) ) {
			if ( false === $auto_update_core ) {
				// Disable automatic updates, unless a later filter allows it.
				$upgrade_minor   = false;
				$upgrade_patch   = false;
				$upgrade_dev     = false;
				$upgrade_nightly = false;
			} elseif ( 'patch' === $auto_update_core ) {
				// Only allow patch, nightly, or dev (pre-release) version
				// updates.  If you're running nightly builds, this is probably
				// not the setting you want, because you will still get new
				// minor versions.
				$upgrade_minor = false;
			}
			// Else: Default setting (true, 'minor', or any other unrecognized
			// value). Automatically update to new minor, patch, development
			// (pre-release), or nightly versions.
		}

		// 1.0.0-beta2+nightly.20181019 -> 1.0.0-beta2+nightly.20181020
		// We only need to confirm that the major version is the same and check
		// the nightly build date.
		if (
			$current['nightly'] &&
			$offered['nightly'] &&
			$current['nightly'] < $offered['nightly']
		) {
			/**
			 * Filters whether to enable automatic core updates for nightly
			 * releases.
			 *
			 * @since CP-1.0.0
			 *
			 * @param bool $upgrade_nightly Whether to enable automatic updates
			 *                              for nightly releases.
			 * @param array $current The array of parts of the current version.
			 * @param array $offered The array of parts of the offered version.
			 */
			$upgrade_nightly = apply_filters(
				'allow_nightly_auto_core_updates',
				$upgrade_nightly,
				$current,
				$offered
			);
			// If we're running the same major version as the proposed nightly,
			// then the above filter is all we need and we return its result.
			//
			// If the upgrade was denied via the WP_AUTO_UPDATE_CORE constant
			// or via this filter, return false.
			//
			// Otherwise, fall through to the 'allow_major_auto_core_updates'
			// filter below, because an auto-update to a nightly from a
			// different major version should be specifically approved.
			if ( $current['major'] === $offered['major'] ) {
				return $upgrade_nightly;
			} elseif ( ! $upgrade_nightly ) {
				return false;
			}
		} elseif ( $current['nightly'] || $offered['nightly'] ) {
			// Never auto-update from a nightly build to a non-nightly build,
			// or vice versa.
			return false;

		} elseif ( ! $current['prerelease'] && $offered['prerelease'] ) {
			// If not a nightly build, never auto-update from a release to a
			// pre-release version.
			return false;

		} elseif ( $offered['prerelease'] && (
			$current['major'] !== $offered['major'] ||
			$current['minor'] !== $offered['minor'] ||
			$current['patch'] !== $offered['patch']
		) ) {
			// If not a nightly build, never auto-update to a pre-release of a
			// different semver version.
			return false;
		}

		// Major version updates (1.2.3 -> 2.0.0, 1.2.3 -> 2.3.4).
		if ( $current['major'] > $offered['major'] ) {
			return false;
		} elseif ( $current['major'] < $offered['major'] ) {
			/**
			 * Filters whether to enable automatic core updates to a newer
			 * semver major release.
			 *
			 * @note Be careful with this filter! New major versions of
			 * WLP may contain breaking changes.
			 * @see https://semver.org/
			 *
			 * @since 3.7.0
			 * @since CP-1.0.0 Version numbering scheme changed from WordPress
			 * to WLP (semver). New parameters $current and $offered.
			 *
			 * @param bool $upgrade_major Whether to enable automatic updates
			 *                            to new major versions.
			 * @param array $current The array of parts of the current version.
			 * @param array $offered The array of parts of the offered version.
			 */
			return apply_filters(
				'allow_major_auto_core_updates',
				false,
				$current,
				$offered
			);
		}

		// Minor updates (1.1.3 -> 1.2.0).
		if ( $current['minor'] > $offered['minor'] ) {
			return false;
		} elseif ( $current['minor'] < $offered['minor'] ) {
			/**
			 * Filters whether to enable automatic core updates to a newer
			 * semver minor release.
			 *
			 * @since 3.7.0
			 * @since CP-1.0.0 Version numbering scheme changed from WordPress
			 * to WLP (semver). New parameters $current and $offered.
			 *
			 * @param bool $upgrade_minor Whether to enable automatic updates
			 *                            to a newer semver minor release.
			 * @param array $current The array of parts of the current version.
			 * @param array $offered The array of parts of the offered version.
			 */
			return apply_filters(
				'allow_minor_auto_core_updates',
				$upgrade_minor,
				$current,
				$offered
			);
		}

		// Patch updates (1.0.0 -> 1.0.1 -> 1.0.2).
		if ( $current['patch'] < $offered['patch'] ) {
			/**
			 * Filters whether to enable automatic core updates to a newer
			 * semver patch release.
			 *
			 * @since CP-1.0.0
			 *
			 * @param bool $upgrade_patch Whether to enable automatic updates
			 *                            to a newer semver patch release.
			 * @param array $current The array of parts of the current version.
			 * @param array $offered The array of parts of the offered version.
			 */
			return apply_filters(
				'allow_patch_auto_core_updates',
				$upgrade_patch,
				$current,
				$offered
			);
		}

		// Prerelease versions (same semver version, several different cases).
		if (
			// Update from pre-release to release of the same version.
			( $current['prerelease'] && ! $offered['prerelease'] ) ||
			// Update from pre-release to a later pre-release of the same version.
			( $current['prerelease'] < $offered['prerelease'] )
		) {
			/**
			 * Filters whether to enable automatic core updates from prerelease
			 * versions.
			 *
			 * This filter is called if the current version is a pre-release,
			 * and the offered version is a newer pre-release of the same
			 * semver version, or the final release of the same semver version.
			 *
			 * @since 3.7.0
			 * @since CP-1.0.0 Version numbering scheme changed from WordPress
			 * to WLP (semver). New parameters $current and $offered.
			 *
			 * @param bool $upgrade_dev Whether to enable automatic updates
			 *                          from prereleases.
			 * @param array $current The array of parts of the current version.
			 * @param array $offered The array of parts of the offered version.
			 */
			return apply_filters(
				'allow_dev_auto_core_updates',
				$upgrade_dev,
				$current,
				$offered
			);
		}

		// If we're still not sure, we don't want it.
		return false;
	}

	/**
	 * Parses a version string into an array of parts with named keys.
	 *
	 * For valid version strings, returns an array with keys (`major`, `minor`,
	 * `patch`, `prerelease`, and `nightly`).  The first three are always
	 * present and always an integer, and the rest are always present but may
	 * be `false`.
	 *
	 * If the version string is not of a format recognized by the automatic
	 * update system, then this function returns `null`.
	 *
	 * @param string $version The version string.
	 * @return array|null An array of version parts, or `null`.
	 */
	public static function parse_version_string( $version ) {
		$ok = preg_match(
			// Start of version string.
			'#^' .
			// First 3 parts must be numbers.
			'(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)' .
			// Optional pre-release version (-alpha1, -beta2, -rc1).
			'(-(?P<prerelease>[[:alnum:]]+))?' .
			// Optional migration or nightly build (+nightly.20190208 or
			// +migration.20181220).  Migration builds are treated the same as
			// the corresponding release build.
			'(\+(?P<build_type>migration|nightly)\.(?P<build_number>\d{8}))?' .
			// End of version string.
			'$#',
			$version,
			$matches
		);

		if ( ! $ok ) {
			return null;
		}

		if ( empty( $matches['prerelease'] ) ) {
			$matches['prerelease'] = false;
		}

		if ( isset( $matches['build_type'] ) && $matches['build_type'] === 'nightly' ) {
			$nightly_build = $matches['build_number'];
		} else {
			$nightly_build = false;
		}

		return array(
			'major'      => intval( $matches['major'] ),
			'minor'      => intval( $matches['minor'] ),
			'patch'      => intval( $matches['patch'] ),
			'prerelease' => $matches['prerelease'],
			'nightly'    => $nightly_build,
		);
	}

	/**
	 * Compare the disk file checksums against the expected checksums.
	 *
	 * @since 3.7.0
	 * @since CP-1.3.0 Correctly uses the checksums for the current WLP
	 * version, not the equivalent WordPress version. This function is no
	 * longer used during the core update process.
	 *
	 * @global string $cp_version
	 *
	 * @return bool True if the checksums match, otherwise false.
	 */
	public function check_files() {
		global $cp_version;

		if ( version_compare( $cp_version, '1.3.0-rc1', '<' ) ) {
			// This version of WLP has a `get_core_checksums()`
			// function which incorrectly expects a WordPress version, so there
			// is no point in continuing.
			return false;
		}

		$checksums = get_core_checksums( $cp_version, 'en_US' );

		if ( ! is_array( $checksums ) ) {
			return false;
		}

		foreach ( $checksums as $file => $checksum ) {
			// Skip files which get updated.
			if ( str_starts_with( $file, 'wp-content' ) ) {
				continue;
			}
			if ( ! file_exists( ABSPATH . $file ) || md5_file( ABSPATH . $file ) !== $checksum ) {
				return false;
			}
		}

		return true;
	}
}
