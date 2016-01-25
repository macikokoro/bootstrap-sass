<?php

/**
 * @file template.php
 *
 * Holds template-level hooks and preprocessing functions.
 */

define('CENTROID_RESOURCES_WEBFORM_NID_COMPONENT_ID', 4);

/**
 * Implements hook_form_alter().
 *
 * Adds the node a webform was submitted from for downloading purposes.
 *
 * Bit of functionality in the theme here... but it integrates with the
 * templates, so putting here seems most logical.
 */
function centroid_custom_form_alter(&$form, &$form_state, $form_id) {
  if (strpos($form_id, 'webform_client_form') === 0 && !empty($form['submitted']['referring_nid'])) {
    if ($node = menu_get_object()) {
      if (property_exists($node, 'nid')) {
        $form['submitted']['referring_nid']['#value'] = $node->nid;
      }
    }
  }
}

/**
 * Implements theme_preprocess_webform_confirmation().
 *
 * Generates a download link for the file requested.
 */
function centroid_custom_preprocess_webform_confirmation(&$vars) {
  // Lots of checks so things don't error out when everything isn't present,
  // but also don't have a forced nid or something. Note that there /is/ a
  // constant component id, since data fields don't have field names.
  if ($submission = webform_menu_submission_load($vars['sid'], NULL)) {
    if ($referring_nid = $submission->data[CENTROID_RESOURCES_WEBFORM_NID_COMPONENT_ID][0]) {
      try {
        $wnode = entity_metadata_wrapper('node', $referring_nid);

        if (!empty($wnode->field_file) && $file = $wnode->field_file->value()) {
          $file_uri = file_create_url($file['uri']);
          $vars['download_link'] = l(t('Download'), $file_uri, array('attributes' => array('class' => array('btn', 'btn-primary', 'btn-lg'))));
        }
      }
      catch (EntityMetadataWrapperException $exc) {
        watchdog(
          'centroid_custom_theme',
          'EntityMetadataWrapper exception in %function() <pre>@trace</pre>',
          array('%function' => __FUNCTION__, '@trace' => $exc->getTraceAsString()),
          WATCHDOG_ERROR
        );
      }
    }
  }
}

/**
 * Implements theme_preprocess_entity.
 *
 * Wraps field_collection_items in appropriate classes for themeing.
 */
function centroid_custom_preprocess_entity(&$vars) {
  if ($vars['entity_type'] == 'field_collection_item' && $vars['elements']['#bundle'] == 'field_enhanced_content') {
    try {
      $wfc_item = entity_metadata_wrapper('field_collection_item', $vars['field_collection_item']);

      // Look up the settings for this field_collection_item.
      $color = $wfc_item->field_background_color->value();
      $location = $wfc_item->field_media_location->value();

      // Add classes based on the settings for this field_collection_item.
      if (!empty($color)) {
        $vars['classes_array'][] = 'has-color node-bg-color-' . $color;
      }
      if (!empty($location)) {
        $vars['classes_array'][] = 'media-location-' . $location;
      }

      // Not sure the root cause for this, but this fixes the issue...
      $vars['content']['field_image']['#access'] = TRUE;
    }
    catch (EntityMetadataWrapperException $exc) {
      watchdog(
        'centroid_custom_theme',
        'EntityMetadataWrapper exception in %function() <pre>@trace</pre>',
        array('%function' => __FUNCTION__, '@trace' => $exc->getTraceAsString()),
        WATCHDOG_ERROR
      );
    }
  }
}

/**
 * Implements template_preprocess_node.
 *
 * Add classes to allow admins to choose background colors for homepage_teasers.
 */
function centroid_custom_preprocess_node(&$vars) {
	$node = $vars['node'];
	if ($node->type == 'homepage_teaser' && $vars['view_mode'] == 'teaser_featured') {
    // If the values are set, add the classes.
		if (!empty($node->field_background_color['und'][0]['value']) && $bg_color = $node->field_background_color['und'][0]['value']) {
			$vars['classes_array'][] = 'has-color node-bg-color-' . $bg_color;
		}
		if (!empty($node->field_media_location['und'][0]['value']) && $m_location = $node->field_media_location['und'][0]['value']) {
			$vars['classes_array'][] = 'media-location-' . $m_location;
		}
	}
}