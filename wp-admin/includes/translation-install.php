<?php

// hotfix, replaced with default values
//
/**
 * ClassicPress Translation Installation Administration API
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/**
 * Retrieve translations from WordPress Translation API.
 *
 * @since 4.0.0
 *
 * @param string       $type Type of translations. Accepts 'plugins', 'themes', 'core'.
 * @param array|object $args Translation API arguments. Optional.
 * @return array Returns an empty array by default.
 */
function translations_api( $type, $args = null ) {
    if ( ! in_array( $type, array( 'plugins', 'themes', 'core' ), true ) ) {
        return array();
    }

    // Return default values.
    return array();
}

/**
 * Get available translations from the ClassicPress.net API.
 *
 * @since 4.0.0
 *
 * @return array[] Returns an empty array.
 */
function wp_get_available_translations() {
    return array();
}

/**
 * Output the select form for the language selection on the installation screen.
 *
 * @since 4.0.0
 *
 * @param array[] $languages Array of available languages (populated via the Translation API).
 */
function wp_install_language_form( $languages ) {
    echo "<label class='screen-reader-text' for='language'>Select a default language</label>\n";
    echo "<select size='14' name='language' id='language'>\n";
    echo '<option value="" lang="en" selected data-continue="Continue" data-installed="1">English (United States)</option>';
    echo "\n</select>\n";
    echo '<p class="step"><span class="spinner"></span><input id="language-continue" type="submit" class="button button-primary button-large" value="Continue"></p>';
}

/**
 * Download a language pack.
 *
 * @since 4.0.0
 * @since CP-2.1.0 Added `$force` parameter to allow updates
 *
 * @param string $download Language code to download.
 * @param bool   $force    Optional. If set to true, language pack will be overwritten.
 * @return string|false Returns the language code or false on failure.
 */
function wp_download_language_pack( $download, $force = false ) {
    return $download;
}

/**
 * Check if ClassicPress has access to the filesystem without asking for
 * credentials.
 *
 * @since 4.0.0
 *
 * @return bool Always returns true.
 */
function wp_can_install_language_pack() {
    return true;
}

/**
 * Download new language packs after core update.
 * Called during update_core() from wp-admin/includes/update-core.php
 *
 * @since CP-2.1.0
 *
 * @return void|bool Always returns true.
 */
function maybe_upgrade_translations() {
    return true;
}

