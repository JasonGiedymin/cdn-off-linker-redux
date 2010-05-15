<?php
/*
Plugin Name: cdn-off-linker-redux
Plugin URI: http://amuxbit.com/projects/cdnofflinkerredux
Description: Based on the original ossdl cdn off linker plugin by W-Mark 
             Kubacki.  Tradditionally replaced the blog URL with another for
             all files under <code>wp-content</code> and
             <code>wp-includes</code>. Static content can then be handled by
             a CDN.

             Note that this is an alpha release, and some enhancements have
             been stripped.

Version: 1.0.0a
Author: Jason Giedymin
Author URI: http://jasongiedymin.amuxbit.com
*/

/**
 * Crude, i'll change this shortly.
 * Not being used.
 */
function ossdl_log( $msg ) {

	# Only enable for Development environments!
	$dev_logging_enabled = false;

	if ( $dev_logging_enabled ) {
		$myFile = "./cdn-off-linker-redux.log";
		$fh = fopen($myFile, 'a') or die("can't open file");

		fwrite($fh, $msg."\n");

		fclose($fh);
	}
}

add_option('ossdl_off_cdn_url', get_option('siteurl'));
$ossdl_off_blog_url = get_option('siteurl');
$ossdl_off_cdn_url = trim(get_option('ossdl_off_cdn_url'));

/**
 * Rewrites URLs, used as a replace-callback.
 */
function ossdl_off_rewriter_new($match) {
        global $ossdl_off_cdn_url;

	// Special case scenarios, which are not used btw
        $excludeCollection = array(".com/?", ".php", "s2member", "facebook", "xd_receiver");

        // Array of regex expressions
	$includeCollection = array("/^.*\.(jpg|jpeg|png|gif|js|css)$/i");

        if( is_array( $includeCollection ) )
        {
                foreach ( $includeCollection as $key => $value )
                {
			$searchPos = preg_match( $value, $match[0]);

                        if ( !empty($searchPos) ) {
				return $ossdl_off_cdn_url.$match[1];
                        }
                }

		return $match[0];
        }

	return $match[0];
}

/**
 * The output filter.
 */
function ossdl_off_filter($content) {
	global $ossdl_off_blog_url, $ossdl_off_cdn_url;

	if ($ossdl_off_blog_url == $ossdl_off_cdn_url) { // no rewrite needed
		return $content;
	}
	else {
		$regex = '#(?<=[\"\'])'.quotemeta($ossdl_off_blog_url).'(?:(/(?:wp\-content|wp\-includes)[^\"\']+)|(/[^/\"\']+))(?=[\"\'])#';
		return preg_replace_callback($regex, 'ossdl_off_rewriter_new', $content);
	}
}

/**
 * Starts the filter.
 */
function do_ossdl_off_ob_start() {
	global $ossdl_off_blog_url, $ossdl_off_cdn_url;

	if ($ossdl_off_blog_url != $ossdl_off_cdn_url) {
		ob_start('ossdl_off_filter');
	}
}

add_action('template_redirect', 'do_ossdl_off_ob_start', 2);

/********** WordPress Administrative ********/
add_action('admin_menu', 'ossdl_off_menu');

function ossdl_off_menu() {
	add_options_page('OSSDL CDN off-linker', 'OSSDL CDN off-linker', 8, __FILE__, 'ossdl_off_options');
}

function ossdl_off_options() {
if ( isset($_POST['action']) && ( $_POST['action'] == 'update_ossdl_off' )){
	update_option('ossdl_off_cdn_url', $_POST['ossdl_off_cdn_url']);
}

?>
<div class="wrap">
	<h2>OSSDL CDN off-linker</h2>
	<p>Many Wordpress plugins misbehave when linking to their JS or CSS files, and yet there is no filter to let your old posts point to a statics' site or CDN for images.  Therefore this plugin replaces at any links into <code>wp-content</code> and <code>wp-includes</code> directories (except for PHP files) the <code>blog_url</code> by the URL you provide below.  That way you can either copy all the static content to a dedicated host or mirror the files at a CDN by <a href="http://knowledgelayer.softlayer.com/questions/365/How+does+Origin+Pull+work%3F" target="_blank">origin pull</a>.</p>
	<p>
		<strong style="color: red">WARNING:</strong> Test some static urls e.g., http://static.mydomain.com/wp-includes/js/prototype.js to ensure your CDN service is fully working before saving changes.
	</p>

	<p>
		<form method="post" action="">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="ossdl_off_cdn_url">off-site URL</label></th>
					<td><input type="text" name="ossdl_off_cdn_url" value="<?php echo get_option('ossdl_off_cdn_url'); ?>" size="64" /></td>
					<td><span class="setting-description">The new URL to be used in place of <?php echo get_option('siteurl'); ?> for rewriting.</span></td>
				</tr>
			</table>

			<input type="hidden" name="action" value="update_ossdl_off" />

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</p>
</div>
<?php
}

