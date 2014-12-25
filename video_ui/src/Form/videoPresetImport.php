<?php

/**
 * @file
 * Contains \Drupal\system\Form\RssFeedsForm.
 */

namespace Drupal\video_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\Preset;

define('VIDEO_PRESET_MAX_LENGTH', 64);


class videoPresetImport extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_preset_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['preset'] = array(
      '#title' => t('Preset code'),
      '#type' => 'textarea',
      '#rows' => 10,
      '#description' => t('Copy the export text and paste it into this text field to import a new preset.'),
      '#wysiwyg' => FALSE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    dsm($form_state);
    // $preset = $form_state['preset'];
    // video_preset_save($preset);
    // drupal_set_message(t('The preset %preset_name has been imported.', array('%preset_name' => $preset['name'])));
    // $form_state['redirect'] = 'admin/config/media/video/presets';

    // $userInputValues = $form_state->getUserInput();
    // $config = \Drupal::config('video.settings');

    // dsm($userInputValues['video_convertor']);
    // $config->set('video_convertor', $userInputValues['video_convertor']);
    // $config->save();
    // parent::submitForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    $preset = '';

    // Get the preset that they declared in the text field.
    ob_start();
    eval($form_state['values']['preset']);
    ob_end_clean();

    if (is_array($preset)) {
      $name = isset($preset['name']) ? $preset['name'] : '';
      if ($error = video_validate_preset_name($name)) {
        form_set_error('name', $error);
      }
    }
    else {
      form_set_error('name', 'Invalid preset import.');
    }

    $form_state['preset'] = &$preset;
  }
}

/**
 * Saves a new preset.
 */
function video_preset_save($preset) {
  // If they provide the pid, then this needs to be an update.
  $pid = (isset($preset['pid']) && $preset['pid']) ? array('pid') : array();
  $object = (object)$preset;

  // Save or update a preset.
  drupal_write_record('video_preset', $object, $pid);

  $preset['pid'] = $object->pid;

  return $preset;
}

/**
 * Verify the syntax of the given prefix name.
 *
 * Borrowed from the user.module.   :)
 */
function video_validate_preset_name($name, $old_name = '') {
  if (empty($name)) {
    return t('You must enter a preset.');
  }
  if (strnatcasecmp($name, $old_name) != 0 && video_preset_name_exists($name)) {
    return t('The preset name %name is already taken.', array('%name' => $name));
  }
  if (is_numeric($name[0])) {
    return t('The preset name cannot begin with a number.');
  }
  if (preg_match('/[^a-z0-9_ \-.\']/i', $name)) {
    return t('The preset name contains an illegal character.');
  }
  if (drupal_strlen($name) > VIDEO_PRESET_MAX_LENGTH) {
    return t('The preset name %name is too long: it must be %max characters or less.', array('%name' => $name, '%max' => VIDEO_PRESET_MAX_LENGTH));
  }

  return NULL;
}

/**
 * Checks to see if another preset is already taken.
 */
function video_preset_name_exists($preset_name) {
  // Get the default presets.
  $default_presets = Preset::getDefaultPresets();

  // See if there is a default preset name.
  if ($default_presets && isset($default_presets[$preset_name])) {
    return TRUE;
  }
  else {
    return (bool) db_select('video_preset', 'p')
    ->fields('p')
    ->condition('p.name', $preset_name)
    ->range(0, 1)
    ->execute()
    ->fetchField();
  }
}