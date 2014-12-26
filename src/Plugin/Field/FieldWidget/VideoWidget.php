<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\Field\FieldWidget\VideoWidget.
 */
namespace Drupal\video\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Plugin implementation of the 'video_video' widget.
 *
 * @FieldWidget(
 *   id = "video_video",
 *   label = @Translation("Video"),
 *   field_types = {
 *     "video"
 *   }
 * )
 */
class VideoWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    //  $transcoder = new Transcoder();  -- TO DO...
    // $hastranscoder = $transcoder->hasTranscoder();
    // Add warnings for streaming to iPad when using the private file system
    // See http://www.metaltoad.com/blog/iphone-video-streaming-drupals-file-system
    $ioswarning = '<br/>' . t('Streaming to Apple iOS devices (iPad/iPhone/iPod) is not supported when using the private file system unless a module to support Range requests is installed. Modules that are known to work are <a href="@xsendfile-module">X-Sendfile</a> or <a href="@resumable-download-module">Resumable Download</a>.', array(
        '@xsendfile-module' => url('http://drupal.org/project/xsendfile'),
        '@resumable-download-module' => url('http://drupal.org/project/resumable_download')
      ));

    $element['uri_scheme']['#description'] .= $ioswarning;
    $element['uri_scheme_converted']['#description'] .= $ioswarning;
    $element['autoconversion'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable auto video conversion'),
      '#description' => t('Convert videos automatically using FFmpeg or Zencoder. You can define presets at !preset to automatically convert videos to web compatible formats eg. FLV, MP4. Make sure to configure your !settings to make this work properly.', array(
        '!settings' => l(t('transcoder settings'), 'admin/config/media/video/transcoders'),
        '!preset' => l(t('preset settings'), 'admin/config/media/video/presets'),
      )),
      '#default_value' => $this->getSetting('autoconversion'),
    );

    $element['thumbnail_format'] = array(
      '#title' => t('Thumbnail format'),
      '#type' => 'radios',
      '#options' => array('jpg' => 'JPEG', 'png' => 'PNG'),
      '#default_value' =>  ($this->getSetting('thumbnail_format')) ? $this->getSetting('thumbnail_format') : 'jpg',
    );

    $thumb_options = array(
      'auto' => 'Automatically extract thumbnails from video (with fallback to manual upload)',
      'manual_upload' => 'Manually upload a thumbnail',
      'no' => 'Don\'t create thumbnail',
    );

      unset($thumb_options['auto']);
      if ( !$this->getSetting('autothumbnail') || $this->getSetting('autothumbnail') == 'auto') {
        $this->setSetting('autothumbnail','no');
      }

    $element['autothumbnail'] = array(
      '#type' => 'radios',
      '#title' => t('Video thumbnails'),
      '#options' => $thumb_options,
      '#description' => t('If you choose <i>Automatically extract thumbnails from video</i> then please make sure to configure your !settings to make this work properly.', array('!settings' => l(t('transcoder settings'), 'admin/config/media/video/transcoders'))),
      '#default_value' => !$this->getSetting('autothumbnail') ? $this->getSetting('autothumbnail') : 'auto',
    );
    $element['default_video_thumbnail'] = array(
      '#title' => t('Default video thumbnail'),
      '#type' => 'managed_file',
      '#element_validate' => array('video_field_default_thumbnail_validate'),
      '#description' => t('You can use a default thumbnail for all videos or videos from which a thumbnail can\'t be extracted. Settings to use default video thumbnails will be available on node edit. You can change the permissions for other users too.'),
      '#default_value' => !$this->getSetting('default_video_thumbnail')['fid'] ? $this->getSetting('default_video_thumbnail')['fid'] : '',
      '#upload_location' => 'public://videos/thumbnails/default',
    );
    $element['preview_video_thumb_style'] = array(
      '#title' => t('Preview thumbnail style'),
      '#type' => 'select',
      '#options' => image_style_options(FALSE),
      '#empty_option' => '<' . t('no preview') . '>',
      '#default_value' => $this->getSetting('preview_video_thumb_style'),
      '#description' => t('This image style will be used to show extracted video thumbnails on video node edit. Extracted thumbnail preview will also use this style.'),
    );
    $selectedpresets = array_filter(variable_get('video_preset', array()));
    $presets = Preset::getAllPresets();
    $presetnames = array();
    foreach ($presets as $preset) {
      $presetnames[$preset['name']] = $preset['name'];
      if (in_array($preset['name'], $selectedpresets)) {
        $presetnames[$preset['name']] .= ' (' . t('default') . ')';
      }
    }
    $element['presets'] = array(
      '#title' => t('Presets'),
      '#type' => 'checkboxes',
      '#options' => $presetnames,
      '#default_value' => $this->getSetting('presets'),
      '#description' => t('If any presets are selected, these presets will be used for this field instead of the default presets.'),
      '#access' => $hastranscoder,
    );
    /*    $element['preview_image_style'] = array(
          '#title' => t('Preview image style'),
          '#type' => 'select',
          '#options' => image_style_options(FALSE),
          '#empty_option' => '<' . t('no preview') . '>',
          '#default_value' => $this->getSetting('preview_image_style'),
          '#description' => t('The preview image will be shown while editing the content.'),
          '#weight' => 15,
        ); */
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    //$field_settings = $this->getFieldSettings();
    // If not using custom extension validation, ensure this is a video.
    $supported_extensions = array('mp4', 'webm', 'ogg');
    $extensions = isset($element['#upload_validators']['file_validate_extensions'][0]) ? $element['#upload_validators']['file_validate_extensions'][0] : implode(' ', $supported_extensions);
    $extensions = array_intersect(explode(' ', $extensions), $supported_extensions);
    $element['#upload_validators']['file_validate_extensions'][0] = implode(' ', $extensions);
    return $element;
  }

  /**
   * Form API callback: Processes a video_video field element.
   *
   * Expands the video_video type to include the alt and title fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];
    $element['#theme'] = 'video_widget';
    $element['#attached']['css'][] = drupal_get_path('module', 'video') . '/css/video.theme.css';
    return parent::process($element, $form_state, $form);
  }

  /**
   * Validate callback for alt and title field, if the user wants them required.
   *
   * This is separated in a validate function instead of a #required flag to
   * avoid being validated on the process callback.
   */
  public static function validateRequiredFields($element, FormStateInterface $form_state) {
    // Only do validation if the function is triggered from other places than
    // the image process form.
    if (!in_array('file_managed_file_submit', $form_state->getTriggeringElement()['#submit'])) {
      // If the image is not there, we do not check for empty values.
      $parents = $element['#parents'];
      $field = array_pop($parents);
      $video_field = NestedArray::getValue($form_state->getUserInput(), $parents);
      // We check for the array key, so that it can be NULL (like if the user
      // submits the form without using the "upload" button).
      if (!array_key_exists($field, $video_field)) {
        return;
      } // Check if field is left empty.
      elseif (empty($video_field[$field])) {
        $form_state->setError($element, t('The field !title is required', array('!title' => $element['#title'])));
        return;
      }
    }
  }

}
