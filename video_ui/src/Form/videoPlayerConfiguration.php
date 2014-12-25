<?php

/**
 * @file
 * Contains \Drupal\system\Form\RssFeedsForm.
 */

namespace Drupal\video_ui\Form;

use Drupal\video\Videoutility;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

class videoPlayerConfiguration extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_players_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['extensions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Video extensions'),
      '#description' => t('Here you can map specific players to each video extension type.') . ' ' . t('This player will be selected when you choose the !formatter formatter for a Video field.', array('!formatter' => t('Video player'))),
    );
    // lets get all our supported extensions and players.
    $extensions = Videoutility::getVideoExtensions();
    $players = Videoutility::getVideoPlayers();
    $flv_players = video_video_flv_players();
    $html5_players = video_video_html5_players();

    foreach ($extensions as $ext => $player) {
      $form['extensions']['video_extension_' . $ext] = array(
        '#type' => 'select',
        '#title' => t('Extension:') . '  ' . $ext,
        '#default_value' => \Drupal::config('video.settings')->get('video_extension_' . $ext) ?: $player,
        '#options' => $players,
        '#required' => TRUE,
      );

      // For Flash
      if (!empty($flv_players)) {
        $value = \Drupal::config('video.settings')->get('video_extension_' . $ext . '_flash_player') ?: '';
        if (empty($value) || !isset($flv_players[$value])) {
          $value = key($flv_players);
        }
        $form['extensions']['video_extension_' . $ext . '_flash_player'] = array(
          '#type' => 'radios',
          '#title' => t('Flash player for @extension', array('@extension' => $ext)),
          '#options' => $flv_players,
          '#default_value' => $value,
          '#required' => TRUE,
          '#states' => array(
            'visible' => array(
              ':input[id=edit-video-extension-' . $ext . ']' => array('value' => 'video_play_flv'),
            ),
          ),
        );
      }
      else {
        $form['extensions']['video_extension_' . $ext . '_flash_player']['type'] = array(
          '#type' => 'markup',
          '#markup' => t('No Flash players detected.<br />You need to install !flowplayer or !jwplayer.', array('!flowplayer' => \Drupal::l(t('Flowplayer API'), Url::fromUri('http://www.drupal.org/project/flowplayer')), '!jwplayer' => \Drupal::l(t('JW Player'), Url::fromUri('http://www.drupal.org/project/jw_player')))),
          '#states' => array(
            'visible' => array(
              ':input[id=edit-video-extension-' . $ext . ']' => array('value' => 'video_play_flv'),
            ),
          ),
        );
      }

      // for HTML5
      if (!empty($html5_players)) {
        $value = \Drupal::config('video.settings')->get('video_extension_' . $ext . '_html5_player') ?: '';
        if (empty($value) || !isset($html5_players[$value])) {
          $value = key($html5_players);
        }
        $form['extensions']['video_extension_' . $ext . '_html5_player'] = array(
          '#type' => 'radios',
          '#title' => t('HTML5 player for @extension', array('@extension' => $ext)),
          '#options' => $html5_players,
          '#markup' => t('Additional HTML5 players module.<br />You can install !videojs.', array('!videojs' => \Drupal::l(t('Video.js'), Url::fromUri('http://drupal.org/project/videojs')))),
          '#default_value' => $value,
          '#required' => TRUE,
          '#states' => array(
            'visible' => array(
              ':input[id=edit-video-extension-' . $ext . ']' => array('value' => 'video_play_html5'),
            ),
          ),
        );
      }
    }

    // Miscellaneous player settings
    $form['playersettings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Player settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    if (\Drupal::moduleHandler()->moduleExists('jw_player')) {
      $presets = array();
      foreach (jw_player_preset_load() as $preset => $item) {
        $presets[$preset] = $item['preset_name'];
      }

      $form['playersettings']['video_jwplayer_preset'] = array(
        '#type' => 'select',
        '#title' => t('JW Player preset'),
        '#options' => $presets,
        '#default_value' => \Drupal::config('video.settings')->get('video_jwplayer_preset') ?: NULL,
        '#empty_value' => '',
      );
    }

    if (count(element_children($form['playersettings'])) == 0) {
      unset($form['playersettings']);
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Remove flv player or html5 player when that player hasn't been selected
    $extensions = Videoutility::getVideoExtensions();
    $userInputValues = $form_state->getUserInput();

    dsm($userInputValues);
    // dsm($extensions);
    foreach (array_keys($extensions) as $ext) {
      dsm($ext);
      $selected = $userInputValues['video_extension_' . $ext];
      dsm($selected);
      // if ($selected != 'video_play_flv') {
      //   unset($userInputValues['video_extension_' . $ext . '_flash_player']);
      // }
      // if ($selected != 'video_play_html5') {
      //   unset($userInputValues['video_extension_' . $ext . '_html5_player']);
      // }
    }
  }
}

/**
 * Return our possible flash players.
 *
 * When extending this method, also change the error message in
 * video_players_admin_settings().
 */
function video_video_flv_players() {
  $options = array();
  if (\Drupal::moduleHandler()->moduleExists('flowplayer')) {
    $options['flowplayer'] = t('Flowplayer');
  }
  if (\Drupal::moduleHandler()->moduleExists('jw_player')) {
    $options['jwplayer'] = t('JW Player');
  }
  return $options;
}

/**
 * Return our possible HTML5 players.
 */
function video_video_html5_players() {
  $options = array(
    'video' => t('Plain HTML5 video player with Flash fallback'),
    'audio' => t('Plain HTML5 audio player with Flash fallback'),
  );
  if (\Drupal::moduleHandler()->moduleExists('videojs')) {
    $options['videojs'] = t('Video.js');
  }
  if (\Drupal::moduleHandler()->moduleExists('mediaelement')) {
    $options['mediaelement'] = t('MediaElement');
  }
  return $options;
}