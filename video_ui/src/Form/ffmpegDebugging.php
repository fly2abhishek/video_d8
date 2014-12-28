<?php

/**
 * @file
 * Contains \Drupal\system\Form\RssFeedsForm.
 */

namespace Drupal\video_ui\Form;

use Drupal\video\PHPVideoToolkit;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ffmpegDebugging extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_admin_ffmpeg_info';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $config = \Drupal::config('video_ui.settings');

    $ffmpegpath = $config->get('video_ffmpeg_path') ?: '/usr/bin/ffmpeg';
	  if (isset($_GET['ffmpegpath'])) {
	    $ffmpegpath = $_GET['ffmpegpath'];
	  }

	  $form = array();
	  $form['ffmpegpath'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Path to FFmpeg or avconv executable'),
	    '#default_value' => $ffmpegpath,
	    '#required' => TRUE,
	  );

	  if ($ffmpegpath != '') {
	    $transcoder = new PHPVideoToolkit($ffmpegpath, realpath(file_directory_temp()) . '/');
	    $info = $transcoder->getFFmpegInfo(FALSE);
	    $infotxt = var_export($info, TRUE);

	    $form['info'] = array(
	      '#type' => 'textarea',
	      '#title' => t('Debug information'),
	      '#default_value' => $infotxt,
	      '#weight' => 1000,
	      '#rows' => min(20, substr_count($infotxt, "\n")),
	    );
	  }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::config('video_ui.settings');
    $userInputValues = $form_state->getUserInput();
    
    $config->set('video_ffmpeg_path', $userInputValues['ffmpegpath']);
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
