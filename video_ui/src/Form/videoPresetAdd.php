<?php

/**
 * @file
 * Contains \Drupal\system\Form\RssFeedsForm.
 */

namespace Drupal\video_ui\Form;

use Drupal\video\Transcoder;
use Drupal\video\Videoutility;
use Drupal\Core\Routing\LinkGeneratorInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\video\TranscoderAbstractionFactoryFfmpeg;
use Drupal\Core\Form\FormStateInterface;

class videoPresetAdd extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_preset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $transcoder = new Transcoder();
	  $transcoder = $transcoder::getTranscoder();
	  $codecs = $transcoder::getCodecs();
	  error_log($codecs);
	  $formats = $transcoder::getAvailableFormats('muxing');
	  $pixelformats = $transcoder::getPixelFormats();
	  $settings = $preset['settings'];

	  if (empty($formats)) {
	    drupal_set_message(t('No video output extensions are available. Please reconfigure your <a href="@transcoder-url">video transcoder</a>.', array('@transcoder-url' => url('admin/config/media/video/transcoders'))), 'warning');
	  }
	  elseif (isset($settings['video_extension']) && !isset($formats[$settings['video_extension']])) {
	    drupal_set_message(t('The currently selected @setting_name %setting_value is no longer available, possibly because you changed the transcoder type or the transcoder itself was updated. Please select a new @setting_name.', array('@setting_name' => t('Video output extension'), '%setting_value' => $settings['video_extension'])), 'warning');
	    unset($settings['video_extension']);
	  }
	  if (empty($codecs['encode']['video'])) {
	    drupal_set_message(t('No video codecs are available. Please reconfigure your <a href="@transcoder-url">video transcoder</a>.', array('@transcoder-url' => url('admin/config/media/video/transcoders'))), 'warning');
	  }
	  elseif (isset($settings['video_codec']) && !isset($codecs['encode']['video'][$settings['video_codec']])) {
	    drupal_set_message(t('The currently selected @setting_name %setting_value is no longer available, possibly because you changed the transcoder type or the transcoder itself was updated. Please select a new @setting_name.', array('@setting_name' => t('Video codec'), '%setting_value' => $settings['video_codec'])), 'warning');
	    unset($settings['video_codec']);
	  }
	  if (empty($codecs['encode']['audio'])) {
	    drupal_set_message(t('No audio codecs are available. Please reconfigure your <a href="@transcoder-url">video transcoder</a>.', array('@transcoder-url' => url('admin/config/media/video/transcoders'))), 'warning');
	  }
	  elseif (isset($settings['audio_codec']) && !isset($codecs['encode']['audio'][$settings['audio_codec']])) {
	    drupal_set_message(t('The currently selected @setting_name %setting_value is no longer available, possibly because you changed the transcoder type or the transcoder itself was updated. Please select a new @setting_name.', array('@setting_name' => t('Audio codec'), '%setting_value' => $settings['audio_codec'])), 'warning');
	    unset($settings['audio_codec']);
	  }

	  // NULL triggers the - Select - option.
	  $defaultvideocodec = isset($codecs['encode']['video']['']) ? '' : NULL;
	  $defaultaudiocodec = isset($codecs['encode']['audio']['']) ? '' : NULL;

	  $form = array();
	  $form_state['preset'] = $preset;

	  // basic preset details
	  $form['preset'] = array(
	    '#type' => 'fieldset',
	    '#title' => t('Preset information'),
	    '#weight' => -10,
	  );

	  if (!$preset || $preset['module'] == NULL) {
	    $form['preset']['name'] = array(
	      '#type' => 'textfield',
	      '#title' => t('Preset name'),
	      '#maxlength' => VIDEO_PRESET_MAX_LENGTH,
	      '#description' => t('The only permitted punctuation is space, period, hyphen, apostrophe, and underscore.'),
	      '#required' => TRUE,
	      '#weight' => -10,
	      '#element_validate' => array('_video_preset_name_validate'),
	      '#default_value' => !empty($preset['name']) ? $preset['name'] : ''
	    );
	  }
	  else {
	    $form['preset']['name'] = array(
	      '#type' => 'item',
	      '#title' => t('Preset name'),
	      '#description' => t('This preset is provided by the %module_name module. You can override the settings by submitting this form.', array('%module_name' => $preset['module'])),
	      '#weight' => -10,
	      '#markup' => check_plain($preset['name']),
	    );
	  }

	  $form['preset']['description'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Description'),
	    '#description' => t('Add a brief description to your preset name.'),
	    '#weight' => -9,
	    '#default_value' => !empty($preset['description']) ? $preset['description'] : '',
	  );

	  // video settings
	  $form['settings']['video'] = array(
	    '#type' => 'fieldset',
	    '#title' => t('Video settings'),
	    '#collapsible' => TRUE,
	    '#collapsed' => FALSE,
	  );

	  $form['settings']['video']['video_extension'] = array(
	    '#type' => 'select',
	    '#title' => t('Video output extension'),
	    '#description' => t('Extension of the output video.'),
	    '#options' => $formats,
	    '#default_value' => !empty($settings['video_extension']) ? $settings['video_extension'] : NULL,
	    '#required' => TRUE,
	  );

	  $form['settings']['video']['video_codec'] = array(
	    '#type' => 'select',
	    '#title' => t('Video codec'),
	    '#description' => t('The video codec used in the video file can affect the ability to play the video on certain devices.'),
	    '#options' => $codecs['encode']['video'],
	    '#required' => TRUE,
	    '#default_value' => !empty($settings['video_codec']) ? $settings['video_codec'] : $defaultvideocodec,
	  );

	  $form['settings']['video']['video_preset'] = array(
	    '#type' => 'select',
	    '#title' => t('FFmpeg video preset'),
	    '#description' => t('A preset file contains a sequence of option=value pairs, one for each line, specifying a sequence of options which would be awkward to specify on the command line. Lines starting with the hash (\'#\') character are ignored and are used to provide comments. Check the &quot;presets&quot; directory in the FFmpeg source tree for examples. See the !doc. Newer FFmpeg installations do not supply libx264 presets anymore, so &quot;!optionnamenone&quot; should be selected. If FFmpeg fails with an error related to presets, please also select &quot;!optionnamenone&quot;. In other cases, an error message may suggest that you should select one of the available options. This setting requires some experimentation.', array('!doc' => LinkGeneratorInterface::generate(t('FFmpeg documentation'), url('http://ffmpeg.org/ffmpeg.html#Preset-files')), '!optionnamenone' => t('None'))),
	    '#options' => array(
	      '' => t('None'),
	      'libx264-baseline' => 'libx264-baseline',
	      'libx264-default' => 'libx264-default',
	      'libx264-fast' => 'libx264-fast',
	      'libx264-faster' => 'libx264-faster',
	      'libx264-hq' => 'libx264-hq',
	      'libx264-ipod320' => 'libx264-ipod320',
	      'libx264-ipod640' => 'libx264-ipod640',
	      'libx264-main' => 'libx264-main',
	      'libx264-max' => 'libx264-max',
	      'libx264-medium' => 'libx264-medium',
	      'libx264-normal' => 'libx264-normal',
	      'libx264-slow' => 'libx264-slow',
	      'libx264-slower' => 'libx264-slower',
	      'libx264-superfast' => 'libx264-superfast',
	      'libx264-ultrafast' => 'libx264-ultrafast',
	      'libx264-veryfast' => 'libx264-veryfast',
	      'libvpx-1080p' => 'libvpx-1080p',
	      'libvpx-1080p50_60' => 'libvpx-1080p50_60',
	      'libvpx-360p' => 'libvpx-360p',
	      'libvpx-720p' => 'libvpx-720p',
	      'libvpx-720p50_60' => 'libvpx-720p50_60',
	      'libx264-lossless_fast' => 'libx264-lossless_fast',
	      'libx264-lossless_max' => 'libx264-lossless_max',
	      'libx264-lossless_medium' => 'libx264-lossless_medium',
	      'libx264-lossless_slow' => 'libx264-lossless_slow',
	      'libx264-lossless_slower' => 'libx264-lossless_slower',
	      'libx264-lossless_ultrafast' => 'libx264-lossless_ultrafast',
	    ),
	    '#default_value' => (!empty($settings['video_preset'])) ? $settings['video_preset'] : '',
	  );

	  $form['settings']['video']['video_quality'] = array(
	    '#type' => 'select',
	    '#title' => t('Video quality'),
	    '#description' => t('A target video quality. Affects bitrate and file size.'),
	    '#options' => array(
	      'none' => t('None'),
	      1 => '1 - Poor quality (smaller file)',
	      2 => '2',
	      3 => '3' . ' (' . t('default') . ')',
	      4 => '4',
	      5 => '5 - High quality (larger file)'
	    ),
	    '#default_value' => (!empty($settings['video_quality'])) ? $settings['video_quality'] : 3,
	  );
	  $form['settings']['video']['video_speed'] = array(
	    '#type' => 'select',
	    '#title' => t('Video speed'),
	    '#description' => t('Speed of encoding. Affects compression.'),
	    '#options' => array(
	      'none' => t('None'),
	      1 => '1 - Slow (better compression)',
	      2 => '2',
	      3 => '3' . ' (' . t('default') . ')',
	      4 => '4',
	      5 => '5 - Fast (worse compression)'
	    ),
	    '#default_value' => (!empty($settings['video_speed'])) ? $settings['video_speed'] : 3,
	  );
	  $form['settings']['video']['wxh'] = array(
	    '#type' => 'select',
	    '#title' => t('Dimensions'),
	    '#description' => t('Select the desired widthxheight of the video player. You can add your own dimensions from !settings.', array('!settings' => LinkGeneratorInterface::generate(t('Video module settings'), 'admin/config/media/video'))),
	    '#default_value' => !empty($settings['wxh']) ? $settings['wxh'] : '640x360',
	    '#options' => Videoutility::getDimensions(),
	  );
	  $form['settings']['video']['video_aspectmode'] = array(
	    '#type' => 'select',
	    '#title' => t('Aspect mode'),
	    '#description' => t('What to do when aspect ratio of input file does not match the target width/height aspect ratio.'),
	    '#options' => array(
	      'preserve' => t('Preserve aspect ratio') . ' (' . t('default') . ')',
	      'crop' => t('Crop to fit output aspect ratio'),
	      'pad' => t('Pad (letterbox) to fit output aspect ratio'),
	      'stretch' => t('Stretch (distort) to output aspect ratio'),
	    ),
	    '#default_value' => (!empty($settings['video_aspectmode'])) ? $settings['video_aspectmode'] : 'preserve',
	  );
	  $form['settings']['video']['video_upscale'] = array(
	    '#type' => 'checkbox',
	    '#title' => t('Upscale'),
	    '#description' => t('If the input file is smaller than the target output, should the file be upscaled to the target size?'),
	    '#default_value' => !empty($settings['video_upscale']) ? $settings['video_upscale'] : ''
	  );

	  // audio settings
	  $form['settings']['audio'] = array(
	    '#type' => 'fieldset',
	    '#title' => t('Audio settings'),
	    '#collapsible' => TRUE,
	    '#collapsed' => FALSE,
	  );

	  $form['settings']['audio']['audio_codec'] = array(
	    '#type' => 'select',
	    '#title' => t('Audio codec'),
	    '#description' => t('The audio codec to be used.'),
	    '#options' => $codecs['encode']['audio'],
	    '#required' => TRUE,
	    '#default_value' => (!empty($settings['audio_codec'])) ? $settings['audio_codec'] : $defaultaudiocodec,
	  );
	  $form['settings']['audio']['audio_quality'] = array(
	    '#type' => 'select',
	    '#title' => t('Audio quality'),
	    '#description' => t('A target audio quality. Affects bitrate and file size.'),
	    '#options' => array(
	      '' => t('None'),
	      1 => '1 - Poor quality (smaller file)',
	      2 => '2',
	      3 => '3' . ' (' . t('default') . ')',
	      4 => '4',
	      5 => '5 - High quality (larger file)'
	    ),
	    '#default_value' => (!empty($settings['audio_quality'])) ? $settings['audio_quality'] : 3,
	  );

	  // advanced video settings
	  $form['settings']['adv_video'] = array(
	    '#type' => 'fieldset',
	    '#title' => t('Advanced video settings'),
	    '#collapsible' => TRUE,
	    '#collapsed' => TRUE
	  );
	  $form['settings']['adv_video']['deinterlace'] = array(
	    '#type' => 'select',
	    '#title' => t('Deinterlace'),
	    '#description' => t('Note that detect mode will auto-detect and deinterlace interlaced content.'),
	    '#options' => array(
	      'detect' => 'Detect' . ' (' . t('default') . ')',
	      'on' => 'On (reduces quality of non-interlaced content)',
	      'off' => 'Off'
	    ),
	    '#default_value' => (!empty($settings['deinterlace'])) ? $settings['deinterlace'] : 'detect'
	  );
	  $form['settings']['adv_video']['max_frame_rate'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Maximum frame rate'),
	    '#description' => t('A maximum frame rate cap (in frames per second).'),
	    '#default_value' => !empty($settings['max_frame_rate']) ? $settings['max_frame_rate'] : ''
	  );
	  $form['settings']['adv_video']['frame_rate'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Frame rate'),
	    '#description' => t('Force a specific output frame rate (in frames per second). For best quality, do not use this setting.'),
	    '#default_value' => !empty($settings['frame_rate']) ? $settings['frame_rate'] : ''
	  );
	  $form['settings']['adv_video']['keyframe_interval'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Key frame interval'),
	    '#description' => t('By default, a keyframe will be created at most every 250 frames. Specifying a different keyframe interval will allow you to create more or fewer keyframes in your video. A greater number of keyframes will increase the size of your output file, but will allow for more precise scrubbing in most players. Keyframe interval should be specified as a positive integer. For example, a value of 100 will create a keyframe every 100 frames.'),
	    '#default_value' => !empty($settings['keyframe_interval']) ? $settings['keyframe_interval'] : ''
	  );
	  $form['settings']['adv_video']['video_bitrate'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Video bitrate'),
	    '#description' => t('A target bitrate in kbps. Not necessary if you select a Video Quality setting, unless you want to target a specific bitrate.'),
	    '#default_value' => !empty($settings['video_bitrate']) ? $settings['video_bitrate'] : '',
	  );
	  $form['settings']['adv_video']['bitrate_cap'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Bitrate cap'),
	    '#description' => t('A bitrate cap in kbps, used for streaming servers.'),
	    '#default_value' => !empty($settings['bitrate_cap']) ? $settings['bitrate_cap'] : ''
	  );
	  $form['settings']['adv_video']['buffer_size'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Buffer size'),
	    '#description' => t('The buffer size for the bitrate cap in kbps.'),
	    '#default_value' => !empty($settings['buffer_size']) ? $settings['buffer_size'] : ''
	  );
	  $form['settings']['adv_video']['one_pass'] = array(
	    '#type' => 'checkbox',
	    '#title' => t('Force one-pass encoding'),
	    '#default_value' => !empty($settings['one_pass']) ? $settings['one_pass'] : ''
	  );
	  $form['settings']['adv_video']['skip_video'] = array(
	    '#type' => 'checkbox',
	    '#title' => t('Skip video'),
	    '#description' => t('The video track will be omitted from the output. You can still specify a video format, however, no video track will be present in the resulting file.'),
	    '#default_value' => !empty($settings['skip_video']) ? $settings['skip_video'] : ''
	  );

	  // Not all transcoders support setting the pixel format
	  if (!empty($pixelformats)) {
	    $pixelformatoptions = array('' => t('Same as input video'));
	    $pixelformatoptions = array_merge($pixelformatoptions, array_combine($pixelformats, $pixelformats));
	    $form['settings']['adv_video']['pixel_format'] = array(
	      '#type' => 'select',
	      '#title' => t('Pixel format'),
	      '#description' => t('The pixel format of the output file. Yuv420p is a safe choice, yuvj420p is not supported by at least Google Chrome. If you select <em>!optionname</em> and the input video is yuvj420p, the output video will not be playable on Chrome.', array('!optionname' => t('Same as input video'))),
	      '#options' => $pixelformatoptions,
	      '#default_value' => !empty($settings['pixel_format']) ? $settings['pixel_format'] : '',
	    );
	  }
	  // Copy the value if present so the value isn't lost when people change transcoders
	  elseif (isset($settings['pixel_format'])) {
	    $form['settings']['adv_video']['pixel_format'] = array(
	      '#type' => 'value',
	      '#value' => $settings['pixel_format'],
	    );
	  }

	  $profiles = array('' => t('None'), 'baseline' => 'Baseline', 'main' => 'Main', 'high' => 'High');
	  $form['settings']['adv_video']['h264_profile'] = array(
	    '#type' => 'select',
	    '#title' => t('H.264 profile'),
	    '#description' => t('Use Baseline for maximum compatibility with players. Select !optionnamenone when this is not an H.264 preset or when setting the profile causes errors.', array('!optionnamenone' => t('None'))),
	    '#options' => $profiles,
	    '#default_value' => !empty($settings['h264_profile']) ? $settings['h264_profile'] : '',
	  );

	  // advanced audio settings
	  $form['settings']['adv_audio'] = array(
	    '#type' => 'fieldset',
	    '#title' => t('Advanced audio settings'),
	    '#collapsible' => TRUE,
	    '#collapsed' => TRUE
	  );
	  $form['settings']['adv_audio']['audio_bitrate'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Audio bitrate'),
	    '#description' => t('The overall audio bitrate specified as kilobits per second (kbps, e.g. 96 or 160). This value can\'t exceed 160 kbps per channel. 96-160 is usually a good range for stereo output.'),
	    '#default_value' => !empty($settings['audio_bitrate']) ? $settings['audio_bitrate'] : ''
	  );
	  $form['settings']['adv_audio']['audio_channels'] = array(
	    '#type' => 'select',
	    '#title' => t('Audio channels'),
	    '#description' => t('By default we will choose the lesser of the number of audio channels in the input file or 2 (stereo).'),
	    '#options' => array(
	      1 => '1 - Mono',
	      2 => '2 - Stereo' . ' (' . t('default') . ')'
	    ),
	    '#default_value' => (!empty($settings['audio_channels'])) ? $settings['audio_channels'] : 2
	  );
	  $form['settings']['adv_audio']['audio_sample_rate'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Audio sample rate'),
	    '#description' => t('The sample rate of the audio in hertz. Manually setting this may cause problems, depending on the selected bitrate and number of channels.'),
	    '#default_value' => !empty($settings['audio_sample_rate']) ? $settings['audio_sample_rate'] : ''
	  );
	  $form['settings']['adv_audio']['skip_audio'] = array(
	    '#type' => 'checkbox',
	    '#title' => t('Skip audio'),
	    '#description' => t('The audio track will be omitted from the output. You must specify a video format and no audio track will be present in the resulting file.'),
	    '#default_value' => !empty($settings['skip_audio']) ? $settings['skip_audio'] : ''
	  );

	  // Watermark
	  $form['settings']['watermark'] = array(
	    '#type' => 'fieldset',
	    '#title' => t('Video watermark'),
	    '#collapsible' => TRUE,
	    '#collapsed' => TRUE,
	    '#description' => t('At this moment this only works when using the Zencoder transcoder.'),
	  );
	  $form['settings']['watermark']['video_watermark_enabled'] = array(
	    '#type' => 'checkbox',
	    '#title' => t('Enable watermark video'),
	    '#default_value' => !empty($settings['video_watermark_enabled']) ? $settings['video_watermark_enabled'] : FALSE,
	  );

	  // When the scheme is private, Zencoder can't download the file.
	  // Force the scheme to public in this case.
	  $destination = (file_default_scheme() != 'private' ? file_default_scheme() : 'public') . '://videos/watermark';
	  $form['settings']['watermark']['video_watermark_fid'] = array(
	    '#type' => 'managed_file',
	    '#title' => t('Upload watermark image'),
	    '#description' => t('Watermark image should be a PNG or JPG image. The file will be uploaded to %destination.', array('%destination' => $destination)),
	    '#default_value' => !empty($settings['video_watermark_fid']) ? $settings['video_watermark_fid'] : 0,
	    '#upload_location' => $destination,
	    '#upload_validators' => array('file_validate_extensions' => array('jpg png'), 'file_validate_is_image' => array()),
	    '#states' => array(
	      'visible' => array(
	        ':input[id=edit-video-watermark-enabled]' => array('checked' => TRUE),
	      ),
	      'required' => array(
	        ':input[id=edit-video-watermark-enabled]' => array('checked' => TRUE),
	      ),
	    ),
	  );
	  $form['settings']['watermark']['video_watermark_x'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Left position'),
	    '#description' => t('Where to place the watermark relative to the left of the video. Use a negative number to place the watermark relative to the right of the video.'),
	    '#default_value' => isset($settings['video_watermark_x']) ? $settings['video_watermark_x'] : 5,
	    '#size' => 10,
	    '#maxlength' => 10,
	    '#field_suffix' => 'px',
	    '#states' => array(
	      'visible' => array(
	        ':input[id=edit-video-watermark-enabled]' => array('checked' => TRUE),
	      ),
	    ),
	  );
	  $form['settings']['watermark']['video_watermark_y'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Top position'),
	    '#description' => t('Where to place the watermark relative to the top of the video. Use a negative number to place the watermark relative to the bottom of the video.'),
	    '#default_value' => isset($settings['video_watermark_y']) ? $settings['video_watermark_y'] : 5,
	    '#size' => 10,
	    '#maxlength' => 10,
	    '#field_suffix' => 'px',
	    '#states' => array(
	      'visible' => array(
	        ':input[id=edit-video-watermark-enabled]' => array('checked' => TRUE),
	      ),
	    ),
	  );
	  $form['settings']['watermark']['video_watermark_width'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Width'),
	    '#description' => t('The width of the watermark. Use pixels or append a % sign to indicate a percentage relative to the width of the video. If left empty, the width will be the original width maximized by the video width.'),
	    '#default_value' => isset($settings['video_watermark_width']) ? $settings['video_watermark_width'] : '',
	    '#size' => 10,
	    '#maxlength' => 10,
	    '#states' => array(
	      'visible' => array(
	        ':input[id=edit-video-watermark-enabled]' => array('checked' => TRUE),
	      ),
	    ),
	  );
	  $form['settings']['watermark']['video_watermark_height'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Height'),
	    '#description' => t('The height of the watermark. Use pixels or append a % sign to indicate a percentage relative to the height of the video. If left empty, the width will be the original height maximized by the video height.'),
	    '#default_value' => isset($settings['video_watermark_width']) ? $settings['video_watermark_width'] : '',
	    '#size' => 10,
	    '#maxlength' => 10,
	    '#states' => array(
	      'visible' => array(
	        ':input[id=edit-video-watermark-enabled]' => array('checked' => TRUE),
	      ),
	    ),
	  );
	  $form['settings']['watermark']['video_watermark_origin'] = array(
	    '#title' => t('Origin'),
	    '#type' => 'select',
	    '#options' => array(
	      'content' => t('content: visible video area') . ' (' . t('default') . ')',
	      'frame' => t('frame: video area including padding'),
	    ),
	    '#default_value' => isset($settings['video_watermark_origin']) ? $settings['video_watermark_origin'] : 'content',
	    '#states' => array(
	      'visible' => array(
	        ':input[id=edit-video-watermark-enabled]' => array('checked' => TRUE),
	      ),
	    ),
	  );
	  
	  // video optimizations
	  $form['settings']['vid_optimization'] = array(
	    '#type' => 'fieldset',
	    '#title' => t('Video optimization'),
	    '#collapsible' => TRUE,
	    '#collapsed' => TRUE
	  );
	  $form['settings']['vid_optimization']['autolevels'] = array(
	    '#type' => 'checkbox',
	    '#title' => t('Autolevels'),
	    '#description' => t('Automatic brightness / contrast correction.'),
	    '#default_value' => !empty($settings['autolevels']) ? $settings['autolevels'] : ''
	  );
	  $form['settings']['vid_optimization']['deblock'] = array(
	    '#type' => 'checkbox',
	    '#title' => t('Deblock'),
	    '#description' => t('Apply deblocking filter. Useful for highly compressed or blocky input videos.'),
	    '#default_value' => !empty($settings['deblock']) ? $settings['deblock'] : ''
	  );
	  $form['settings']['vid_optimization']['denoise'] = array(
	    '#type' => 'select',
	    '#title' => t('Denoise'),
	    '#description' => t('Apply denoise filter. Generally results in slightly better compression and slightly slower encoding. Beware of any value higher than "Weak" (unless you\'re encoding animation).'),
	    '#options' => array(
	      '' => t('None'),
	      'weak' => 'Weak - usually OK for general use',
	      'medium' => 'Medium',
	      'strong' => 'Strong - beware',
	      'strongest' => 'Strongest - beware, except for Anime'
	    ),
	    '#default_value' => (!empty($settings['denoise'])) ? $settings['denoise'] : 2
	  );

	  // Create clip
	  $form['settings']['create_clip'] = array(
	    '#type' => 'fieldset',
	    '#title' => t('Create clip'),
	    '#collapsible' => TRUE,
	    '#collapsed' => empty($settings['clip_start']) && empty($settings['clip_length']),
	  );
	  $form['settings']['create_clip']['clip_start'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Start clip'),
	    '#description' => t('The starting point of a subclip (in hh:mm:ss.s or number of seconds).'),
	    '#default_value' => !empty($settings['clip_start']) ? $settings['clip_start'] : '',
	  );
	  $form['settings']['create_clip']['clip_length'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Clip length'),
	    '#description' => t('The length of the subclip (in hh:mm:ss.s or number of seconds).'),
	    '#default_value' => !empty($settings['clip_length']) ? $settings['clip_length'] : '',
	  );


	  // Buttons
	  $form['actions'] = array(
	      '#type' => 'actions',
	      '#weight' => 40,
	  );

	  if (!$preset || $preset['module'] == NULL || $preset['overridden']) {
	    $form['actions']['submit'] = array(
	      '#type' => 'submit',
	      '#value' => t('Save'),
	    );
	  }
	  else {
	    $form['actions']['submit'] = array(
	      '#type' => 'submit',
	      '#value' => t('Override defaults'),
	    );
	  }

	  if ($preset) {
	    if ($preset['module'] == NULL) {
	      $form['actions']['delete'] = array(
	        '#type' => 'submit',
	        '#value' => t('Delete'),
	        '#submit' => array('video_preset_delete_submit'),
	        '#limit_validation_errors' => array(),
	      );
	    }
	    elseif ($preset['overridden']) {
	      $form['actions']['revert'] = array(
	        '#type' => 'submit',
	        '#value' => t('Revert'),
	        '#submit' => array('video_preset_revert_submit'),
	        '#limit_validation_errors' => array(),
	      );
	    }
	  }

	  $form['actions']['cancel'] = array(
	    '#type' => 'link',
	    '#title' => t('Cancel'),
	    '#href' => 'admin/config/media/video/presets',
	  );
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