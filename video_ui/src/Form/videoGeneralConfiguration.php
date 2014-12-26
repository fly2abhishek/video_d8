<?php

/**
 * @file
 * Contains \Drupal\system\Form\RssFeedsForm.
 */

namespace Drupal\video_ui\Form;

use Drupal\video\Videoutility;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class videoGeneralConfiguration extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_general_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $config = \Drupal::config('video_ui.settings');
    $hastranscoder = $config->get('video_convertor') !== '';
    
    $form = array();
    $form['video_autoplay'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically start video on page load'),
      '#default_value' => $config->get('video_autoplay') ?: FALSE,
      '#description' => t('Start the video when the page and video loads'),
    );
    $form['video_autobuffering'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically start video buffering'),
      '#default_value' => $config->get('video_autobuffering') ?: TRUE,
      '#description' => t('Start buffering video when the page and video loads'),
    );
    $form['video_bypass_conversion'] = array(
      '#type' => 'checkbox',
      '#title' => t('Bypass video conversion'),
      '#default_value' => $config->get('video_bypass_conversion') ?: FALSE,
      '#description' => t('Bypass video conversion when creating videos.'),
      '#access' => $hastranscoder,
    );
    $form['video_convert_on_save'] = array(
      '#type' => 'checkbox',
      '#title' => t('Video convert on node submit'),
      '#default_value' => $config->get('video_convert_on_save') ?: FALSE,
      '#description' => t('Convert videos on node submit will enable by default for all users.'),
      '#access' => $hastranscoder,
    );
    $form['video_use_default_thumb'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override video thumbnails with default thumbnail'),
      '#default_value' => $config->get('video_use_default_thumb') ?: FALSE,
      '#description' => t('Override auto thumbnails with default thumbnail.'),
      '#access' => $hastranscoder,
    );
    $form['video_publish_on_complete'] = array(
      '#type' => 'checkbox',
      '#title' => t('Publish when conversion complete'),
      '#default_value' => $config->get('video_publish_on_complete') ?: FALSE,
      '#description' => t('This feature is now fully controlled by !rules module. Download the module and configure rules to take effect when video conversions have completed or failed.', array('!rules' => \Drupal::l(t('Rules'), Url::fromUri('http://drupal.org/project/rules/')))),
      '#disabled' => TRUE,
      '#access' => $hastranscoder,
    );
    $form['video_dimensions'] = array(
      '#type' => 'textarea',
      '#title' => t('Available dimensions for converting and displaying videos'),
      '#description' => t('Enter one dimension per line. Each resolution must be in the form of <code>width</code>x<code>height</code>. Example dimensions: <code>1280x720</code>.'),
      '#default_value' => $config->get('video_dimensions') ?: Videoutility::getDefaultDimensions(),
      '#required' => TRUE,
      '#wysiwyg' => FALSE,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::config('video_ui.settings');
    $userInputValues = $form_state->getUserInput();

    $config->set('video_autoplay', $userInputValues['video_autoplay']);
    $config->set('video_autobuffering', $userInputValues['video_autobuffering']);
    $config->set('video_bypass_conversion', $userInputValues['video_bypass_conversion']);
    $config->set('video_convert_on_save', $userInputValues['video_convert_on_save']);
    $config->set('video_use_default_thumb', $userInputValues['video_use_default_thumb']);
    $config->set('video_publish_on_complete', $userInputValues['video_publish_on_complete']);
    $config->set('video_dimensions', $userInputValues['video_dimensions']);
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
