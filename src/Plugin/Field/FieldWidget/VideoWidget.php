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
