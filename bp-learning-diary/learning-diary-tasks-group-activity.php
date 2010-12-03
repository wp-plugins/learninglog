<?php

class LearningDiaryTasksGroupActivity
{
	protected $user_ID;
	protected $group_id;
	protected $feed = false;
	
	function __construct($get_feed_url=false) {
		if($get_feed_url) {
			$this->feed = true;
			$this->user_ID = $get_feed_url['user_id'] ? (int)$get_feed_url['user_id'] : 0;
			$this->group_id = (int)$get_feed_url['group_id'];
		}
	}
	
	protected function set_feed_url()
	{
		global $user_ID;
		
		$userdata = get_userdata($user_ID);
		
		$key = $user_ID != 0 ? "&key=" . $userdata->user_pass : "";
	 	
		return  WP_PLUGIN_URL
					. "/bp-learning-diary"
					. "/feed.php"
					. "?group=" . bp_get_group_id() 
					. "&user=" . $user_ID
					. $key;
		
	}
	
	public function allowed($key)
	{
		$userdata = get_userdata($this->user_ID);
		
		if($userdata->user_pass == $key)
			return true;
		else
			return false;
		
	}
	
	public function show_user_id()
	{
		return $this->user_ID;
	}
	
	/*
	 * get group activity
	 */

	public function get_group_activity()
	{
		global $wpdb;
		global $user_ID;
		
		//get current user
		$user_ID = !$this->user_ID ? $user_ID : $this->user_ID;
	
		//arrays to collect the posts
		$all_blogs_ids = array();
		$all_posts_ids = array();
		$all_posts_dates = array(); //for sorting

		//get current group
		$group_id = !$this->group_id ? bp_get_group_id() : $this->group_id;

		$group_members = BP_Groups_Member::get_all_for_group($group_id, false, false, false, true);	

		foreach($group_members['members'] as $member) {
			$user_id = $member->user_id;
		
			if ($_POST['filter'] == 'myposts' AND $user_id != $user_ID) continue;
		
			//get member's primary (and only) blog
			$blog = get_active_blog_for_user($user_id);
		
			switch_to_blog($blog->blog_id);
		
			//new object to control access to posts
			$PostAC = new BP_Post_Access_Control();
		
			//get all post ids that are maped to our group
			$posts_for_group = $PostAC->get_posts_maped_to_group($group_id);
		
			//fill the arrays for sorting later
			foreach($posts_for_group as $post_id) {
				$post = get_post($post_id);

				if( $PostAC->get_user_post_setting($user_ID, $post_id) == "allow" ) { //double security?
					$all_blogs_ids[] = $blog->blog_id;
					$all_posts_ids[] = $post_id;
					$all_posts_dates[] = $post->post_date;
				}	
			}//end foreach post
		}//end foreach member
		
		array_multisort($all_posts_dates, SORT_DESC, $all_blogs_ids, $all_posts_ids);

		return array('posts_dates' => $all_posts_dates, 'blogs_ids' => $all_blogs_ids, 'posts_ids' => $all_posts_ids);
	
	}//end function

	/*
	 * show group activity on group's home screen
	 */
	
	public function show_group_activity()
	{
		global $bp_deactivated;
		
		//simulate bp-activitiy deactivation to prevent bp-activitiy messages if this component is activated
		$bp_deactivated['bp-activity.php'] = 1;
		//$bp_components = get_site_option('bp-deactivated-components');

		//check if we are (not) on the home screen (labeled 'Start') of the group
		if( !bp_group_is_visible() || !bp_is_group_home() ) return;

		$activity = $this->get_group_activity();

		$all_posts_dates = $activity['posts_dates'];
		$all_blogs_ids = $activity['blogs_ids'];
		$all_posts_ids = $activity['posts_ids'];
	
		?>
		<div class="item-list-tabs no-ajax" id="subnav">
		<ul>
<!-- rss-feed 	
			<li class="feed">
				<a href="<?php //echo $this->set_feed_url(); ?>" title="RSS Feed">RSS-Feed</a>
			</li>
-->			
			<li class="last">
				<form name="form_filter" id="form_filter" method="post">
				<select name="filter" onChange="submit()">
					<option value="nofilter" ><?php _e('Kein Filter', 'bp_learning_diary')?></option>
					<option value="myposts" <?php echo $_POST['filter']=='myposts' ? "selected='selected'" :"";?>>
						<?php _e('Meine Beitr&auml;ge', 'bp_learning_diary')?>
					</option>
				</select>
				</form>
			</li>
			
		</ul>
		</div>
		<?php
	
		//if there are no posts
		if(!count($all_posts_ids)) {
			if ($_POST['filter'] == 'myposts')
				echo "<p>" . __('Du hast noch keine Beitr&auml;ge f&uuml;r diese Gruppe geschrieben.', 'bp_learning_diary') . "</p>";
			else 
				echo "<p>" . __('Diese Gruppe hat noch keine (f&uuml;r dich sichtbare) Beitr&auml;ge.', 'bp_learning_diary') . "</p>";
		}
		?>
		
		<div> <!-- div 1-->
		<div> <!-- div 2-->
		<div> <!-- div 3 -->
		
		<?php
	
		$nextp = false;
		$prevp = false;
		$ppp = 10; //posts per page
	
		foreach($all_posts_ids as $key => $post_id){
		
			/*pagination*/
			$page = $_GET['page'] ? $_GET['page'] : 1;
		
			if($key >= $page*$ppp-1 && $key < count($all_posts_ids)-1) {
				$nextp = true;
				//break;
			}
		
			if($key < ($page-1)*$ppp) {
				$prevp = true;
				continue;
			}
			/*end pagination*/
		
			switch_to_blog($all_blogs_ids[$key]);

			$post = get_post($post_id);

			query_posts('p=' . $post_id); //query for have_posts()

			?>
			<?php if ( have_posts() ) : ?>

				<?php while (have_posts()) : the_post(); ?>

					<?php do_action( 'bp_before_blog_post' ) ?>

					<div class="post"  id="post-<?php the_ID(); ?>">

						<div class="author-box">
							<?php echo get_avatar( get_the_author_meta( 'user_email' ), '50' ); ?>
							<p><?php printf( __( 'by %s', 'buddypress' ), bp_core_get_userlink( $post->post_author ) ) ?></p>
						</div>
					
						<div class="post-content" style="margin-left:105px">
							<h2 class="posttitle">
								<a 	href="<?php the_permalink() ?>" 
									rel="bookmark" 
									title="<?php _e( 'Permanent Link to', 'buddypress' ) ?> <?php the_title_attribute(); ?>">
									<?php the_title(); ?>
								</a>
							</h2>
						
							<p class="date"><?php the_time() ?> 
								<em><?php _e( 'in', 'buddypress' ) ?> 
									<?php //the_category(', ') ?>
									<?php echo str_replace( 'blog/', '', get_the_category_list(', ') )?>
									<?php printf( __( 'by %s', 'buddypress' ), bp_core_get_userlink( $post->post_author ) ) ?>
								</em>
							</p>
						
							<div class="entry">
								<?php the_content( __( 'Read the rest of this entry &rarr;', 'buddypress' ) ); ?>
								<?php do_action('ld_display_answer_format') //to hook in to display custom answer format?>
							</div>

							<p class="postmetadata">
								<span class="tags"><?php 
									//the_tags( __( 'Tags: ', 'buddypress' ), ', ', '<br />'); 
									echo str_replace( 'blog/', '', get_the_tag_list( __( 'Tags: ', 'buddypress' ), ', ', '<br />') ); 
								?></span>
								<span class="comments">
									<?php comments_popup_link( 	__( 'No Comments &#187;', 'buddypress' ),
									 							__( '1 Comment &#187;', 'buddypress' ), 
																__( '% Comments &#187;', 'buddypress' ) 
											); 
									?>
								</span>
							</p>
						</div> <!-- .post-content -->

						<?php do_action( 'bp_after_blog_post' ) ?>
				
					</div> <!-- .post -->
				
				<?php endwhile; ?>

				<div class="navigation">
					<?php if($nextp):?>
						<div class="alignleft"><a href="?page=<?php echo $page+1 ?>"><?php _e( '&larr; Previous Entries', 'buddypress' ) ?></a></div>
					<?php endif;?>
					<?php if( ($prevp && $nextp && $page > 1) || ($key == count($all_posts_ids)-1 && $page > 1) ):?>
						<div class="alignright"><a href="?page=<?php echo $page-1 ?>"><?php _e( 'Next Entries &rarr;', 'buddypress' ) ?></a></div>
					<?php endif;?>
				</div> <!-- .navigation -->
			<?php endif; //have_posts()?> 

			<?php if($nextp) break;	
		}//end foreach post
	
		wp_reset_query();
	
		restore_current_blog();
	
		?>
		</div><!-- div 3 -->
		</div><!-- div 2 -->
		</div><!-- div 1-->
		<?php
	
	}//end func
	
	public function reorder_members() {
		global $members_template;
		
		//fill sort helper array with display names
		foreach ($members_template->members as $member) {
			$display_names[] = $member->display_name;
		}
		//sort members
		array_multisort($members_template->members, $display_names);
	}//end func
	
}//end class
