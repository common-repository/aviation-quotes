<?php
/*
Plugin Name: Aviation Quotes
Version: 0.2
Plugin URI: http://www.beliefmedia.com/wp-plugins/aviation-quotes.php
Description: Displays a random aviation quote via the BeliefMedia Quotes API. Use as &#91;aviationquote&#93;. Wrap in paragraph tags with &#91;aviationquote p="1"&#93;. Wrap the attribution in html tag with &#91;aviationquote tags="strong,em"&#93;. Change the separator (between quote and subject) with &#91;aviationquote sep="::"&#93; (defaults to em-dash).
Author: Martin Khoury
Author URI: http://www.beliefmedia.com/
*/


function beliefmedia_aviationquote($atts) {
  $atts = shortcode_atts(array(
    'sep' => '&mdash;',
    'tags' => false,
    'p' => false,
    'offline' => 'The Aviation Quotes <a href="http://www.beliefmedia.com/">API</a> is temporarily offline.',
    'cache_temp' => 360,
    'cache' => 3600,
  ), $atts);

 $transient = 'bmaq_' . md5(serialize($atts));
 $cachedposts = get_transient($transient);
 if ($cachedposts !== false) {
 return $cachedposts;

 } else {

 /* Construct tag(s) for attribution */
 if ($atts['tags'] !== false) {
  $tags = explode(',', $atts['tags']);
    foreach($tags as $tag) {
    $htmltag .= '<' . $tag . '>';
   }
  $html_tags_closing = str_replace('<', '</', $htmltag);
  }

  /* Get quote from BeliefMedia API */
  $json = @file_get_contents('http://api.beliefmedia.com/aviation-quotes/random.php');
  if ($json !== false) $data = json_decode($json, true);

   if ($data['status'] == '200') {

     /* Get quote */
     $thequote = (string) $data['data']['quote'];

     /* Attribution? */
     $attribution = ($data['data']['attribution'] != '') ? $data['data']['attribution'] : false;

     /* Apply tags around attribution? */
     if ( ($atts['tags'] !== false) && ($attribution != false) ) $attribution = $htmltag . $data['data']['attribution'] . $html_tags_closing;

     /* Attribution */
     if ($attribution !== false) $attribution = '&nbsp;' . $atts['sep'] . '&nbsp;' . $attribution;

     /* String to return */
     $result = ($attribution != false) ? $thequote . $attribution : $thequote;

     /* Wrap in paragraph tags? */
     if ($atts['p'] !== false) $result = '<p>' . $result . '</p>';

     /* Set transient */
     set_transient($transient, $result, $atts['cache']);

     } else {

     $result = $atts['offline'];
     if ($atts['p'] !== false) $result = '<p>' . $result . '</p>';
     set_transient($transient, $result, $atts['cache_temp']);
   }

  }
 return $result;
}
add_shortcode('aviationquote','beliefmedia_aviationquote');
	

/*
	Menu Links
*/


function beliefmedia_aviationquotes_action_links($links, $file) {
  static $this_plugin;
  if (!$this_plugin) {
   $this_plugin = plugin_basename(__FILE__);
  }

  if ($file == $this_plugin) {
	$links[] = '<a href="http://www.beliefmedia.com/wp-plugins/aviation-quotes.php" target="_blank">Support</a>';
	$links[] = '<a href="http://www.beliefmedia.com/aviation-quotes" target="_blank">BM</a>';
  }
 return $links;
}
add_filter('plugin_action_links', 'beliefmedia_aviationquotes_action_links', 10, 2);



/*
	Delete Transient Data on Deactivation
*/

	
function remove_beliefmedia_aviationquotes_options() {
  global $wpdb;
   $wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE ('_transient%_bmaq_%')" );
   $wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE ('_transient_timeout%_bmaq_%')" );
}
register_deactivation_hook( __FILE__, 'remove_beliefmedia_aviationquotes_options' );


/*
	Uncomment if shortcode isn't working in widgets
*/


// add_filter('widget_text', 'do_shortcode');