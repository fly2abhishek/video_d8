<?php

/**
 * @file
 * Contains \Drupal\system\Form\RssFeedsForm.
 */

namespace Drupal\video_ui\Form;

use Drupal\video\Preset;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class videoPresetConfiguration extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_preset_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // $transcoder = new Transcoder();
    $presets = Preset::getAllPresets();

    $form['video_use_preset_wxh'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use preset dimensions for video conversion.'),
      '#default_value' => \Drupal::config('video.settings')->get('video_use_preset_wxh') ?: FALSE,
      '#description' => t('Override the user selected dimensions with the value from the presets (recommended).')
    );

    if (!empty($presets)) {
      $selected = array_filter(\Drupal::config('video.settings')->get('video_preset') ?: array());

      $form['video_preset'] = array(
        '#tree' => TRUE,
      );

      foreach ($presets as $preset) {
        $delete = NULL;
        if (empty($preset['module']) && !in_array($preset['name'], $selected)) {
          $delete = array('#type' => 'link', '#title' => t('delete'), '#href' => 'admin/config/media/video/presets/preset/' . $preset['name'] . '/delete');
        }
        elseif ($preset['overridden']) {
          $delete = array('#type' => 'link', '#title' => t('revert'), '#href' => 'admin/config/media/video/presets/preset/' . $preset['name'] . '/revert');
        }

        $form['video_preset'][$preset['name']] = array(
          'status' => array(
            '#type' => 'checkbox',
            '#title' => check_plain($preset['name']),
            '#default_value' => in_array($preset['name'], $selected),
          ),
          'description' => array('#markup' => !empty($preset['description']) ? check_plain($preset['description']) : ''),
          'edit' => array('#type' => 'link', '#title' => t('edit'), '#href' => 'admin/config/media/video/presets/preset/' . $preset['name']),
          'delete' => $delete,
          'export' => array('#type' => 'link', '#title' => t('export'), '#href' => 'admin/config/media/video/presets/preset/' . $preset['name'] . '/export'),
        );
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  // //   $userInputValues = $form_state->getUserInput();
  // //   $node_types = NodeType::loadMultiple();

  // //   $config = $this->config('social_stats.settings');

  // //   // Add new index to the config variable per content type.
  // //   foreach ($node_types as $type) {
  // //     $config->set('social_stats_content_types_' . $type->type, serialize($userInputValues['social_stats_' . $type->type]));
  // //   }

  // //   $config->save();
  // //   parent::submitForm($form, $form_state);
  }
}