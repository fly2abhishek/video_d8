<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\field\formatter\VideoFormatter.
 */
namespace Drupal\video\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'video' formatter.
 *
 * @FieldFormatter(
 *   id = "video",
 *   label = @Translation("Video"),
 *   field_types = {
 *     "video"
 *   }
 * )
 */
class VideoFormatter extends VideoFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    foreach ($items as $delta => $item) {
      if ($item->entity) {
        // if (isset($link_file)) {
        $video_uri = $item->entity->getFileUri();
        $uri = array(
          'path' => file_create_url($video_uri),
          'options' => array(),
        );
        //  }
        $elements[$delta] = array(
          '#theme' => 'video_formatter',
          // '#item' => $item,
          //  '#item_attributes' => $item_attributes,
          //  '#image_style' => $image_style_setting,
          '#path' => isset($uri) ? $uri : '',
        );
      }
    }
    return $elements;
  }

}
