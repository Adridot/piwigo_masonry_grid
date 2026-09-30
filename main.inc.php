<?php
/* 
Plugin Name: Masonry Grid
Version: auto
Description: Pinterest style masonry grid on piwigo.
Plugin URI: auto
Author: Bliwi
Author URI: https://bliwi.uk
Has Settings: true
*/
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

if (basename(dirname(__FILE__)) != 'piwigo_masonry_grid')
{
  add_event_handler('init', 'masonry_grid_error');
  function masonry_grid_error()
  {
    global $page;
    $page['errors'][] = 'Masonry Grid folder name is incorrect, uninstall the plugin and rename it to "Piwigo_masonry_grid"';
  }
  return;
}

define('MASONRY',   PHPWG_PLUGINS_PATH . 'piwigo_masonry_grid/');

global $conf;
if (!isset($conf['masonry_grid'])) {
  include(dirname(__FILE__) . '/config_default.inc.php');
  conf_update_param('masonry_grid', $config_default);
  load_conf_from_db();
}
// Handle both serialized-string and array forms depending on Piwigo version
if (isset($conf['masonry_grid'])) {
  if (is_string($conf['masonry_grid'])) {
    $conf['masonry_grid'] = @unserialize($conf['masonry_grid']);
  }
  if (!is_array($conf['masonry_grid'])) {
    $conf['masonry_grid'] = array();
  }
}

add_event_handler('get_admin_plugin_menu_links', 'masonry_admin_menu');
add_event_handler('init', 'masonry_init');

add_event_handler('loc_begin_index_thumbnails', 'masonry_replace_template');
add_event_handler('loc_end_index_thumbnails', 'masonry_assign_params', 50, 2);

function masonry_replace_template()
{
    global $template;

    $template->set_prefilter('index_thumbnails', 'masonry_prefilter');
}

function masonry_prefilter($content)
{
    return file_get_contents(MASONRY . 'template/masonry.tpl');
}

function masonry_admin_menu($menu)
{
  $menu[] = array(
    'NAME' => 'Masonry Grid',
    'URL'  => get_root_url() . 'admin.php?page=plugin-' . basename(dirname(__FILE__)),
  );
  return $menu;
}

function masonry_init()
{
  global $conf, $user, $page;
  $params = $conf['masonry_grid'];

  load_language('plugin.lang', MASONRY);
  load_language('lang', PHPWG_ROOT_PATH.PWG_LOCAL_DIR, array('no_fallback'=>true, 'local'=>true) );

  if (isset($params['nb_image_page']) && is_numeric($params['nb_image_page'])) {
    $user['nb_image_page'] = $params['nb_image_page'];
    $page['nb_image_page'] = $params['nb_image_page'];
  }
}

function masonry_assign_params($tpl_vars, $pictures)
{
  global $template, $conf;
  $params = $conf['masonry_grid'];
  $width = isset($params['width']) ? intval($params['width']) : 220;
  if ($width <= 0) {
    $width = 220;
  }
  $gap = isset($params['gap']) ? intval($params['gap']) : 16;
  $radius = isset($params['corner_radius']) ? intval($params['corner_radius']) : 25;
  if ($radius < 0) {
    $radius = 0;
  }
  // reading order: 'row' = left-to-right then top-to-bottom, 'column' = top-to-bottom then left-to-right
  $order = (isset($params['order']) && $params['order'] === 'column') ? 'column' : 'row';
  $template->assign('derivative_params', ImageStdParams::get_custom($width, 9999));
  // Larger variants for srcset: HiDPI screens, and flex columns stretched wider than $width
  $template->assign('derivative_params_2x', ImageStdParams::get_custom(2 * $width, 9999));
  $template->assign('derivative_params_3x', ImageStdParams::get_custom(3 * $width, 9999));
  $template->assign('MASONRY_WIDTH', $width);
  $template->assign('MASONRY_GAP', $gap);
  $template->assign('MASONRY_RADIUS', $radius);
  $template->assign('MASONRY_ORDER', $order);
  return $tpl_vars;
}
