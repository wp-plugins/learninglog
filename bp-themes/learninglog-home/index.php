<?php get_header() ?>

	<div id="content">
		
		<div class="padder home-left">
		<?php if ( !function_exists('dynamic_sidebar')
        || !dynamic_sidebar('SidebarLeft') ) : ?>
    	<?php endif; ?>
		</div>
		<div class="padder home-right">

		<?php do_action( 'bp_before_blog_home' ) ?>

		<div class="page home" id="blog-latest">

			<?php if ( have_posts() ) : ?>

				<?php while (have_posts()) : the_post(); ?>

					<?php do_action( 'bp_before_blog_post' ) ?>

					<div class="post" id="post-<?php the_ID(); ?>">

						<div class="post-content">
							<h2 class="posttitle"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'buddypress' ) ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>

							<p class="date"><?php the_time() ?></p>

							<div class="entry">
							
								<?php the_excerpt( __( 'Read the rest of this entry &rarr;', 'buddypress' ) ); ?>
								
								<?php //wp_trim_excerpt(); ?>
							</div>

						</div>

					</div>

					<?php do_action( 'bp_after_blog_post' ) ?>

				<?php endwhile; ?>

				<div class="navigation">

					<div class="alignleft"><?php next_posts_link( __( '&larr; Previous Entries', 'buddypress' ) ) ?></div>
					<div class="alignright"><?php previous_posts_link( __( 'Next Entries &rarr;', 'buddypress' ) ) ?></div>

				</div>

			<?php else : ?>

				<h2 class="center"><?php _e( 'Not Found', 'buddypress' ) ?></h2>
				<p class="center"><?php _e( 'Sorry, but you are looking for something that isn\'t here.', 'buddypress' ) ?></p>

				<?php locate_template( array( 'searchform.php' ), true ) ?>

			<?php endif; ?>
		</div>

		<?php do_action( 'bp_after_blog_home' ) ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

<?php get_footer();?>