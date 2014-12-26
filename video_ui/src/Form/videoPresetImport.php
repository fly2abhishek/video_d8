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

    $userInputValues = $form_state->getUserInput();
    $preset = $userInputValues['preset'];
    video_preset_save($preset);
    
    drupal_set_message(t('The preset %preset_name has been imported.', array('%preset_name' => $preset['name'])));
    $form_state->setRedirect('video_ui.preset_setting');
  }

    public function validateForm(array &$form, FormStateInterface $form_state) {

      $userInputValues = $form_state->getUserInput();
      $preset = '';

      // Get the preset that they declared in the text field.
      ob_start();
      eval($userInputValues['preset']);
      ob_end_clean();

      if (is_array($preset)) {
        $name = isset($preset['name']) ? $preset['name'] : '';
        if ($error = video_validate_preset_name($name)) {
          $form_state->setErrorByName('name', $error);
        }
      }
      else {
        $form_state->setErrorByName('name', 'Invalid preset import.');
      }

      $userInputValues['preset'] = &$preset;
    }
}