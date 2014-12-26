<?php

/**
 * @file
 * Contains \Drupal\system\Form\RssFeedsForm.
 */

namespace Drupal\video_ui\Form;

use Drupal\video\Transcoder;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class videoTranscoderConfiguration extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_transcoder_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // $transcoder = new Transcoder();
    $options = Transcoder::getAllTranscoders();

    $form = array();
    $form['video_convertor'] = array(
      '#type' => 'radios',
      '#title' => t('Video transcoder'),
      '#default_value' => \Drupal::config('video_ui.settings')->get('video_convertor') ?: 'TranscoderAbstractionFactoryFfmpeg',
      '#options' => $options['radios'],
      '#description' => '<p>' . t('Select a video transcoder will help you convert videos and generate thumbnails.') . '</p>' /*. theme('item_list', array('items' => $options['help']))*/,
    );
    // $form = $form + $options['admin_settings'];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  
    $userInputValues = $form_state->getUserInput();
    $config = \Drupal::config('video_ui.settings');

    dsm($userInputValues['video_convertor']);
    $config->set('video_convertor', $userInputValues['video_convertor']);
    $config->save();
    parent::submitForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    $userInputValues = $form_state->getUserInput();
    $transcodername = $userInputValues['video_convertor'];
    if ($transcodername == '') {
      return;
    }

    Transcoder::createTranscoder($transcodername)->adminSettingsValidate($form, $form_state);
    $video_convertor = \Drupal::config('video_ui.settings')->get('video_convertor');
    if (!form_get_errors() && $transcodername != $video_convertor) {
      drupal_set_message(t('Because the selected transcoder was changed, you need to update the codec settings for your presets.'), 'warning');
      $form_state->setRedirect('video_ui.preset_setting');
    }
  }
}
