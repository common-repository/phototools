<?php
/**
 * @package phototools
 * @version 1.7
 */
/*
Plugin Name: Phototools: Basics
Plugin URI: Donate link: https://gerhardhoogterp.nl/plugins/&utm_source=readme&utm_campaign=phototools
Description: Dashboard widgets and more for photosites
Author: Gerhard Hoogterp
Version: 1.7
Author URI: https://gerhardhoogterp.nl/
*/

if (!defined('WPINC')) {
	die;
}

class phototools_class {

	const FS_TEXTDOMAIN = 'phototools';
	const FS_PLUGINID = 'phototools';
	const NO_IMAGE = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 39.687 39.688" height="150" width="150"><g transform="matrix(1.76368 0 0 1.6497 -8.564 -130.72)" fill="#fff" stroke="gray" stroke-width=".155"><rect width="22.34" height="23.898" x="4.936" y="79.318" ry=".399" stroke-linecap="round" stroke-miterlimit="0" paint-order="fill markers stroke"/><path d="M4.936 79.318l22.34 23.898M27.275 79.318l-22.34 23.898" fill-rule="evenodd"/></g></svg>';

	public function __construct() {
		$phototools_options = get_option('phototools_options');

		register_activation_hook(__FILE__, array(
			$this,
			'activate'
		));
		register_deactivation_hook(__FILE__, array(
			$this,
			'deactivate'
		));

		add_action('init', array(
			$this,
			'myTextDomain'
		));

//		add_action('admin_enqueue_scripts', array(
		add_action('init', array(
			$this,
			'add_headers'
		));

		add_action('admin_menu', array(
			$this,
			'add_phototools_menuitem'
		) , 1, 5);
		
		add_action('admin_init', array(
			$this,
			'register_settingspage'
		));

		if ($phototools_options['replaceActivity']):
			add_action('wp_dashboard_setup', array(
				$this,
				'loadNewDashboardWidgets'
			),1,30);
		endif;
		if ($phototools_options['enableShortCodeInWidgets']):
			add_filter('widget_text', 'shortcode_unautop');
			add_filter('widget_text', 'do_shortcode');
		endif;
		
		
		if ($phototools_options['enableTaxonomy']):
			add_action('init', array(
				$this,
				'doRegisterTaxonomies'));
		endif;

		if ($phototools_options['addRichData']):
			add_action('wp_head', array($this,'rich_photographyinfo'));
		endif;
		
		if ($phototools_options['redirectToLatestPost']):
                    add_action('template_redirect', array($this,'redirect_to_latest_post'));
                endif;                    

                             
                add_action('widgets_init', function() { return register_widget("latestPostWidget_class");} );              
              
	}
	// defaults
	

	public function myTextDomain() {
                load_plugin_textdomain(self::FS_TEXTDOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages/' ); 
	}

	function PluginLinks($links, $file) {
		if (strpos($file, self::FS_PLUGINID . '.php') !== false) {
			$links[] = '<a href="' . admin_url() . 'admin.php?page=phototools">' . __('General info', self::FS_TEXTDOMAIN) . '</a>';
			$links[] = '<a href="' . admin_url() . 'admin.php?page=' . self::FS_PLUGINID . '">' . __('Settings', self::FS_TEXTDOMAIN) . '</a>';

		}
		return $links;
	}

	/* ****************************************************************************
	 *  Add the javascript
	 ***************************************************************************** */

	function add_headers() {
		wp_register_style('phototools-style', plugins_url('/css/phototools.css', __FILE__));
		wp_enqueue_style('phototools-style');
	}
	
	public function activate() {

		$phototools = get_option('phototools_list');
		$phototools[self::FS_PLUGINID] = plugin_basename(__FILE__);;
		update_option('phototools_list', $phototools);

		$phototools_options = get_option('phototools_options');
		if (empty($phototools_options)):
			$phototools_options['replaceActivity'] = true;
			$phototools_options['enableTaxonomy'] = true;
			$phototools_options['enableShortCodeInWidgets'] = true;
			$phototools_options['enableFuzzyDates'] = true;
			$phototools_options['addRichData'] = true;
			$phototools_options['publishingSoon'] = 5;
			$phototools_options['publishedRecently'] = 2;
			$phototools_options['recentComments'] = 5;
			$phototools_options['redirectToLatestPost'] = true;
			$phototools_options['redirectString'] = 'latest';
			

			update_option('phototools_options', $phototools_options);
		endif;
	
	}

	public function deactivate() {
		$phototools = get_option('phototools_list');
		$self = self::FS_PLUGINID;
		unset($phototools[$self]);
		if (!empty($phototools)):
			update_option('phototools_list', $phototools);
		else:
			delete_option('phototools_list');
		endif;
	}

	function add_phototools_menuitem() {
		if (empty($GLOBALS['admin_page_hooks']['phototools'])):
			add_menu_page(__('Phototools', self::FS_TEXTDOMAIN) , __('Phototools', self::FS_TEXTDOMAIN) , 'manage_options', 'phototools', array(
				$this,
				'phototools_info'
			) , 'dashicons-camera', 25);
		endif;
		//$this->create_submenu_item();
		
	}
	public function phototools_info() {
?>
			<div class="phototools-wrap">
                            <div class="phototools-wrap-left">
                                <div class="icon32" id="icon-options-general"></div>
                                <h1><?php _e('Phototools', self::FS_TEXTDOMAIN); ?></h1>
                                
                                <p>
                                <?php _e('Phototools is a collection of plugins which add functionality for those who use WordPress to run a photoblog or gallery site or something alike.', self::FS_TEXTDOMAIN);
?></p>
                                
                                <form method="POST" action="options.php">
                                <?php
		settings_fields('phototools_group');
		do_settings_sections('phototools');
		submit_button();
?>
                        </form>
                                
                                
                            </div>
                            
                            <div class="phototools-wrap-right">
                                <h2><?php _e('The following plugins in this series are installed:', self::FS_TEXTDOMAIN); ?></h2>
                                <?php
		$phototools = get_option('phototools_list');
		foreach ($phototools as $id => $shortPath):
			$plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . $shortPath, true);
?>
                                        <div class="card">
                                        <h3><a href="<?php echo $plugin['PluginURI']; ?>" target="_blank" rel="”noopenener noreferrer"><?php echo $plugin['Name'] . ' ' . $plugin['Version']; ?></a></h3>
                                        <p><?php echo $plugin['Description']; ?></p>
                                        </div>
                                        <?php
		endforeach;
?>
                            </div>
			</div>
		<?php
	}

	public function validate_options($options) {
	
	/*
		if (!array_key_exists('replaceActivity',$options))            $options['replaceActivity']=true;
		if (!isset($options['publishingSoon']))                       $options['publishingSoon'] = 5;
		if (!isset($options['publishedRecently']))                    $options['publishedRecently'] = 5;
		if (!isset($options['recentComments']))                       $options['recentComments'] = 5;
		if (!isset($options['enableTaxonomy']))                       $options['enableTaxonomy']=false;
		if (!array_key_exists('enableShortCodeInWidgets',$options))   $options['enableShortCodeInWidgets']=false;
		if (!array_key_exists('enableFuzzyDates',$options))           $options['enableFuzzyDates']=true;
        */
        
                if (isset($options['redirectToLatestPost']) && empty($options['redirectString'])) {
                    $options['redirectString']='latest';
                }
                
		return $options;
	}

	public function register_settingspage() {
		register_setting('phototools_group', 'phototools_options', array(
			$this,
			'validate_options'
		));

		add_settings_section('phototools_general_settings', __('Dashboard', self::FS_TEXTDOMAIN) , '', 'phototools');

		add_settings_field('replaceActivity', __('Replace activity widget:', self::FS_TEXTDOMAIN) , array(
			$this,
			'replace_activity_CB'
		) , 'phototools', 'phototools_general_settings', ['label_for' => 'replaceActivity']);

		add_settings_field('publishingSoon', __('No. publishing soon:', self::FS_TEXTDOMAIN) , array(
			$this,
			'maxPublishingSoon'
		) , 'phototools', 'phototools_general_settings', ['label_for' => 'publishingSoon']);
		add_settings_field('publishedRecently', __('No. published recently:', self::FS_TEXTDOMAIN) , array(
			$this,
			'maxPublishedRecentlySoon'
		) , 'phototools', 'phototools_general_settings', ['label_for' => 'publishedRecently']);
		add_settings_field('recentComments', __('No. recent comments:', self::FS_TEXTDOMAIN) , array(
			$this,
			'recentCommentsPosted'
		) , 'phototools', 'phototools_general_settings', ['label_for' => 'recentComments']);

		add_settings_section('phototools_bonus_settings', __('Extra\'s', self::FS_TEXTDOMAIN) , '', 'phototools');
		add_settings_field('enableShortCodeInWidgets', __('Enable use of shortcodes in widgets', self::FS_TEXTDOMAIN) , array(
			$this,
			'phototools_shortcodeInWidgets_CB'
		) , 'phototools', 'phototools_bonus_settings', ['label_for' => 'enableShortCodeInWidgets']);
		
		add_settings_field('enableTaxonomy', __('Enable use of the photogroup taxonomy', self::FS_TEXTDOMAIN) , array(
			$this,
			'phototools_useTaxonomy_CB'
		) , 'phototools', 'phototools_bonus_settings', ['label_for' => 'enableTaxonomy']);
		
		add_settings_field('enableFuzzyDates', __('Enable fuzzy dates', self::FS_TEXTDOMAIN) , array(
			$this,
			'phototools_useFuzzyDates_CB'
		) , 'phototools', 'phototools_bonus_settings', ['label_for' => 'enableFuzzyDates']);
		
		add_settings_field('addRichData', __('Add rich data to posts with a featured image', self::FS_TEXTDOMAIN) , array(
			$this,
			'phototools_addRichData_CB'
		) , 'phototools', 'phototools_bonus_settings', ['label_for' => 'addRichData']);

		add_settings_field('redirectToLatestPost', __('use the "redirect to the latest post"', self::FS_TEXTDOMAIN) , array(
			$this,
			'phototools_redirectToLatestPost_CB'
		) , 'phototools', 'phototools_bonus_settings', ['label_for' => 'redirectToLatestPost']);
	
                add_settings_field('redirectString', __('Redirect string', self::FS_TEXTDOMAIN) , array(
			$this,
			'phototools_redirectString'
		) , 'phototools', 'phototools_bonus_settings', ['label_for' => 'redirectString']);
	
	
	}

	public function replace_activity_CB() {
		$phototools_options = get_option('phototools_options');
		print '<input type="checkbox" id="replaceActivity" name="phototools_options[replaceActivity]" ' . ($phototools_options['replaceActivity'] ? 'checked' : '') . ' >';
	}

	public function maxPublishingSoon() {
		$phototools_options = get_option('phototools_options');
		print '<input type="number" min=1 max=30 id="publishingSoon" name="phototools_options[publishingSoon]" value="' . $phototools_options['publishingSoon'] . '"> (1-30)';
	}

	public function maxPublishedRecentlySoon() {
		$phototools_options = get_option('phototools_options');
		print '<input type="number" min=1 max=30 id="publishedRecently" name="phototools_options[publishedRecently]" value="' . $phototools_options['publishedRecently'] . '"> (1-30)';
	}

	public function recentCommentsPosted() {
		$phototools_options = get_option('phototools_options');
		print '<input type="number" min=1 max=30 id="recentComments" name="phototools_options[recentComments]" value="' . $phototools_options['recentComments'] . '"> (1-30)';
	}
	public function phototools_shortcodeInWidgets_CB() {
		$phototools_options = get_option('phototools_options');
		print '<input type="checkbox" id="enableShortCodeInWidgets" name="phototools_options[enableShortCodeInWidgets]" ' . ($phototools_options['enableShortCodeInWidgets'] ? 'checked' : '') . ' >';
	}

	public function phototools_useTaxonomy_CB() {
		$phototools_options = get_option('phototools_options');
		print '<input type="checkbox" id="enableTaxonomy" name="phototools_options[enableTaxonomy]" ' . ($phototools_options['enableTaxonomy'] ? 'checked' : '') . ' >';
	}

	public function phototools_useFuzzyDates_CB() {
		$phototools_options = get_option('phototools_options');
		print '<input type="checkbox" id="enableFuzzyDates" name="phototools_options[enableFuzzyDates]" ' . ($phototools_options['enableFuzzyDates'] ? 'checked' : '') . ' >';
	}
	
	public function phototools_addRichData_CB() {
		$phototools_options = get_option('phototools_options');
		print '<input type="checkbox" id="addRichData" name="phototools_options[addRichData]" ' . ($phototools_options['addRichData'] ? 'checked' : '') . ' >';
	}
	
	public function phototools_redirectToLatestPost_CB() {
		$phototools_options = get_option('phototools_options');
		print '<input type="checkbox" id="redirectToLatestPost" name="phototools_options[redirectToLatestPost]" ' . ($phototools_options['redirectToLatestPost'] ? 'checked' : '') . ' >';
	}
	
	public function phototools_redirectString() {
		$phototools_options = get_option('phototools_options');
		print '<input id="redirectString" name="phototools_options[redirectString]" value="' . $phototools_options['redirectString'] . '">';
	}
	
	
	/* ****************************************************************************
	Start general methods
	***************************************************************************** */

        function current_time( $type, $gmt = 0 ) {
                switch ( $type ) {
                        case 'mysql':
                                return ( $gmt ) ? gmdate( 'Y-m-d H:i:s' ) : gmdate( 'Y-m-d H:i:s', ( gmdate('U') + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );
                        case 'timestamp':
                                return ( $gmt ) ? gmdate('U') : gmdate('U') + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
                        default:
                                return ( $gmt ) ? date( $type ) : date( $type, gmdate('U') + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
                }
        }	
	
	
        const MINinSEC  = 60;                      const TWOMINinSEC   = 2 * self::MINinSEC;
        const HOURinSEC = 60 * self::MINinSEC;     const TWOHOURinSEC  = 2 * self::HOURinSEC;
        const DAYinSEC  = 24 * self::HOURinSEC;    const TWODAYSinSEC  = 2 * self::DAYinSEC;
        const WEEKinSEC =  7 * self::DAYinSEC;     const FOURWEEKSinSEC = 4 * self::WEEKinSEC;
        const HALFDAYinSEC = 12 * self::HOURinSEC;

        function daysDiff($f,$t) {
            $datediff = $t - $f;
            return abs(round($datediff / (60 * 60 * 24)));
        }
        
        
        function daysText($time) {
            $phototools_options = get_option('phototools_options');
            $df = get_option('date_format');

            if (!$phototools_options['enableFuzzyDates']) return date($df, $time);

            $now = $this->current_time('timestamp');
            $diff = $now - $time;
            $daysDiff = $this->daysDiff($now,$time);

            if($diff == 0) return __('now',self::FS_TEXTDOMAIN); 

            if ($diff>0):
                    if ($daysDiff==0  || $diff < self::HALFDAYinSEC):
                            if ($diff < self::MINinSEC)                         return __('just',self::FS_TEXTDOMAIN);
                            if ($diff < self::TWOMINinSEC)                      return __('1 minute ago',self::FS_TEXTDOMAIN);
                            if ($diff < self::HOURinSEC)                        return round($diff / self::MINinSEC) . __(' minutes ago',self::FS_TEXTDOMAIN);
                            if ($diff < self::DAYinSEC)                         return round($diff / self::HOURinSEC) . __(' hours ago',self::FS_TEXTDOMAIN);
                            if ($diff < self::TWOHOURinSEC)                     return __('1 hour ago',self::FS_TEXTDOMAIN);
                    endif;

                    if( $daysDiff== 1 )                                         return __('yesterday',self::FS_TEXTDOMAIN);
                    if( $daysDiff== 2 )                                         return __('the day before yesterday',self::FS_TEXTDOMAIN);                    
                    if( $daysDiff < 7 )                                         return sprintf(__('%s days ago',self::FS_TEXTDOMAIN),$daysDiff);
                    if( $daysDiff < 31)                                         return sprintf(__('%s weeks ago',self::FS_TEXTDOMAIN),ceil($daysDiff / 7));
                                                                                return date($df, $time);
            else:
                    $diff=-1 * $diff;
                    $daysDiff=-1 * $daysDiff;
                    if($daysDiff == 0 || $diff < self::HALFDAYinSEC):
                            if ($diff < self::MINinSEC)                         return __('in a moment',self::FS_TEXTDOMAIN);
                            if ($diff < self::TWOMINinSEC)                      return __('in a minute',self::FS_TEXTDOMAIN);
                            if ($diff < self::HOURinSEC)                        return sprintf(__('in %s minutes',self::FS_TEXTDOMAIN),round($diff / self::MINinSEC));
                            if ($diff < self::TWOHOURinSEC)                     return __('in an hour',round($diff / SELF::MINinSEC));
                            if ($diff < self::DAYinSEC )                        return sprintf(__('in %s hours',self::FS_TEXTDOMAIN),round($diff / self::HOURinSEC));
                    endif;
                    if( $daysDiff== 1 )                                         return __('tomorrow',self::FS_TEXTDOMAIN);
                    if( $daysDiff== 2 )                                         return __('the day after tomorrow',self::FS_TEXTDOMAIN);                    
                    if( $daysDiff < 7)                                          return sprintf(__('in %s days',self::FS_TEXTDOMAIN),$daysDiff);
                    if( $daysDiff < 31)                                         return sprintf(__('in %s weeks',self::FS_TEXTDOMAIN),floor($daysDiff / 7));
                                                                                return date($df, $time);
            endif;
	}
	
	
	function loadNewDashboardWidgets() {
		remove_meta_box('dashboard_activity', 'dashboard', 'normal');

		wp_add_dashboard_widget('publishingsoon', // Widget slug.
		__('Publishing Soon',self::FS_TEXTDOMAIN), // Title.
		array(
			$this,
			'publishingSoon'
		) // Display function.
		);
		wp_add_dashboard_widget('publishedRecent', // Widget slug.
		__('Published Recent',self::FS_TEXTDOMAIN), // Title.
		array(
			$this,
			'publishedRecent'
		) // Display function.
		);

		wp_add_dashboard_widget('recentcomments', // Widget slug.
		__('Recent Comments',self::FS_TEXTDOMAIN), // Title.
		array(
			$this,
			'recentComments'
		) // Display function.
		);
	}

	function showPostPublic($post) {
		$thumb_id = get_post_thumbnail_id($post->ID);
		$thumb = wp_get_attachment_image_src($thumb_id, 'thumbnail');
		$df = get_option('date_format');
		$tf = get_option('time_format');
		$permalink = get_permalink($post->ID);

?>
            <div class="phototools-showpost" style="background-image: url('<?php echo $thumb[0]; ?>')">
                <a href="<?php echo $permalink; ?>" title="<?php echo __('view post', self::FS_TEXTDOMAIN); ?>" rel="”noopenener noreferrer">
                <?php echo '<div class="text"><strong>' . $post->post_title . '</strong></a><br /><span title="'.date($df . ', ' . $tf, strtotime($post->post_date)).'">' . $this->daysText(strtotime($post->post_date)) . '</span></div>'; ?>
            </div></a><?php
	}
	
	
	function showPost($post) {
		$thumb_id = get_post_thumbnail_id($post->ID);
		$thumb = wp_get_attachment_image_src($thumb_id, 'thumbnail');
		$df = get_option('date_format');
		$tf = get_option('time_format');
		$permalink = get_permalink($post->ID);
		$editlink = get_site_url().'/wp-admin/post.php?post=' . $post->ID . '&action=edit';
?>
            <div class="phototools-showpost" style="background-image: url('<?php echo $thumb[0]; ?>')">
                <?php echo '<div class="text"><strong>' . $post->post_title . '</strong><br /><span title="'.date($df . ', ' . $tf, strtotime($post->post_date)).'">' . $this->daysText(strtotime($post->post_date)) . '</span></div>'; ?>
                
                <?php if( current_user_can('edit_others_pages') ): ?>
                <div class="phototools-menu">
                    <p>
                    <a href="<?php echo $editlink; ?>" title="<?php echo __('edit post', self::FS_TEXTDOMAIN); ?>"><span class="dashicons dashicons-edit"></span></a>
                    <a href="<?php echo $permalink; ?>" title="<?php echo __('view post', self::FS_TEXTDOMAIN); ?>" target="_blank" rel="”noopenener noreferrer"><span class="dashicons dashicons-format-image"></span></a>
                    </p>
                </div>
                <?php endif; ?>
            </div><?php
	}

	function showComment($comment) {
		$thumb_id = get_post_thumbnail_id($comment->comment_post_ID);
		$thumb = wp_get_attachment_image_src($thumb_id, 'thumbnail');
		$df = get_option('date_format');
		$tf = get_option('time_format');

		// Straight from the Wordpress source wp-admin/include/dashboard.php
		if ($comment->comment_post_ID > 0) {

			$comment_post_title = _draft_or_post_title($comment->comment_post_ID);
			$comment_post_url = get_the_permalink($comment->comment_post_ID);
			$comment_post_link = "<a href='$comment_post_url'>$comment_post_title</a>";
		}
		else {
			$comment_post_link = '';
		}
		$actions_string = '';
		if (current_user_can('edit_comment', $comment->comment_ID)) {

			$actions = array(
				'approve' => '',
				'unapprove' => '',
				'reply' => '',
				'edit' => '',
				'spam' => '',
				'trash' => '',
				'delete' => '',
				'view' => '',
			);

			$del_nonce = esc_html('_wpnonce=' . wp_create_nonce("delete-comment_$comment->comment_ID"));
			$approve_nonce = esc_html('_wpnonce=' . wp_create_nonce("approve-comment_$comment->comment_ID"));

			$approve_url = esc_url("comment.php?action=approvecomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$approve_nonce");
			$unapprove_url = esc_url("comment.php?action=unapprovecomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$approve_nonce");
			$spam_url = esc_url("comment.php?action=spamcomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$del_nonce");
			$trash_url = esc_url("comment.php?action=trashcomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$del_nonce");
			$delete_url = esc_url("comment.php?action=deletecomment&p=$comment->comment_post_ID&c=$comment->comment_ID&$del_nonce");

			$actions['approve'] = "<a href='$approve_url' data-wp-lists='dim:the-comment-list:comment-$comment->comment_ID:unapproved:e7e7d3:e7e7d3:new=approved' class='vim-a' aria-label='" . esc_attr__('Approve this comment') . "'>" . __('Approve') . '</a>';
			$actions['unapprove'] = "<a href='$unapprove_url' data-wp-lists='dim:the-comment-list:comment-$comment->comment_ID:unapproved:e7e7d3:e7e7d3:new=unapproved' class='vim-u' aria-label='" . esc_attr__('Unapprove this comment') . "'>" . __('Unapprove') . '</a>';
			$actions['edit'] = "<a href='comment.php?action=editcomment&amp;c={$comment->comment_ID}' aria-label='" . esc_attr__('Edit this comment') . "'>" . __('Edit') . '</a>';
			$actions['reply'] = '<a onclick="window.commentReply && commentReply.open(\'' . $comment->comment_ID . '\',\'' . $comment->comment_post_ID . '\');return false;" class="vim-r hide-if-no-js" aria-label="' . esc_attr__('Reply to this comment') . '" href="#">' . __('Reply') . '</a>';
			$actions['spam'] = "<a href='$spam_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID::spam=1' class='vim-s vim-destructive' aria-label='" . esc_attr__('Mark this comment as spam') . "'>" . /* translators: mark as spam link */
			_x('Spam', 'verb') . '</a>';

			if (!EMPTY_TRASH_DAYS) {
				$actions['delete'] = "<a href='$delete_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID::trash=1' class='delete vim-d vim-destructive' aria-label='" . esc_attr__('Delete this comment permanently') . "'>" . __('Delete Permanently') . '</a>';
			}
			else {
				$actions['trash'] = "<a href='$trash_url' data-wp-lists='delete:the-comment-list:comment-$comment->comment_ID::trash=1' class='delete vim-d vim-destructive' aria-label='" . esc_attr__('Move this comment to the Trash') . "'>" . _x('Trash', 'verb') . '</a>';
			}

			$actions['view'] = '<a class="comment-link" href="' . esc_url(get_comment_link($comment)) . '" aria-label="' . esc_attr__('View this comment') . '">' . __('View') . '</a>';

			$actions = apply_filters('comment_row_actions', array_filter($actions) , $comment);
			$i = 0;
			foreach ($actions as $action => $link) {
				++$i;
				((('approve' == $action || 'unapprove' == $action) && 2 === $i) || 1 === $i) ? $sep = '' : $sep = ' | ';

				// Reply and quickedit need a hide-if-no-js span
				if ('reply' == $action || 'quickedit' == $action) {
					$action .= ' hide-if-no-js';
				}

				if ('view' === $action && '1' !== $comment->comment_approved) {
					$action .= ' hidden';
				}
				$actions_string .= "<span class='$action'>$sep$link</span>";
			}
		}

?>
            <div class="phototools-showcomment" style="background-image: url('<?php echo $thumb[0]; ?>')">
            
                <div class="phototools-column">
                    <div class="text"><?php echo $comment->comment_content; ?><br>
                    <strong>
                        <a href="mailto:<?php echo $comment->comment_author_email ?>"><?php echo $comment->comment_author ?></a></strong>,
                    <?php echo '<span title="'.date($df . ', ' . $tf, strtotime($comment->comment_date)).'">' . $this->daysText(strtotime($comment->comment_date)) . '</span>'; ?></div>
                    <?php if ($actions_string): ?>
                            <div class="text row-actions"><?php echo $actions_string; ?></div>
                    <?php
		endif; ?>
                </div>
            </div><?php
	}

	function publishingSoon() {

		$phototools_options = get_option('phototools_options');
		$args = array(
			'post_type' => 'post',
			'posts_per_page' => ($phototools_options['publishingSoon'] ? $phototools_options['publishingSoon'] : 5) ,
			'orderby' => 'date',
			'order' => 'asc',
			'no_found_rows' => true, // Get 5 poss and bail. Make our query more effiecient
			'suppress_filters' => true, // We don't want any filters to alter this query
			'date_query' => array(
				array(
					'after' => $this->current_time( 'mysql') ,
					'inclusive' => true, // Don't include the current post in the query,
				)
			)
		);
		$posts = query_posts($args);
		if (count($posts)):
                    print '<div style="text-align:right;">'.$this->current_time( 'mysql').'</div>';
                    foreach ($posts as $post):
                            $this->showPost($post);
                    endforeach;
                else:
                    print __('No posts planned.',self::FS_TEXTDOMAIN);
                endif;

	}

	function publishedRecent($howMany = 0) {
		$phototools_options = get_option('phototools_options');
		
		$default = ($phototools_options['publishedRecently'] 
                                        ? $phototools_options['publishedRecently'] 
                                        : 5
                                        );
                $howMany = $howMany==0
                                ? $default
                                : $howMany;
		$args = array(
			'post_type' => 'post',
			'posts_per_page' => $howMany ,
			'orderby' => 'date',
			'order' => 'desc',
			'no_found_rows' => true, // Get 5 poss and bail. Make our query more effiecient
			'suppress_filters' => true, // We don't want any filters to alter this query
			'date_query' => array(
				array(
					'before' => $this->current_time( 'mysql' ) ,
					'inclusive' => false, // Don't include the current post in the query
				)
			)
		);
		$posts = query_posts($args);
		if (count($posts)):
                    print '<div style="text-align:right;">'.$this->current_time( 'mysql').'</div>';
                    foreach ($posts as $post):
                            if( current_user_can('edit_others_pages') ):
                                $this->showPost($post);
                                else:
                                $this->showPostPublic($post);
                            endif;
                    endforeach;
                else:
                    print __('No posts posted yet.',self::FS_TEXTDOMAIN);
                endif;
	}

        function getLatestPost() {
		$args = array(
			'post_type' => 'post',
			'posts_per_page' => 1 ,
			'orderby' => 'date',
			'order' => 'desc',
			'no_found_rows' => true, // Get 5 poss and bail. Make our query more effiecient
			'suppress_filters' => true, // We don't want any filters to alter this query
			'date_query' => array(
				array(
					'before' => $this->current_time( 'mysql' ) ,
					'inclusive' => false, // Don't include the current post in the query
				)
			)
		);
		$post = current(query_posts($args));
		return get_permalink($post->ID);
	}
	
        function redirect_to_latest_post() {
            $phototools_options = get_option('phototools_options');
            if (!empty($phototools_options['redirectString'])) {
           
                if ( 0 === stripos( $_SERVER['REQUEST_URI'], '/'.$phototools_options['redirectString'] ) ) {
                    $latestPost = $this->getLatestPost();
                    wp_redirect( $latestPost);
                    exit;
                }
            }
        }
	
	
	function recentComments() {
		$phototools_options = get_option('phototools_options');
		$comments = get_comments(array(
			'number' => ($phototools_options['recentComments'] ? $phototools_options['recentComments'] : 5)
		));
		if (count($comments)):
                    foreach ($comments as $comment):
                        $this->showComment($comment);
                    endforeach;
                else:
                    print __('No comments found.',self::FS_TEXTDOMAIN);
                endif;
	}

	/* ****************************************************************************
	Taxonomy for attachments
	***************************************************************************** */
	function doRegisterTaxonomies() {
	
	$labels = array(
		'name'                       => _x( 'Photogroup', 'taxonomy general name', self::FS_TEXTDOMAIN ),
		'singular_name'              => _x( 'Group', 'taxonomy singular name', self::FS_TEXTDOMAIN ),
		'search_items'               => __( 'Search groups', self::FS_TEXTDOMAIN ),
		'popular_items'              => __( 'Popular groups', self::FS_TEXTDOMAIN ),
		'all_items'                  => __( 'All groups', self::FS_TEXTDOMAIN ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit photogroup', self::FS_TEXTDOMAIN ),
		'update_item'                => __( 'Update photogroup', self::FS_TEXTDOMAIN ),
		'add_new_item'               => __( 'Add new photogroup', self::FS_TEXTDOMAIN ),
		'new_item_name'              => __( 'New photogroup', self::FS_TEXTDOMAIN ),
		'separate_items_with_commas' => __( 'Separate groups with commas', self::FS_TEXTDOMAIN ),
		'add_or_remove_items'        => __( 'Add or remove Photogroups', self::FS_TEXTDOMAIN ),
		'choose_from_most_used'      => __( 'Choose from the most used photogroups', self::FS_TEXTDOMAIN ),
		'not_found'                  => __( 'No groups found.', self::FS_TEXTDOMAIN ),
		'menu_name'                  => __( 'Photogroups', self::FS_TEXTDOMAIN ),
	);

	 $args = array(
		'hierarchical'          => true,
		'labels'                => $labels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		'show_in_menu'				=> true,
		'update_count_callback' => '',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'photogroups' ),
		'description'				=> __('Group photos together',self::FS_TEXTDOMAIN),
	);
	
		register_taxonomy( 'photogroups', 'attachment', $args );	
	}

	function rich_photographyinfo() {
		$post = $GLOBALS['post'];
		$post_thumbnail_id = get_post_thumbnail_id($GLOBALS['post']->ID);;
		if ($post_thumbnail_id):
			$thumb = wp_get_attachment_metadata( $post_thumbnail_id, true );
			?>
			<script type='application/ld+json'>
			{
				"@context": "http://schema.org/",
				"@type": "Photograph",
				"commentCount": "<?php echo $post->comment_count;?>",
				"copyrightYear": "<?php echo date('Y',$thumb['image_meta']['created_timestamp']);?>",
				"dateCreated": "<?php echo date('c',$thumb['image_meta']['created_timestamp']);?>",
				"datePublished": "<?php echo date('c',strtotime($post->post_date));?>",
				"discussionUrl": "<?php echo get_comments_link( $post->ID ); ?> ",
				"fileFormat": "<?php echo $thumb['sizes']['thumbnail']['mime-type']; ?>",
				"headline": "<?php echo $post->post_title; ?>",
				"isAccessibleForFree": "true",
				"license": "<?php echo $thumb['image_meta']['copyright']; ?>",
				"thumbnailUrl": "<?php echo the_post_thumbnail_url( 'thumbnail ');?>",
				"description": "<?php echo $post->post_excerpt; ?>",
				"mainEntityOfPage": "<?php echo $post->guid; ?>"
			}
			</script>
			<?php
		endif;
	}

}

class latestPostWidget_class extends WP_Widget {
        const FS_TEXTDOMAIN = 'phototools';
        
	public function __construct() {
		parent::__construct(false, $name         = __('Phototools: latest post widget', self::FS_TEXTDOMAIN) , array(
			'description'              => __('Showing the latest posts with thumbnail', self::FS_TEXTDOMAIN)
		));
	}

	// widget form creation
	function form($instance) {
		$phototoolsWidget_options = get_option('photoToolsWidget_options');

		// Check values
		if ($instance) {
			$title              = esc_attr($instance['title']);
			$howMany            = (int) $instance['howMany'];

		}
		else {
			$title              = __('Latest posts', self::FS_TEXTDOMAIN);
                        $howMany            = 5;
		}
?>

	    <p>
	    	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', self::FS_TEXTDOMAIN); ?></label>
	    	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
	    	
	    	<label for="<?php echo $this->get_field_id('howMany'); ?>"><?php _e('How many entries', self::FS_TEXTDOMAIN); ?></label>
	    	<input class="widefat" id="<?php echo $this->get_field_id('howMany'); ?>" name="<?php echo $this->get_field_name('howMany'); ?>" type="text" value="<?php echo $howMany; ?>" />
	    </p>
	    
	    <?php
	}

	// widget update
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		// Fields
		$instance['title']          = strip_tags($new_instance['title']);
		$instance['howMany']        = $new_instance['howMany'];

		return $instance;
	}

	// widget display
	function widget($args, $instance) {
                global $phototools;
		extract($args);

		// these are the widget options
		$title = apply_filters('widget_title', $instance['title']);
                echo $before_widget;

                // Display the widget
                echo '<div class="widget-text wp_widget_plugin_box phototools_widget_class">';

                // Check if title is set
                if ($title) {
                        echo $before_title . $title . $after_title;
                }
                $phototools->publishedRecent($instance['howMany']);

                echo '</div>';
                echo $after_widget;
	}
}


$phototools = new phototools_class();
?>
