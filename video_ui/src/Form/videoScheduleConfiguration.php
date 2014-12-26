<?php

/**
 * @file
 * Contains \Drupal\system\Form\RssFeedsForm.
 */

namespace Drupal\video_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class videoScheduleConfiguration extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_scheduling_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $config = \Drupal::config('video_ui.settings');

    $form = array();
	  $form['video_cron'] = array(
	    '#type' => 'checkbox',
	    '#title' => t('Use Drupal\'s built-in cron'),
	    '#default_value' => $config->get('video_cron') ?: TRUE,
	    '#description' =>
	        t('Check this box to use Drupal\'s built in cron execution to transcode videos. Be aware that video transcoding is very resource-intensive. If you use poor man\'s cron, this option is strongly discouraged.') . '<br/>' .
	        t('Alternatives to using cron are the video_scheduler.php script that is located in the Video module directory or the video-scheduler <a href="@drush-url">Drush</a> command (recommended).', array('@drush-url' => Url::fromUri('http://drupal.org/project/drush'))) . '<br/>' .
	        t('If you use none of these options, you can only transcode videos by using the %convertonsave option when uploading a video.', array('%convertonsave' => t('Convert video on save'))),
	  );
	  $form['video_transcode_timeout'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Video transcode timeout'),
	    '#default_value' => $config->get('video_transcode_timeout') ?: 10,
	    '#description' => t('The maximum time allowed for a video transcode to complete. Use a larger value when you regularly transcode large or long videos. When a video has been transcoding for more than this amount of time, it will be marked as failed. Leave this field empty to disable this behavior.'),
	    '#field_suffix' => t('minutes'),
	    '#maxlength' => 5,
	    '#size' => 10,
	    '#element_validate' => array('element_validate_integer_positive'),
	  );
	  $form['video_queue_timeout'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Video queue timeout'),
	    '#default_value' => $config->get('video_queue_timeout') ?: 60,
	    '#description' => t('The maximum time allowed for a video to be in the queue. When processing of the queue is aborted, the video will be requeued after this amount of time. Leave this field empty to disable this behavior.'),
	    '#field_suffix' => t('minutes'),
	    '#maxlength' => 5,
	    '#size' => 10,
	    '#element_validate' => array('element_validate_integer_positive'),
	  );
	  $form['video_ffmpeg_instances'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Total videos to convert during each scheduled run'),
	    '#default_value' => $config->get('video_ffmpeg_instances') ?: 5,
	    '#description' => t('How many videos do you want to process on each cron/video_scheduler.php/drush run?'),
	    '#maxlength' => 5,
	    '#size' => 10,
	    '#element_validate' => array('element_validate_integer_positive'),
	  );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::config('video_ui.settings');
    $userInputValues = $form_state->getUserInput();

    $config->set('video_cron', $userInputValues['video_cron']);
    $config->set('video_transcode_timeout', $userInputValues['video_transcode_timeout']);
    $config->set('video_queue_timeout', $userInputValues['video_queue_timeout']);
    $config->set('video_ffmpeg_instances', $userInputValues['video_ffmpeg_instances']);
    
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
