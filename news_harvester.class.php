<?php
/*//////////////////////////////////////////////////////////////////
//  News Harvester Class                                          //
//  Creates the class from which all News Harvester functions     //
//  spawn.                                                        //
//                                                                //
//  Version 0.5                                                   //
//////////////////////////////////////////////////////////////////*/

class NewsHarvester {
	public $categories, $pub_cats, $number, $update, $separator, $publish_features, $use_pubdate, $publish_features_pubdate, $feeds, $featured_category, $stopwords, $selectfeed;
	
	/*///////////////////////////////////////////////////////////////////////
	//  Function: getOptions()                                             //
	//  Purpose: Sets up our options per the database.                     //
	///////////////////////////////////////////////////////////////////////*/
	public function getOptions() {
		$options = get_option('dfenh_config');
			$this->omit = $options['omit'];
			$this->categories = get_categories('orderby=name&order=ASC&hide_empty=0');
			$this->pub_cats = array();
			foreach($this->categories as $thisone) {
			     if(!$this->omit || !in_array($thisone->cat_ID, $this->omit)) { ($this->pub_cats[] = $thisone); }
			}
			$options['number'] ? ( $this->number = $options['number'] ) : ( $this->number = 5 );
			$options['update'] ? ( $this->update = $options['update'] ) : ( $this->update = 'on-publish' );
			$this->append = $options['append'];
			$options['separator'] ? ( $this->separator = $options['separator'] ) : ( $this->separator = ' || ' );
			$options['publish_features'] ? ( $this->publish_features = $options['publish_features'] ) : ( $this->publish_features = '' );
			$this->use_pubdate = $options['use_pubdate'];
			$this->publish_features_pubdate = $options['publish_features_pubdate'];
		function_exists('get_site_option') ? ($checkfeeds = get_site_option('dfenh_current_feeds')) : ($checkfeeds = get_option('dfenh_current_feeds'));
		$this->feeds = $this->checkFeed($checkfeeds);
		$this->featured_category = $options['featured_category'];
     }

	/*///////////////////////////////////////////////////////////////////////
	//  Function: displayFeed()                                            //
	//  Purpose: Shows articles from feed based on user params             //
	//  Params: string $selected, string $showall                          //
	///////////////////////////////////////////////////////////////////////*/
	public function displayFeed($selected, $showall = false) {
		foreach($this->feeds as $afeed) {
			if ($selected == $afeed['feed_id']) {
				$this->selectfeed = $afeed;
				break;
			}
		}
		$this->default_cat = $this->selectfeed['cat'];
		// Let's go get our news feed and display the results:
		$rss = fetch_rss($this->selectfeed['url']);
		if(is_object($rss)) {
			$items = array_slice($rss->items, 0, $this->number);
		} else {
			$items[]['title'] = 'Cannot obtain feed from '.$this->selectfeed['url'];
		}
		return($items);
	}
	
	
	
	/*///////////////////////////////////////////////////////////////////////
	//  Function: registerFeed($data)                                      //
	//  Purpose: Add a feed to the registered feeds list                   //
	//  Params: array $data, includes all relevant feed data.              //
	///////////////////////////////////////////////////////////////////////*/
	public function registerFeed($data) {
		$thisfeed['url'] = strip_tags(stripslashes($data['dfenh_feed_url']));
		$thisfeed['name'] = strip_tags(stripslashes($data['dfenh_feed_name']));
		$thisfeed['suffix'] = strip_tags(stripslashes($data['dfenh_feed_suff']));
		if ($data['dfenh_feed_cat'] != 'none') { $thisfeed['cat'] = strip_tags(stripslashes($data['dfenh_feed_cat'])); }
		if($thisfeed['url'] && $thisfeed['name'] && $thisfeed['suffix'] && $thisfeed['cat']) {
			$thisfeed['feed_id'] = rand();
			$this->feeds[] = $thisfeed;
			if(function_exists('update_site_option')) {
				update_site_option('dfenh_current_feeds', $this->feeds);
			} else {
				update_option('dfenh_current_feeds', $this->feeds);
			}
			?><div id="message" class="updated fade">Feed registered!</div><?php
		} else {
			?><div id="message" class="updated fade">Missing information, feed not created.</div><?php
		}
	}
	
	
	
	/*///////////////////////////////////////////////////////////////////////
	//  Function: deleteFeed($data)                                        //
	//  Purpose: Delete a feed from the registered feeds list              //
	//  Params: array $data, includes all relevant feed data.              //
	///////////////////////////////////////////////////////////////////////*/
	public function deleteFeed($data) {
		for($y = 0; $y < count($this->feeds); $y++) {
			if ($this->feeds[$y]['feed_id'] == $data['feed']) {
				continue;
			} else {
				$newfeeds[] = $this->feeds[$y];
			}
		}
		$this->feeds = $newfeeds;
		if(function_exists('update_site_option')) {
			update_site_option('dfenh_current_feeds', $newfeeds);
		} else {
			update_option('dfenh_current_feeds', $newfeeds);
		}
		?><div id="message" class="updated fade">Feed deleted!</div><?php
	}
	
	
	
	
	/*///////////////////////////////////////////////////////////////////////
	//  Function: updateFeed($data)                                        //
	//  Purpose: Update feed based on user input                           //
	//  Params: array $data, includes all relevant feed data from the form //
	//			found on the "Feeds" page.                                 //
	///////////////////////////////////////////////////////////////////////*/
	public function updateFeed($data) {
		$feed = $data['feed'];
		for($y = 0; $y < count($this->feeds); $y++) {
			if($this->feeds[$y]['feed_id'] == $feed) {
				$thisfeed['url'] = strip_tags(stripslashes($data['dfenh_feed_url']));
				$thisfeed['name'] = strip_tags(stripslashes($data['dfenh_feed_name']));
				$thisfeed['suffix'] = strip_tags(stripslashes($data['dfenh_feed_suff']));
				if ($data['dfenh_feed_cat'] != 'none') { $thisfeed['cat'] = strip_tags(stripslashes($data['dfenh_feed_cat'])); }
				if($thisfeed['url'] && $thisfeed['name'] && $thisfeed['suffix'] && $thisfeed['cat']) {
					$this->feeds[$y] = $thisfeed;
					if(function_exists('update_site_option')) {
						update_site_option('dfenh_current_feeds', $this->feeds);
					} else {
						update_option('dfenh_current_feeds', $this->feeds);
					}
					?><div id="message" class="updated fade">"<?php echo $data['dfenh_feed_name']; ?>" feed edited</div><?php
				} else {
					?><div id="message" class="updated fade">"<strong>Missing information!</strong> <?php echo $data['dfenh_feed_name']; ?>" feed not edited.</div><?php
				}
			}
		}
	}
	

	/*///////////////////////////////////////////////////////////////////////
	//  Function: checkFeed($data)                                         //
	//  Purpose: Organize feeds, check for feeds with no ids               //
	//  Params: array $feeds, includes all relevant feed data.              //
	///////////////////////////////////////////////////////////////////////*/
	function checkFeed($feeds) {
		if(is_array($feeds)) {
			foreach ($feeds as $feed) {
				$feed['feed_id'] ? $feed['feed_id'] : ($feed['feed_id'] = rand());
				$new[] = $feed;
			}
			return($new);
		}
	}
	
	
	/*///////////////////////////////////////////////////////////////////////
	//  Function: createPosts()                                            //
	//  Purpose: Creates new WP posts based on submission form.            //
	//  Params: $data should be populated with the entire $_POST of the    //
	//  		Harvester form.                                            //
	///////////////////////////////////////////////////////////////////////*/
	public function createPosts($data, $user_ID) {
		// Setup common variables
		global $wpdb;
		$source_id = $data['dfenh-source'];  // $this->feeds ID corresponding to the selected source feed
		foreach($this->feeds as $afeed) {
			if ($source_id == $afeed['feed_id']) {
				$source = $afeed;
				break;
			}
		}
		if ($this->append) {  // Are we appending the titles?  If not, we can skip this step
			$suffix = $source['suffix'];  // Suffix set when registering the feed, to be appended to the end of the title
			$separator = $this->separator;  // Separator set in Config which separates the title from the suffix
		}
		$created = 0; // Increment for counting the number of articles successfully created
		
		// 
		// Loop through submitted articles
		// 
		foreach($data['dfenh_select-articles'] as $article) {
			// A couple variables we'll need to use a couple timess: $title, $title2
			$title = ( $data[$article.'_dfenh_article-title'] ? strip_tags(stripslashes($data[$article.'_dfenh_article-title'])) : strip_tags(stripslashes($data[$article.'_dfenh_article-orgtit'])) );
			$title2 = ( $data[$article.'_dfenh_article-title2'] ? strip_tags(stripslashes($data[$article.'_dfenh_article-title2'])) : $title2 = $title );
			
			// WP Post attributes:
			$thispost = array();
			// post_title ~ if we're setup to append post titles, do it now.
			$this->append ? ( $thispost['post_title'] = $title.$separator.$suffix ) : ( $thispost['post_title'] = $title );
			// post_content ~ if there is content to be placed, do so.  If not, use a generic statement
			$data[$article.'_dfenh_article-content'] ? ( $thispost['post_content'] = $data[$article.'_dfenh_article-content'] ) : ( $thispost['post_content'] = 'Added via DFE News Harvester.' );
			// post_author
			$thispost['post_author'] = $user_ID;
			// post_category ~ loop through categories and add.  Also, add featured category if this is a featured item
			if ($data[$article.'_dfenh_article-cats']) {
				foreach ($data[$article.'_dfenh_article-cats'] as $nextcat) {
					$thispost['post_category'][] = $nextcat;
				}
			} else {
				$thispost['post_category'][] = $source['cat'];
			}
			$data[$article.'_dfenh_article-feature'] ? $thispost['post_category'][] = $this->featured_category : $thispost['post_category'] = $thispost['post_category'];
			$thispost['post_date'] = $this->getDate($article, $data);
			// $thispost['post_date_gmt'] = $this->getDate($article, 'gmt');
			$thispost['post_status'] = $this->getStatus($article, $data);
			
			// DFENH meta values:
			$this->append ? ( $dfenh_meta['link_text'] = $title2.$separator.$suffix ) : ( $dfenh_meta['link_text'] = $title2 );
			$dfenh_meta['link_url'] = $data[$article.'_dfenh_article-link'];
			$dfenh_meta['art_image'] = $data[$article.'_dfenh_article-image'];
			$dfenh_meta['image_credit'] = $data[$article.'_dfenh_article-imgcred'];
			$dfenh_meta['image_cred_url'] = $data[$article.'_dfenh_article-imgcred-url'];
			$dfenh_meta['subtitle'] = $data[$article.'_dfenh_article-summary'];
			
			// Now that we have our variables in place, time to post the article to the dB.
			// Check to see if posting works, and if so, proceed to setting meta values
			if ($newpost = wp_insert_post($thispost)) {
				foreach($dfenh_meta as $key=>$value) {
					add_post_meta($newpost, $key, $value, true);
				}
			} else {
				?><div id="message" class="updated fade">There was an error creating the posts!</div><?php
			}
			// Add tags
			$this->titleToTags($newpost, $title);
			$this->titleToTags($newpost, $title2);
			$created++;
		}
		// All done!  Report back how many articles have been created
		?><div id="message" class="updated fade"><?php echo($created); ?> articles created!</div><?php
	}
	
	
	
	/*///////////////////////////////////////////////////////////////////////
	//  Function: getStatus()                                              //
	//  Purpose: Determines the proper status for a given post             //
	//  Params: $data should be populated with the current article info    //
	///////////////////////////////////////////////////////////////////////*/
	function getStatus($article, $data) {
		if($data[$article.'_dfenh_article-feature']) {
			$status = ($this->publish_features == 'publish' ? 'publish' : 'draft');
		} else {
			$status = 'publish';
		}
		return($status);
	}
	


	/*///////////////////////////////////////////////////////////////////////
	//  Function: getDate()                                                //
	//  Purpose: Determines the proper date for a given post               //
	//  Params: $data should be populated with the current article info    //
	//  		$gmt returns date in GMT                                   //
	///////////////////////////////////////////////////////////////////////*/
	function getDate($article, $data, $gmt = '') {
		if($data[$article.'_dfenh_article-feature']) {
			$this->publish_features_pubdate ? ($date = date('Y-m-d H:i:s', ($data[$article.'_dfenh_article-pubdate'] + $data[$article.'_dfenh_article-postdate']))) : ($date = (date('Y-m-d H:i:s', time() + $data[$article.'_dfenh_article-postdate'])));
		} else {
			$this->use_pubdate ? ($date = date('Y-m-d H:i:s', $data[$article.'_dfenh_article-pubdate'])) : ($date = date('Y-m-d H:i:s', time()));
		}
		// echo("<strong>Publish_Features_Pubdate:</strong> ".$this->publish_features_pubdate."<br><strong>Use_Pubdate:</strong>".$this->use_pubdate);
		return($date);
	}
	
	
	
	/*///////////////////////////////////////////////////////////////////////
	//  Function: getPubdate()                                             //
	//  Purpose: Very simple.  Just return the correct publish element     //
	//  		in the correct format.                                     //
	//	Params: array $article should refer to an article array            //
	//  		string $format is either 'nice' for standard time format   //
	//				or 'unix' for a UNIX timestamp format                  //
	///////////////////////////////////////////////////////////////////////*/
	public function getPubdate($article, $format = 'nice') {
		if ($article['pubdate']) {
			$the_date = strtotime($article['pubdate']);
		} elseif($article['published']) {
			$the_date = strtotime($article['published']);
		} elseif($article['issued']) {
			$the_date = strtotime($article['issued']);
		} 
		// if using this function for other uses, pass $data['date']
		else {
			$the_date = strtotime($article['date']);
		}
		if($format == 'nice') { $the_date = date('Y-m-d H:i:s', $the_date); }
		return($the_date);
	}
	
	
	
	/*///////////////////////////////////////////////////////////////////////
	//  Function: titleToTags()                                            //
	//  Purpose: Cleans title words and creates tags from them             //
	///////////////////////////////////////////////////////////////////////*/
	public function titleToTags($post_id, $title) {
		$t2toption = get_option('dfenh_titleToTags');
		$stopwords = $t2toption['t2t_exceptions'];
		if ( !$stopwords ) {
			$file = dirname(__FILE__).'/stopwords.txt';
			$defaults = file_get_contents($file);
			$stopwords = $defaults;
		}
		$verboten = explode(',', $stopwords);
		$title_werdz = explode(' ', $title);
		for($x = 0; $x < count($verboten); $x++) {
			$verboten[$x] = $this->lowerNoPunc($verboten[$x]);
		}
		foreach ($title_werdz as $werd) {
			$werd = $this->lowerNoPunc($werd); //trim(preg_replace('#[^\p{L}\p{N}]+#u', '', $werd));
			if(!in_array($werd, $verboten)) {
				$tags[] = $werd;
			}
		}
		wp_add_post_tags($post_id, $tags);
	}
	
	
	
	/*///////////////////////////////////////////////////////////////////////
	//  Function: lowerNoPunc()                                            //
	//  Purpose: Very simple.  Just to compare all words as lower case.    //
	//       Also, to convert posessives to standard nouns                 //
	///////////////////////////////////////////////////////////////////////*/
	public function lowerNoPunc($werd) {
		if(stristr($werd, "'s")) {
			$sploded = explode("'", $werd);
			$werd = $sploded[0];
		}
		$werd = strtolower(trim(preg_replace('#[^\p{L}\p{N}]+#u', '', $werd)));
		return $werd;
	}


	/*///////////////////////////////////////////////////////////////////////
	//  Function: dfenh_get_shortcut_link()                                //
	//  Purpose: Modified version of the 'Press This' javascript           //
	//  	function which provides the 'Press This' popup window          //
	///////////////////////////////////////////////////////////////////////*/	
	public function dfenh_get_shortcut_link() {
		$link = "javascript:
			  var d=document,
			  w=window,
			  e=w.getSelection,
			  k=d.getSelection,
			  x=d.selection,
			  s=(e?e():(k)?k():(x?x.createRange().text:0)),
			  f='" . admin_url('harvest-this.php') . "',
			  l=d.location,
			  e=encodeURIComponent,
			  g=f+'?u='+e(l.href)+'&t='+e(d.title)+'&s='+e(s)+'&v=2';
			  function a(){
				  if(!w.open(g,'t','toolbar=0,resizable=0,scrollbars=1,status=1,width=720,height=570')){
					  l.href=g;
				  }
			  }";
			  if (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== false)
				  $link .= 'setTimeout(a,0);';
			  else
				  $link .= 'a();';
		
			  $link .= "void(0);";
		
		$link = str_replace(array("\r", "\n", "\t"),  '', $link);
	
	return apply_filters('shortcut_link', $link);
	}
}
?>