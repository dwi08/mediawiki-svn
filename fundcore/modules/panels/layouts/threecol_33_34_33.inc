<?php
// $Id: threecol_33_34_33.inc,v 1.5 2006/08/22 23:54:20 merlinofchaos Exp $
/**
 * implementation of hook_panels_layouts
 */
function panels_threecol_33_34_33_panels_layouts() {
  $items['threecol_33_34_33'] = array(
    'module' => 'panels',
    'title' => t('Three column 33/34/33'),
    'icon' => 'layouts/threecol_33_34_33.png',
    'theme' => 'panels_threecol_33_34_33',
    'css' => 'layouts/threecol_33_34_33.css',
    'content areas' => array('left' => t('Left side'), 'middle' => t('Middle column'), 'right' => t('Right side')),
  );

  return $items;
}

/**
 * This function uses heredoc notation to make it easier to convert
 * to a template.
 */
function theme_panels_threecol_33_34_33($id, $content) {
  if ($id) {
    $idstr = " id='$id'";
  }

  $output = <<<EOT
<div class="panel-3col-33" $idstr>
  <div class="panel-col-first">
    <div>$content[left]</div>
  </div>

  <div class="panel-col">
    <div>$content[middle]</div>
  </div>

  <div class="panel-col-last">
    <div>$content[right]</div>
  </div>
</div>
<br class="panel-clearer" />
EOT;
  return $output;
}
