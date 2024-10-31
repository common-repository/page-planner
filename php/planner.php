<?php

class planner {
	var $taxonomy_used = 'post_tag';
	
	var $num_columns = 2;
	
	var $max_num_columns;
	
	var $no_matching_posts = true;
	
	var $terms = array();
	
	const screen_width_percent = 98;
	
	const screen_id = 'dashboard_page_page-planner/planner';
	
	const usermeta_key_prefix = 'page_planner';
	
	const default_num_columns = 1;

    var $sections = array ('A', 'B', 'C', 'D');
    var $pages = 50;

    function __construct( $active = 1 ) {
	    add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_scripts' ) );
	    add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_styles' ) );		
		
		
	}

	function enqueue_admin_scripts() {
		global $current_screen;
		
		if ( $current_screen->id == self::screen_id ) {
			wp_enqueue_script('page_planner-date-lib', PAGE_PLANNER_URL . 'js/lib/date.js', false, PAGE_PLANNER_VERSION, true);
			wp_enqueue_script('page_planner-date_picker-lib', PAGE_PLANNER_URL . 'js/lib/jquery.datePicker.js', array( 'jquery' ), PAGE_PLANNER_VERSION, true);
			wp_enqueue_script('page_planner-date_picker', PAGE_PLANNER_URL . 'js/page_planner_date.js', array( 'page_planner-date_picker-lib', 'page_planner-date-lib' ), PAGE_PLANNER_VERSION, true);
			wp_enqueue_script('page_planner_budget', PAGE_PLANNER_URL . 'js/page_planner.js', array( 'page_planner-date_picker' ), PAGE_PLANNER_VERSION, true);
		}
	}
	
	function enqueue_admin_styles() {
		global $current_screen;
		
		wp_enqueue_style('page_planner-datepicker-styles', PAGE_PLANNER_URL . 'css/datepicker-page_planner.css', false, PAGE_PLANNER_VERSION, 'screen');
/*		if ( $current_screen->id == self::screen_id ) {		
			wp_enqueue_style('edit_flow-story_budget-styles', EDIT_FLOW_URL . 'css/ef_story_budget.css', false, EDIT_FLOW_VERSION, 'screen');
			wp_enqueue_style('edit_flow-story_budget-print-styles', EDIT_FLOW_URL . 'css/ef_story_budget_print.css', false, EDIT_FLOW_VERSION, 'print');
		}
*/
	}
	
	function setup_terms() {
	    foreach ($this->sections as $section) {
            for ($i = 1; $i <= $this->pages; $i++) {
                $term = get_term_by('name', $section.$i, 'post_tag');
                array_push($this->terms, $term);
            }
	    }
	    print "<pre>";
	   # print_r($this->terms);
	    print "</pre>";
	}

    function view() {
        $this->setup_terms();
        $user_filters = $this->update_user_filters();
		
?>
	<div class="wrap" id="page-planner-wrap">
	<div id="page-planner-title">
		<div class="icon32" id="icon-edit"></div>
		<h2>Page Planner</h2>
	</div>
	<?php $this->table_navigation(); ?>
		<div id="dashboard-widgets-wrap">
			<div id="dashboard-widgets" class="metabox-holder">
			
			<?php
				$this->print_column( $this->terms );
			?>
			</div>
		</div><!-- /dashboard-widgets -->
		<?php # $this->matching_posts_messages(); ?>
	</div><!-- /wrap -->
<?php
    }
    
    /**
	 * Print the table navigation and filter controls, using the current user's filters if any are set.
	 */
	function table_navigation() {
	    global $edit_flow;
	    if ($edit_flow) {
		    $custom_statuses = $edit_flow->custom_status->get_custom_statuses();
	    }
		$user_filters = $this->get_user_filters();
	?>
	<div class="tablenav" id="ef-story-budget-tablenav">
		<div class="alignleft actions">
			<form method="get" action="<?php echo admin_url() . PAGE_PLANNER_PAGE; ?>" style="float: left;">
				<input type="hidden" name="page" value="page-planner/planner"/>
				<select id="post_status" name="post_status"><!-- Status selectors -->
					<option value=""><?php _e( 'View all statuses', 'edit-flow' ); ?></option>
					<?php
						foreach ( $custom_statuses as $custom_status ) {
							echo "<option value='$custom_status->slug' " . selected($custom_status->slug, $user_filters['post_status']) . ">$custom_status->name</option>";
						}
						echo "<option value='future'" . selected('future', $user_filters['post_status']) . ">" . __( 'Scheduled', 'edit-flow' ) . "</option>";
						echo "<option value='unpublish'" . selected('unpublish', $user_filters['post_status']) . ">" . __( 'Unpublished', 'edit-flow' ) . "</option>";
						echo "<option value='publish'" . selected('publish', $user_filters['post_status']) . ">" . __( 'Published', 'edit-flow' ) . "</option>";
					?>
				</select>

				<?php
					// Borrowed from wp-admin/edit.php
					#if ( ef_taxonomy_exists('category') ) {
						$category_dropdown_args = array(
							'show_option_all' => __( 'View all categories', 'edit-flow' ),
							'hide_empty' => 0,
							'hierarchical' => 1,
							'show_count' => 0,
							'orderby' => 'name',
							'selected' => $user_filters['cat']
							);
						wp_dropdown_categories( $category_dropdown_args );
					#}
					
					// TODO: Consider getting rid of this dropdown? The Edit Posts page doesn't have it and only allows filtering by user by clicking on their name. Should we do the same here?
					$user_dropdown_args = array(
						'show_option_all' => __( 'View all users', 'edit-flow' ),
						'name'     => 'post_author',
						'selected' => $user_filters['post_author']
						);
					wp_dropdown_users( $user_dropdown_args );
				?>
					<label for="date">Date: </label>
    				<input id='date' name='date' type='text' class="date-pick" value="<?php echo $user_filters['date']; ?>" autocomplete="off" />
				<input type="submit" id="post-query-submit" value="<?php _e( 'Filter', 'edit-flow' ); ?>" class="button-primary button" />
			</form>
			<form method="get" action="<?php echo admin_url() . PAGE_PLANNER_PAGE; ?>" style="float: left;">
				<input type="hidden" name="page" value="page-planner/planner"/>
				<input type="hidden" name="post_status" value=""/>
				<input type="hidden" name="cat" value=""/>
				<input type="hidden" name="post_author" value=""/>
				<input type="hidden" name="date" value=""/>
				<input type="submit" id="post-query-clear" value="<?php _e( 'Reset', 'edit-flow' ); ?>" class="button-secondary button" />
			</form>
			<br/>
		</div><!-- /alignleft actions -->
		<div class="clear"></div>
        <h3 style="text-align: center;margin: 10px 0 5px 0;">
		<?php
		$date = $this->combine_get_with_user_filter( $user_filters, 'date' );
		if ( !empty( $date ) ) {
			// strtotime basically handles turning any date format we give to the function into a valid timestamp
			// so we don't really care what date string format is used on the page, as long as it makes sense
			$prev = date( 'M d Y', strtotime("-1 day",strtotime($date)));
			$next = date( 'M d Y', strtotime("+1 day",strtotime($date)));
			$now = date( 'M d Y', strtotime($date));
		} else {
		    $prev = date( 'M d Y', strtotime("-1 day"));
			$next = date( 'M d Y', strtotime("+1 day"));
		    $now = date( 'M d Y' );
		}
		?>
		<a style="text-decoration: none;" href="<?php echo admin_url() . PAGE_PLANNER_PAGE; ?>&date=<?php echo urlencode($prev); ?>">&lt;</a>
		<?php
		echo $now;
		?>
		<a style="text-decoration: none;" href="<?php echo admin_url() . PAGE_PLANNER_PAGE; ?>&date=<?php echo urlencode($next); ?>">&gt;</a>

        </h3>		
		
		<p class="print-box" style="float:right; margin-right: 30px;"><!-- Print link -->
			<a href="#" id="toggle_details"><?php _e( 'Toggle Post Details', 'edit-flow' ); ?></a> | <a href="#" id="print_link"><?php _e( 'Print', 'edit-flow' ); ?></a>
		</p>
		<div class="clear"></div>
	</div><!-- /tablenav -->
	<?php
	}
	
	function combine_get_with_user_filter( $user_filters, $param ) {
		if ( !isset( $user_filters[$param] ) ) {
			return $this->filter_get_param( $param );
		} else {
			return $user_filters[$param];
		}
	}
	
	/**
	 *
	 * @param string $param The parameter to look for in $_GET
	 * @return null if the parameter is not set in $_GET, empty string if the parameter is empty in $_GET,
	 *		   or a sanitized version of the parameter from $_GET if set and not empty
	 */
	function filter_get_param( $param ) {
		// Sure, this could be done in one line. But we're cooler than that: let's make it more readable!
		if ( !isset( $_GET[$param] ) ) {
			return null;
		} else if ( empty( $_GET[$param] ) ) {
			return '';
		}
		
		// TODO: is this the correct sanitization/secure enough?
		return htmlspecialchars( $_GET[$param] );
	}

	/**
	 * Get the filters for the current user for the story budget display, or insert the default
	 * filters if not already set.
	 * 
	 * @return array The filters for the current user, or the default filters if the current user has none.
	 */
	function get_user_filters() {
		$current_user = wp_get_current_user();
		$user_filters = array();
		if ( function_exists( 'get_user_meta' ) ) { // Let's try to avoid using the deprecated API
			$user_filters = get_user_meta( $current_user->ID, self::usermeta_key_prefix . 'filters', true );
		} else {
			$user_filters = get_usermeta( $current_user->ID, self::usermeta_key_prefix . 'filters' );
		}
		
		// If usermeta didn't have filters already, insert defaults into DB
		if ( empty( $user_filters ) ) {
			$user_filters = $this->update_user_filters();
		}
        if ($_GET['date'] == "") { $user_filters['date'] = ""; }

		return $user_filters;
	}	
		
	/**
	 * Update the current user's filters for story budget display with the filters in $_GET. The filters
	 * in $_GET take precedence over the current users filters if they exist.
	 */
	function update_user_filters() {
		$current_user = wp_get_current_user();
		
		$user_filters = array(
								'post_status' 	=> $this->filter_get_param( 'post_status' ),
								'cat' 			=> $this->filter_get_param( 'cat' ),
								'post_author' 	=> $this->filter_get_param( 'post_author' ),
								'date' 	=> $this->filter_get_param( 'date' ),
							  );
		
		$current_user_filters = array();
		if ( function_exists( 'get_user_meta' ) ) { // Let's try to avoid using the deprecated API
			$current_user_filters = get_user_meta( $current_user->ID, self::usermeta_key_prefix . 'filters', true );
		} else {
			$current_user_filters = get_usermeta( $current_user->ID, self::usermeta_key_prefix . 'filters' );
		}
		
		// If any of the $_GET vars are missing, then use the current user filter
		foreach ( $user_filters as $key => $value ) {
			if ( is_null( $value ) && !empty( $current_user_filters[$key] ) ) {
				$user_filters[$key] = $current_user_filters[$key];
			}
		}
		
		if ( function_exists( 'update_user_meta' ) ) { // Let's try to avoid using the deprecated API
			update_user_meta( $current_user->ID, self::usermeta_key_prefix . 'filters', $user_filters );
		} else {
			update_usermeta( $current_user->ID, self::usermeta_key_prefix . 'filters', $user_filters );
		}
		return $user_filters;
	}
	
	/**
	 * Prints a single column in the story budget.
	 *
	 * @param int $col_num The column which we're going to print.
	 * @param array $terms The terms to print in this column.
	 */
	// function print_column($col_num, $terms) {
	function print_column( $terms ) {
		// If printing fewer than get_num_columns() terms, only print that many columns
		$num_columns = $this->get_num_columns();
		?>
		<div class="postbox-container" style="width: 98%">
			<div class="meta-box-sortables">
			<?php
				// for ($i = $col_num; $i < count($terms); $i += $num_columns)
				for ($i = 0; $i < count($terms); $i++)
					$this->print_term( $terms[$i] );
			?>
			</div>
		</div>
		<?php
	}
	
	function get_num_columns() {
	    return 1;
    }
    
    /**
	 * Prints the stories in a single term in the story budget.
	 *
	 * @param object $term The term to print.
	 */
	function print_term( $term ) {
		global $wpdb;
#		print "Term: " . $term;
		$posts = $this->get_matching_posts_by_term_and_filters( $term );
		#print "<pre>";
		#print_r($posts);
		#print "</pre>";
		if ( !empty( $posts ) ) :
			// Don't display the message for $no_matching_posts
			$this->no_matching_posts = false;
		$words = 0;
		
		$options = get_option( COLUMN_INCHES_OPTION );
		$word_inches = $options['words_inch'];
	    
		foreach ($posts as $post) {
		    $words += sizeof(explode(" ", $post->post_content));
		}
		$inches = $this->words_to_inches($words, $word_inches);
	?>
	<div class="postbox">
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'edit-flow' ); ?>"><br /></div>
		<h3 class='hndle'><span><?php echo $term->name; ?>  - <font size="0.67em" color="#aaa"><?php echo $inches; ?> inches</font></span></h3>
		<div class="inside">
			<table class="widefat post fixed story-budget" cellspacing="0">
				<thead>
					<tr>
						<th scope="col" id="title" class="manage-column column-title" ><?php _e( 'Title', 'edit-flow' ); ?></th>
						<th scope="col" id="author" class="manage-column column-author"><?php _e( 'Author', 'edit-flow' ); ?></th>
						<!-- Intentionally using column-author below for CSS -->
						<th scope="col" id="status" class="manage-column column-author"><?php _e( 'Status', 'edit-flow' ); ?></th>
						<th scope="col" id="inches" class="manage-column column-author"><?php _e( 'Column Inches', 'edit-flow' ); ?></th>
						<th scope="col" id="updated" class="manage-column column-author" title="<?php _e( 'Last update time', 'edit-flow'); ?>"><?php _e( 'Updated', 'edit-flow' ); ?></th>
					</tr>
				</thead>

				<tfoot></tfoot>

				<tbody>
				<?php
					foreach ($posts as $post)
						$this->print_post($post, $term);
				?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
		endif;
	}
	
	/**
	 * Get posts by term and any matching filters
	 * TODO: Get this to actually work
	 */
	function get_matching_posts_by_term_and_filters( $term ) {
		global $wpdb, $edit_flow;
		
		$user_filters = $this->get_user_filters();
		
		// TODO: clean up this query, make it work with an eventual setup_postdata() call
		$query = "SELECT * FROM $wpdb->posts as p
					JOIN $wpdb->term_relationships tr
						ON p.ID = tr.object_id
					JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
					WHERE ";
		
		$post_where = '';		
		
		// Only show approved statuses if we aren't filtering (post_status isn't set or it's 0 or empty), otherwise filter to status
		$post_status = $this->combine_get_with_user_filter( $user_filters, 'post_status' );
		if ( !empty( $post_status ) ) {
			if ( $post_status == 'unpublish' ) {
				$post_where .= "(p.post_status IN (";
				if ($edit_flow) {
				    $custom_statuses = $edit_flow->custom_status->get_custom_statuses();
				} else {
				    $custom_statuses = array();
				}
				foreach( $custom_statuses as $status ) {
					$post_where .= $wpdb->prepare( "%s, ", $status->slug );
				}
				$post_where = rtrim( $post_where, ', ' );
				if ( apply_filters( 'ef_show_scheduled_as_unpublished', false ) ) {
					$post_where .= ", 'future'";
				}
				$post_where .= ')) ';
			} else {
				$post_where .= $wpdb->prepare( "p.post_status = %s ", $post_status );
			}
		} else {
			$post_where .= "(p.post_status IN ('publish', 'future'";
			
			if ($edit_flow) {
			    $custom_statuses = $edit_flow->custom_status->get_custom_statuses();
			} else {
			    $custom_statuses = array();
			}

			foreach( $custom_statuses as $status ) {
				$post_where .= $wpdb->prepare( ", %s", $status->slug );
			}
			$post_where .= ')) ';
		}
		
		// Filter by post_author if it's set
		$post_author = $this->combine_get_with_user_filter( $user_filters, 'post_author' );
		if ( !empty( $post_author ) ) {
			$post_where .= $wpdb->prepare( "AND p.post_author = %s ", (int) $post_author );
		}
		
		// Filter by start date if it's set
		$date = $this->combine_get_with_user_filter( $user_filters, 'date' );
		if ( !empty( $date ) ) {
			// strtotime basically handles turning any date format we give to the function into a valid timestamp
			// so we don't really care what date string format is used on the page, as long as it makes sense
			$mysql_time = date( 'Y-m-d', strtotime( $date ) );

			#$post_where .= $wpdb->prepare( "AND (p.post_date LIKE %s) ", $mysql_time );
			$post_where .= $wpdb->prepare("AND (pm.meta_value=%s AND pm.meta_key='e_section_date')", $mysql_time);
		} else {
		    $mysql_time = date( 'Y-m-d' );		    
			#$post_where .= $wpdb->prepare( "AND (p.post_date LIKE %s) ", $mysql_time );
			$post_where .= $wpdb->prepare("AND (pm.meta_value=%s AND pm.meta_key='e_section_date')", $mysql_time);
		}
	
		// Limit results to the given category where type is 'post'
		$post_where .= $wpdb->prepare( "AND tr.term_taxonomy_id = %d ", $term->term_taxonomy_id );
		$post_where .= "AND p.post_type = 'post' ";
		
		// Limit the number of results per category
		$default_query_limit_number = 10;
		$query_limit_number = apply_filters( 'ef_story_budget_query_limit', $default_query_limit_number );
		// Don't allow filtering the limit below 0
		if ( $query_limit_number < 0 ) {
			$query_limit_number = $default_query_limit_number;
		}
		$query_limit = $wpdb->prepare( 'LIMIT %d ', $query_limit_number );
		
		$query .= apply_filters( 'ef_story_budget_query_where', $post_where );
		$query .= apply_filters( 'ef_story_budget_order_by', 'ORDER BY p.post_modified DESC ' );
		$query .= $query_limit;
		$query .= ';';
		#print "<pre>". $query . "</pre><br/>";
		return $wpdb->get_results( $query );
	}
	
	/**
	 * Prints a single post in the story budget.
	 *
	 * @param object $post The post to print.
	 * @param object $parent_term The top-level term to which this post belongs.
	 */
	function print_post( $the_post, $parent_term ) {
		global $post, $edit_flow;
		$post = $the_post; // TODO: this isn't right - need to call setup_postdata($the_post). But that doesn't work. Why?
		$authordata = get_userdata($post->post_author); // get the author data so we can use the author's display name
		
		// Build filtering URLs for post_author and post_status
		$filter_url = admin_url() . EDIT_FLOW_STORY_BUDGET_PAGE;	
		$author_filter_url = $filter_url . '&post_author=' . $post->post_author;
		$status_filter_url = $filter_url . '&post_status=' . $post->post_status;
		// Add any existing $_GET parameters to filter links in printed post
		if ( isset($_GET['post_status']) && !empty( $_GET['post_status'] )  ) {
			$author_filter_url .= '&post_status=' . $_GET['post_status'];
		}
		if ( isset( $_GET['post_author'] ) && !empty( $_GET['post_author'] ) ) {
			$status_filter_url .= '&post_author=' . $_GET['post_author'];
		}
		if ( isset( $_GET['start_date'] ) && !empty( $_GET['start_date'] ) ) {
			$author_filter_url .= '&start_date=' . $_GET['start_date'];
			$status_filter_url .= '&start_date=' . $_GET['start_date'];
		}
		if ( isset( $_GET['end_date'] ) && !empty( $_GET['end_date'] ) ) {
			$author_filter_url .= '&end_date=' . $_GET['end_date'];
			$status_filter_url .= '&end_date=' . $_GET['end_date'];
		}
		
		$post_owner = ( get_current_user_id() == $post->post_author ? 'self' : 'other' );
		$edit_link = get_edit_post_link( $post->ID );
		$post_title = _draft_or_post_title();				
		$post_type_object = get_post_type_object( $post->post_type );
		$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $post->ID );
				
		// TODO: use these two lines before and after calling the_excerpt() once setup_postdata works correctly
		//add_filter( 'excerpt_length', array( &$this, 'story_budget_excerpt_length') );
		//remove_filter( 'excerpt_length', array( &$this, 'story_budget_excerpt_length') );
		
		// Get the friendly name for the status (e.g. Pending Review for pending)
		if ($edit_flow) {
		    $status = $post->post_status; #$edit_flow->custom_status->get_custom_status_friendly_name( $post->post_status );
	    } else {
	        $status = $post->post_status;
	    }
		?>
			<tr id='post-<?php echo $post->ID; ?>' class='alternate author-self status-publish iedit' valign="top">
				<td class="post-title column-title">
					<?php if ( $can_edit_post ): ?>
						<strong><a class="row-title" href="<?php echo $edit_link; ?>" title="<?php sprintf( __( 'Edit &#8220;%s&#8221', 'edit-flow' ), $post->post_title ); ?>"><?php echo $post_title; ?></a></strong>
					<?php else: ?>
						<strong><?php echo $post_title; ?></strong>
					<?php endif; ?>
					<p><?php echo strip_tags( substr( $post->post_content, 0, 5 * $this->story_budget_excerpt_length(0) ) ); // TODO: just call the_excerpt once setup_postadata works ?></p>
					<p><?php do_action('story_budget_post_details'); ?></p>
					<div class="row-actions">
						<?php if ( $can_edit_post ) : ?>
							<span class='edit'><a title='<?php _e( 'Edit this item', 'edit-flow' ); ?>' href="<?php echo $edit_link; ?>"><?php _e( 'Edit', 'edit-flow' ); ?></a> | </span>
						<?php endif; ?>
						<?php if ( EMPTY_TRASH_DAYS > 0 && current_user_can( $post_type_object->cap->delete_post, $post->ID ) ) : ?>
						<span class='trash'><a class='submitdelete' title='<?php _e( 'Move this item to the Trash', 'edit-flow' ); ?>' href='<?php echo get_delete_post_link( $post->ID ); ?>'><?php _e( 'Trash', 'edit-flow' ); ?></a> | </span>
						<?php endif; ?>
						<span class='view'><a href="<?php echo get_permalink( $post->ID ); ?>" title="<?php echo esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'edit-flow' ), $post_title ) ); ?>" rel="permalink"><?php _e( 'View', 'edit-flow' ); ?></a></span></div>
				</td>
				<td class="author column-author"><a href="<?php echo $author_filter_url; ?>"><?php echo $authordata->display_name; ?></a></td>
				<td class="status column-status"><a href="<?php echo $status_filter_url; ?>"><?php echo $status ?></a></td>
				<?php
				$options = get_option( COLUMN_INCHES_OPTION );
    		    $word_inches = $options['words_inch'];
    		    $words = sizeof(explode(" ", $post->post_content));
    		    ?>
                <td class="inches column-inches"><?php echo $this->words_to_inches($words, $word_inches); ?> inches</td>
				<td class="last-updated column-updated"><abbr class="ef-timeago" title="<?php echo printf( __( 'Last updated at %s', 'edit-flow' ), date( 'c', get_the_modified_date( 'U' ) ) ); ?>"><?php echo $this->timesince(get_the_modified_date('U')); ?><?php //$this->print_subcategories( $post->ID, $parent_term ); ?></abbr></td>
			</tr>
		<?php
	}

	function story_budget_excerpt_length( $default_length ) {
		return 60 / $this->get_num_columns();
	}	

    // Lifted fromhttp://stackoverflow.com/questions/11/how-do-i-calculate-relative-time/18393#18393
    // We can probably do better and customize further
    function timesince( $original ) {
    	// array of time period chunks
    	$chunks = array(
    		array(60 * 60 * 24 * 365 , 'year'),
    		array(60 * 60 * 24 * 30 , 'month'),
    		array(60 * 60 * 24 * 7, 'week'),
    		array(60 * 60 * 24 , 'day'),
    		array(60 * 60 , 'hour'),
    		array(60 , 'minute'),
    		array(1 , 'second'),
    	);

    	$today = time(); /* Current unix time  */
    	$since = $today - $original;

    	if ( $since > $chunks[2][0] ) {
    		$print = date("M jS", $original);

    		if( $since > $chunks[0][0] ) { // Seconds in a year
    				$print .= ", " . date( "Y", $original );
    		}

    		return $print;
    	}

    	// $j saves performing the count function each time around the loop
    	for ($i = 0, $j = count($chunks); $i < $j; $i++) {

    		$seconds = $chunks[$i][0];
    		$name = $chunks[$i][1];

    		// finding the biggest chunk (if the chunk fits, break)
    		if (($count = floor($since / $seconds)) != 0) {
    			break;
    		}
    	}

    	return sprintf( _n( "1 $name ago", "$count ${name}s ago", $count), $count);
    }	
    
    function words_to_inches($words, $words_inches) {
        $rv = "";
        $num_counts = count($words_inches);

    	// Display column inches
    	for ($i = 0; $i < $num_counts; $i++) {
    		$column_inch = $words_inches[$i];
    		$name = $column_inch['name'];
    		$inches = ceil( $words / $column_inch['count'] );
    		$rv .= "<span title='$name: $inches column inch" . ($inches != 1 ? "es" : "") . "' style='border-bottom: 1px dotted #666; cursor: help;'>$inches</span>";
    		if ($num_counts  > 1 && $i < $num_counts - 1)
    			$rv .= ' / ';
    	}
    	return $rv;
    }
}
