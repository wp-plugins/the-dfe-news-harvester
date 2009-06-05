<?php
/*
Plugin Name: DFE News Harvester
Plugin URI: http://holisticnetworking.net/plugins/2009/04/27/the-dfe-news-harvester-plugin/
Description: Leverages WP's RSS functions to register, read and harvest from desired feeds.
Author: Tom Belknap
Version: 0.8
Author URI: http://holisticnetworking.net/
*/ 

//==============================================================================================
// Change Log:
//	0.1 ~ initial release
//  0.2 ~ numerous improvements including:
//			1.  Add publish date to RSS list view
//			2.  Option to use the original pub date instead of current date for published
//				articles.
//			3.  Add "feature" category automatically.  Oops!
//			4.  Filter available feeds by category
//			5.  Option to exclude categories from pick list in RSS list view
//  0.3 ~ creating NewsHarvester class to push down code volume
//  0.4 ~ Correcting error that prevented feed suffixes from being appended to the final
//			post.
//  0.5 ~ Cleaned up and improved createPosts() function.  Moved many functions over to methods 
//			of the NewsHarvester class.  Creating option to edit registered feeds.
//  0.6 ~ Added a new "Harvest This" feature which acts like "Press This," but with DFE News
//			Harvester meta fields for easy inclusion of news you find anywhere on the internet.
//  0.7 ~ Grr!  Didn't realize WP Plugins would change the name of the folder.  Had to make some
//			changes to some of the file calls for non-MU users (see comments on plugin page).
//  0.8 ~ Fixed some whoopsies in the harvest-this.php file.
//==============================================================================================

// Hooking into WordPress
add_action('admin_menu', 'dfenh_add_menus');
add_action('admin_head', 'dfenh_add_style');
add_action('init', 'dfenh_enqueue_script');


/*///////////////////////////////////////////////////////////////////////
//  Function: dfenh_add_menus()                                        //
//  Purpose: Hooks pages into WP Admin screens, registers JS scrips.   //
///////////////////////////////////////////////////////////////////////*/
function dfenh_add_menus() {
	add_menu_page('News Harvester', 'News Harvester', 10, __FILE__, 'dfenh_menu');
	add_submenu_page(__FILE__, 'Configure Harvester', 'Config', 10, __FILE__.'/config', 'dfenh_config');
	add_submenu_page(__FILE__, 'Register and Configure Feeds', 'Feeds', 10, __FILE__.'/feeds', 'dfenh_feeds');
	add_submenu_page(__FILE__, 'Tag Substitution Configuration', 'Tags', 10, __FILE__.'/tags', 'dfenh_title_to_tags_control');
}

function dfenh_add_style() {
    $url = get_settings('siteurl');
    $url = $url . '/wp-content/plugins/the-dfe-news-harvester/wp-admin.css';
    echo '<link rel="stylesheet" type="text/css" href="' . $url . '" />';
}

function dfenh_enqueue_script() {
	if(stristr( $_SERVER['REQUEST_URI'], 'dfe_news_harvester.php' )) {
		wp_enqueue_script('dfenh_js', '/wp-content/plugins/the-dfe-news-harvester/dfe_news_harvester.js');
	}
}

/*///////////////////////////////////////////////////////////////////////
//  Function: dfenh_menu()                                             //
//  Purpose: Provides main Harvester feeds page.                       //
///////////////////////////////////////////////////////////////////////*/
function dfenh_menu() {
	global $wpdb, $user_ID;
	include_once(ABSPATH.WPINC.'/rss.php');
	
	// Include our class file, populate with the correct options, and go
	include(dirname(__FILE__).'/news_harvester.class.php');
	$nh = new NewsHarvester;
        $nh->getOptions();
	
	// If we have data with which to create articles, pass it to the correct function
	if( $_POST['dfenh_harvest-posts'] ) {
		$nh->createPosts($_POST, $user_ID);
	}
	
	?>
<div class="wrap">
	<h2>Currently Available Feeds:</h2>
	<form action="" method="post" name="dfenh_select_feed" id="dfenh_select_feed">
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" class="form-table">
<?php if(!$nh->feeds) { ?><tr><td>No feeds configured yet.  Add new feeds below.</td></tr><?php } else {
?>			<tr>
				<td align="center" width="60"><label for="dfenh_feed-cat_select">Select Category:</label></td>
				<td align="center" width="60"><select name="dfenh_feed-cat_select" id="dfenh_feed-cat_select">
                	<option value="">All Feeds</option>
				<?php for($q=0; $q<count($nh->pub_cats); $q++) { ?>
						<option value="<?php echo($nh->pub_cats[$q]->cat_ID); ?>" <?php if($_POST['dfenh_feed-cat_select'] == $nh->pub_cats[$q]->cat_ID) { echo('selected="selected"'); } ?>><?php echo($nh->pub_cats[$q]->cat_name); ?></option>
					<?php 	} /* end for */ ?>
				</select></td>
				<td><input name="dfenh_feed-cat_select-submit" type="submit" value="Filter Feeds"></td>
				<td align="center" width="60"><label for="dfenh_feed-select">Select Feed:</label></td>
				<td align="center" width="60"><select name="dfenh_feed-select" id="dfenh_feed-select"> <?php
				for($x=0; $x<count($nh->feeds); $x++) { 
					if( ($_POST['dfenh_feed-cat_select'] == $nh->feeds[$x]['cat']) || !$_POST['dfenh_feed-cat_select']) { ?>
						<option value="<?php echo($nh->feeds[$x]['feed_id']); ?>" id="<?php echo($_POST['dfenh_feed-select']); ?>" <?php if($_POST['dfenh_feed-select'] == $nh->feeds[$x]['feed_id']) { echo('selected="selected"'); } ?>><?php echo($nh->feeds[$x]['name']); ?></option>
					<?php }	
				} /* end for */ ?>
				</select></td>
				<td><input name="dfenh_feed-submit" type="submit" value="Load Feed"></td>
			</tr>
<?php  }/* end else */ ?>
		</table></form> <?php

	if ( $_POST['dfenh_feed-submit'] ) {
		$items = $nh->displayFeed($_POST['dfenh_feed-select']);
		?>
			<form action="" method="post" name="dfenh_harvest" id="dfenh_harvest">
            	<input type="hidden" value="<?php echo $_POST['dfenh_feed-select']; ?>" name="dfenh-source" />
				<h2>Latest News:</h2>
				<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" class="form-table dfenh-table">
    				<tr>
                        <td class="dfenh_thead" valign="bottom" align="center">Select Article</td>
                        <td class="dfenh_thead" valign="bottom">Details</td>
                        <td class="dfenh_thead" valign="bottom">Make Feature</td>
                    </tr>
		<?php for ($x=0; $x<count($items); $x++) {
				$item_date = $nh->getPubdate($items[$x]);
				$unix_date = $nh->getPubdate($items[$x], 'unix');
				?>
                <tr id="<?php echo($x); ?>_info">
					<td width="60" valign="top" align="center"><input class="select" name="dfenh_select-articles[]" type="checkbox" value="<?php echo($x); ?>"></td>
					<td>
                    	<span class="option1"><a class="dfenh_title" href="<?php echo $items[$x]['link']; ?>" title="<?php echo $items[$x]['title']; ?>"><?php echo $items[$x]['title']; ?></a>&nbsp;~&nbsp;<?php echo $item_date; ?>
                        	<input type="hidden" value="<?php echo $items[$x]['link']; ?>" name="<?php echo($x); ?>_dfenh_article-link" />
                            <input type="hidden" value="<?php echo $items[$x]['title']; ?>" name="<?php echo($x); ?>_dfenh_article-orgtit" />
							<input type="hidden" value="<?php echo $unix_date; ?>" name="<?php echo($x); ?>_dfenh_article-pubdate" />
                        </span>
                        <?php // Secondary options for *selected* articles ?>
                        <span class="option2"><span class="dfenh_blocks"><label for="<?php echo($x); ?>_dfenh_article-title2">Teaser Title:</label><input disabled="true" name="<?php echo($x); ?>_dfenh_article-title2" type="text" size="40" /></span></span>
                        <?php // Tertiary options for *featured* articles ?>
                        <span class="option3"><span class="dfenh_blocks"><label for="<?php echo($x); ?>_dfenh_article-image">Article Image:</label><input disabled="true" name="<?php echo($x); ?>_dfenh_article-image" type="text" size="40" /></span>
                            <span class="dfenh_blocks"><label for="<?php echo($x); ?>_dfenh_article-imgcred">Image Credit:</label><input disabled="true" name="<?php echo($x); ?>_dfenh_article-imgcred" type="text" size="40" /></span>
                            <span class="dfenh_blocks"><label for="<?php echo($x); ?>_dfenh_article-summary">Article Summary:</label><textarea disabled="true" name="<?php echo($x); ?>_dfenh_article-summary" cols="40" rows="6"></textarea></span>
                            <span class="dfenh_blocks"><label for="<?php echo($x); ?>_dfenh_article-cats">Categories:</label>
<?php  
	foreach($nh->pub_cats as $cat) {
?>
									<span class="dfenh_inlines"><input disabled="true" name="<?php echo($x); ?>_dfenh_article-cats[]" type="checkbox" value="<?php echo($cat->cat_ID) ?>" <?php if($cat->cat_ID == $nh->default_cat) { echo('checked'); } ?>>&nbsp;<label for="<?php echo($x); ?>_dfenh_article-cats[]"><?php echo($cat->cat_name) ?></label></span>
<?php } ?>
							</span>
<?php if($nh->publish_features != 'draft') { ?>
							<span class="dfenh_blocks">
								<label for="<?php echo($x); ?>_dfenh_article-postdate">Publish Delay:</label>
								<select name="<?php echo($x); ?>_dfenh_article-postdate" id="<?php echo($x); ?>_dfenh_article-postdate">
									<option value="">No Delay</option>
									<option value="1800">Half an Hour</option>
									<option value="3600">One Hour</option>
									<option value="5400">One and a Half Hour</option>
									<option value="7200">Two Hours</option>
									<option value="9000">Two and a Half Hours</option>
									<option value="10800">Three Hours</option>
								</select>
							</span>
<?php } ?>
						</span>
                    </td>
                    <td valign="top">
                    	<span class="option2">
                        	<span class="dfenh_blocks"><label for="<?php echo($x); ?>_dfenh_article-feature">Make Feature:&nbsp;</label><input disabled="true" class="feature" name="<?php echo($x); ?>_dfenh_article-feature" type="checkbox" id="<?php echo($x); ?>" value="1" /></span>
                    </td>
				</tr><?php
		}
		?>
					<tr><td colspan="3" align="right">
						<?php /* if we have a category and feed selected, let's not lose that: */ ?>
						<input type="hidden" name="dfenh_feed-cat_select" value="<?php echo($_POST['dfenh_feed-cat_select']); ?>" />
						<input type="hidden" name="dfenh_feed-select" value="<?php echo($_POST['dfenh_feed-select']); ?>" />
						<input type="hidden" name="dfenh_harvest-posts" value="1" />
						<input name="dfenh_harvest-submit" type="submit" value="Post News Articles"></td></tr>
				</table></form> <?php
	}
}

/*///////////////////////////////////////////////////////////////////////
//  Function: dfenh_config()                                           //
//  Purpose: Provides admin page for Harvester config.                 //
///////////////////////////////////////////////////////////////////////*/
function dfenh_config() {
	global $wpdb;
	// Include our class file, populate with the correct options, and go
	include(dirname(__FILE__).'/news_harvester.class.php');
	
	if($_POST['dfenh_config-save']) {
		$options['featured_category'] = strip_tags(stripslashes($_POST['dfenh_config-feature']));
		$options['number'] = strip_tags(stripslashes($_POST['dfenh_config-feednum']));
		$options['update'] = strip_tags(stripslashes($_POST['dfenh_config-update']));
		$options['append'] = strip_tags(stripslashes($_POST['dfenh_config-append']));
		$options['publish_features'] = strip_tags(stripslashes($_POST['dfenh_config-publish_features']));
		$options['use_pubdate'] = strip_tags(stripslashes($_POST['dfenh_config-use_pubdate']));
		$options['publish_features_pubdate'] = strip_tags(stripslashes($_POST['dfenh_config-publish_features_pubdate']));
		if(is_array($_POST['dfenh_config-omit'])) {
			foreach($_POST['dfenh_config-omit'] as $next) {
				$options['omit'][] = $next;
			}
		}
		
		update_option('dfenh_config', $options);
		?><div id="message" class="updated fade">News Harvester options updated!</div><?php
	}
	
	$nh = new NewsHarvester();
		$nh->getOptions();
	?>
<h1>DFE News Harvester Configuration Options</h1>
<h3>Harvest This!</h3>
<p>Drag the below link into your toolbar to have access to DFE News Harvester anywhere you are on the Internet, just like the WP Press This function</p>
<p class="pressthis"><a href="<?php echo htmlspecialchars( $nh->dfenh_get_shortcut_link() ); ?>" title="<?php echo attribute_escape(__('Harvest This')) ?>"><?php _e('Harvest This') ?></a></p>
<form action="" method="post" name="dfenh_config" id="dfenh_config">
<h3>Configuration</h3>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" class="form-table">
        <tr>
            <th valign="top">Featured Category:</th>
            <td valign="top">
                <p>When you make an article a "feature," you add the option to declare a bunch of extra meta values: an article image link, an image credit, an article summary, extra categories to assign.  It is up to the site administrator to know how to handle these meta values.  Creating a featured article also gives you the option to delay publication for as much as three hours.</p>
                <select name="dfenh_config-feature" size="1">
						<option value="none">Select</option>
<?php  
	foreach($nh->categories as $cat) {
?>
						<option value="<?php echo($cat->cat_ID); ?>" <?php if($cat->cat_ID == $nh->featured_category) { ?> selected <?php } ?>><?php echo($cat->cat_name); ?></option>
<?php } ?>
					</select>
            </td>
        </tr>
        <tr>
            <th valign="top">Display Articles:</th>
            <td valign="top"><p>Set the maximum number of articles which will appear from a given feed.  Note that this is a maximum.  If there are less articles than you specify either in the feed or that are newer than the update time, the actual number may be less.</p>
            	<input name="dfenh_config-feednum" type="text" size="10" value="<?php echo($nh->number) ?>" /></td>
        </tr>
        <tr>
            <th valign="top">Update:</th>
            <td valign="top">
            	<p>In order to avoid posting the same article twice, the plugin will update the "last viewed" time and only display articles newer than that date.  You can set how the last viewed time is determined below:</p>
                <label><input type="radio" name="dfenh_config-update" value="on-feed" id="dfenh_config-update_0" <?php if($nh->update == 'on-feed') { echo('checked'); } ?> />When I view the feed</label><br />
                <label><input type="radio" name="dfenh_config-update" value="on-publish" id="dfenh_config-update_1" <?php if($nh->update == 'on-publish') { echo('checked'); } ?> />Only when I've publish posts from the feed</label><br />
                <label><input type="radio" name="dfenh_config-update" value="do-not" id="dfenh_config-update_1" <?php if($nh->update == 'do-not') { echo('checked'); } ?> />Do not update, display all articles</label>
            </td>
        </tr>
        <tr>
            <th valign="top">Append Posts:</th>
            <td valign="top">
            	<p>You can choose to have post titles appended to include the name of the news source, specified either as the name or the suffix in the "Feeds" config screen.</p>
                <label><input type="radio" name="dfenh_config-append" value="1" <?php if($nh->append) { echo('checked'); } ?> />Yes</label><br />
                <label><input type="radio" name="dfenh_config-append" value="0" <?php if(!$nh->append) { echo('checked'); } ?> />No</label>
            </td>
        </tr>
        <tr>
            <th valign="top">Post Dates:</th>
            <td valign="top">
            	<p>If you would prefer that articles posted to your site get dated with the original article's date, you can specify that here.  Otherwise, the post will be timestamped with the current time and date.  It may be of benefit to have featured articles post as the current time and date so they are the very latest posts on your site.  If so, the same timestamp option appears for features.</p>
				<table>
					<tr>
						<td>
							<strong>Use <em>original &lt;pubDate&gt;</em> for each post's date?</strong><br />
            			    <label><input type="radio" name="dfenh_config-use_pubdate" value="1" <?php if($nh->use_pubdate) { echo('checked'); } ?> />Yes</label><br />
			                <label><input type="radio" name="dfenh_config-use_pubdate" value="0" <?php if(!$nh->use_pubdate) { echo('checked'); } ?> />No</label>						
						
						</td>
						<td>
            			    <strong>Use <em>original &lt;pubDate&gt;</em> for featured posts?</strong><br />
							<label><input type="radio" name="dfenh_config-publish_features_pubdate" value="1" <?php if($nh->publish_features_pubdate) { echo('checked'); } ?> />Yes</label><br />
			                <label><input type="radio" name="dfenh_config-publish_features_pubdate" value="0" <?php if(!$nh->publish_features_pubdate) { echo('checked'); } ?> />No</label>						
						</td>
					</tr>
				</table>
            </td>
        </tr>
        <tr>
            <th valign="top">Publish Features:</th>
            <td valign="top">
            	<p>When publishing articles marked as "features," you can specify that you only want those posts saved as drafts or have them publish directly on save.</p>
                <select name="dfenh_config-publish_features" size="1">
                    <option value="publish" <?php if($nh->publish_features == 'publish') { echo('selected="selected"'); } ?> >Publish immediately</option>
                    <option value="draft" <?php if($nh->publish_features == 'draft') { echo('selected="selected"'); } ?> >Save as Draft</option>
                </select>
                <p><strong>Note about scheduling posts:</strong>  While its possible to schedule a featured post to publish at a later date, if you set this option to "Save to Draft," then this option will not work.</p>
            </td>
        </tr>
        <tr>
            <th valign="top">Omit Categories:</th>
            <td valign="top">
<?php  
	foreach($nh->categories as $cat) {
?>
									<span class="dfenh_inlines"><input name="dfenh_config-omit[]" type="checkbox" value="<?php echo($cat->cat_ID) ?>" <?php if(is_array($nh->omit) && in_array($cat->cat_ID, $nh->omit)) { echo('checked'); } ?>>&nbsp;<label for="dfenh_config-omit[]"><?php echo($cat->cat_name) ?></label></span>
<?php } ?>
            </td>
        </tr>
        <tr>
            <th valign="top">Save Options:</th>
            <td valign="top"><input name="dfenh_config-save" type="hidden" value="1" /><input name="dfenh_config-submit" type="submit" value="Save Options" /></td>
        </tr>
    </table>
</form>
    <?php
}



/*///////////////////////////////////////////////////////////////////////
//  Function: dfenh_feeds()                                            //
//  Purpose: Provides admin page for adding and configuring new feeds. //
///////////////////////////////////////////////////////////////////////*/
function dfenh_feeds() {
	global $wpdb;
	// Include our class file, populate with the correct options, and go
	include(dirname(__FILE__).'/news_harvester.class.php');
	$nh = new NewsHarvester();
		$nh->getOptions();
	
	if ( $_POST['dfenh_add_feed-submit'] ) {
		$nh->registerFeed($_POST);
	}
	
	if ( $_POST['dfenh_delete_feed-submit'] ) {
		$nh->deleteFeed($_POST);
	}
	
	if ( $_POST['dfenh_edit_feed-submit'] ) {
		$nh->updateFeed($_POST);
	}
  ?>
<div class="wrap">
	<h2>Currently Available Feeds (<a href="#add">+Add</a>):</h2>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" class="form-table">
			<tr>
				<td width="60" align="center">Delete</td>
				<td width="60" align="center">Edit</td>
				<td>Feed:</td>
				<td>Default Category:</td>
				<td>Suffix:</td>
			</tr>
<?php if(!$nh->feeds) { ?><tr><td colspan="5">No feeds configured yet.  Add new feeds below.</td></tr><?php } else {
		for($x=0; $x<count($nh->feeds); $x++) { 
			$cat = get_category($nh->feeds[$x]['cat'], ARRAY_A)?>
            <form action="" method="post" name="<?php echo($nh->feeds[$x]['feed_id']) ?>" id="<?php echo($nh->feeds[$x]['feed_id']) ?>">
			<tr id="<?php echo($nh->feeds[$x]['feed_id']) ?>">
				<td align="center"><input name="dfenh_delete_feed-submit" type="submit" value="Delete" class="deletefeed"><input type="hidden" name="feed" value="<?php echo($nh->feeds[$x]['feed_id']) ?>" /></td>
				<td align="center"><input name="edit_feed" type="button" value="Edit" class="editfeed"></td>
				<td id="<?php echo($nh->feeds[$x]['feed_id']) ?>_title">
                	<p class="static"><?php echo($nh->feeds[$x]['name']) ?></p>
                    <input type="text" name="dfenh_feed_name" class="edit" value="<?php echo($nh->feeds[$x]['name']) ?>" disabled="disabled" />
                </td>
				<td id="<?php echo($nh->feeds[$x]['feed_id']) ?>_category">
                	<p class="static"><?php echo($cat['cat_name']) ?></p>
                    <select name="dfenh_feed_cat" class="edit" size="1" disabled="disabled">
						<option value="none">Select</option>
<?php  
	foreach($nh->pub_cats as $cat) {
?>
						<option value="<?php echo($cat->cat_ID); ?>" <?php if($cat->cat_ID == $nh->feeds[$x]['cat']) { ?> selected <?php } ?>><?php echo($cat->cat_name); ?></option>
<?php } ?>
					</select>
                </td>
				<td id="<?php echo($nh->feeds[$x]['feed_id']) ?>_suffix">
                	<p class="static"><?php echo($nh->feeds[$x]['suffix']) ?></p>
                    <input type="text" name="dfenh_feed_suff" class="edit" value="<?php echo($nh->feeds[$x]['suffix']) ?>" disabled="disabled" />
                </td>
			</tr>
            <tr id="<?php echo($nh->feeds[$x]['feed_id']) ?>_url" style="display:none;">
            	<td colspan="4"><input type="text" name="dfenh_feed_url" value="<?php echo($nh->feeds[$x]['url']) ?>" size="60" /></td>
                <td><input type="submit" name="dfenh_edit_feed-submit" value="Save Changes" class="editfeedsubmit" /></td>
            </tr>
            </form>
<?php 	} /* end for */ }/* end else */ ?>
		</table>
	<h2>Add Feed:<a name="add">&nbsp;</a></h2>
	<form action="" method="post" name="dfenh_add_feed" id="dfenh_add_feed">
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" class="form-table">
			<tr>
				<td align="left" colspan="2"><p>Paste the URL of the feed you wish to harvest from here:</p><label for="dfenh_feed_url">Feed URL:</label><input name="dfenh_feed_url" type="text" size="40"></td>
			</tr>
			<tr>
				<td align="left" colspan="2"><p>This is the name of the feed as it will appear in your Feed Harvester list:</p><label for="dfenh_feed_name">Feed Name:</label><input name="dfenh_feed_name" type="text" size="40"></td>
			</tr>
			<tr>
				<td align="left"><p>If you choose to append each post with a suffix identifying the source, this is where you set the name that will appear.  Set this option in Config.  If no name is given here, the Feed Name will be used instead:</p><label for="dfenh_feed_suff">Feed suffix:</label><input name="dfenh_feed_suff" type="text" size="20"></td>
				<td align="left"><p>Every post Feed Harvester creates from this feed will be assigned this category.  If none is selected, your system default category will be used:</p><label for="dfenh_feed_cat">Feed Default Category:</label>
					<select name="dfenh_feed_cat" id="dfenh_feed_cat" size="1">
						<option value="none">Select</option>
<?php  
	foreach($nh->pub_cats as $cat) {
?>
						<option value="<?php echo($cat->cat_ID); ?>"><?php echo($cat->cat_name); ?></option>
<?php } ?>
					</select></td>
			</tr>
			<tr>
				<td colspan="2" align="right"><input type="hidden" name="dfenh_add_feed-submit" value="1"><input type="submit" name="dfenh_add_feed" value="Add Feed"></td>
			</tr>
		</table>
	</form>
</div>
  <?php
}


/*///////////////////////////////////////////////////////////////////////
//  Function: dfenh_title_to_tags_control()                            //
//  Purpose: Control for the tagging function                          //
///////////////////////////////////////////////////////////////////////*/
function dfenh_title_to_tags_control() {

	// Get our options and see if we're handling a form submission.
	$options = get_option('dfenh_title_to_tags');
	$stopwords = dirname(__FILE__).'/dfe_news_harvester/stopwords.txt';
	if ( !is_array($options) ){
		$defaults = file_get_contents($stopwords);
		$options = array('t2t_exceptions'=>$defaults);
	}
	if ( $_POST['hnt2t-submit'] ) {
		if ($_POST['hnt2t_reset'] == 1) {
			$options['t2t_exceptions'] = file_get_contents($stopwords);	
		}
		else { $options['t2t_exceptions'] = strip_tags(stripslashes($_POST['hnt2t_exceptions'])); }
		update_option('dfenh_title_to_tags', $options);
		?><div id='message' class='updated fade'><p><strong>Title to Tags exception list updated!</strong></p></div><?php
	}

	// Be sure you format your options to be valid HTML attributes.
	$exceptions = htmlspecialchars($options['t2t_exceptions'], ENT_QUOTES);
	
	// Begin form output
?>
<div class="wrap">
<h2>Title to Tags</h2>
	<form id="hn_title_to_tags" name="hn_title_to_tags" method="post" action="">
	<h3>Excluded Words</h3>
	<p>These words will be ignored by Title to Tags</p>
	<textarea rows="6" cols="100" name="hnt2t_exceptions" id="hnt2t_exceptions"><?php echo($exceptions); ?></textarea>
	<h3>Reset Excluded Words</h3>
	<p><strong>Warning!!</strong>  Setting this option will restore your ignore list to it's original state and delete any entries you may have made.  Please only use this option when you are <strong>sure</strong> you want to reset!</p>
	<input type="checkbox" name="hnt2t_reset" id="hnt2t_reset" value="1"><label for="hnt2t_reset">Reset all ignore words to defaults</label><br /><br />
	<input type="submit" name="submit" id="submit" value="submit" />
	<input type="hidden" id="hnt2t-submit" name="hnt2t-submit" value="1" />
	</form>
</div>
<?php

}
?>
