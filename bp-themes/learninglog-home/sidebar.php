<?php do_action( 'bp_before_sidebar' ) ?>

<div id="sidebar">
	<div class="padder">

	<?php do_action( 'bp_inside_before_sidebar' ) ?>

	<?php if ( is_user_logged_in() ) : ?>

		<?php do_action( 'bp_before_sidebar_me' ) ?>

		<div id="sidebar-me">
			<a href="<?php echo bp_loggedin_user_domain() ?>">
				<?php bp_loggedin_user_avatar( 'type=thumb&width=40&height=40' ) ?>
			</a>

			<h4><a href="<?php bp_loggedin_user_link()?>"><?php bp_loggedin_user_fullname() ?></a></h4>
			<a class="button logout" href="<?php echo wp_logout_url( bp_get_root_domain() ) ?>"><?php _e( 'Log Out', 'buddypress' ) ?></a>

			<?php do_action( 'bp_sidebar_me' ) ?>
		</div>

		<?php do_action( 'bp_after_sidebar_me' ) ?>

		<?php if ( function_exists( 'bp_message_get_notices' ) ) : ?>
			<?php bp_message_get_notices(); /* Site wide notices to all users */ ?>
		<?php endif; ?>

	<?php else : ?>

		<?php do_action( 'bp_before_sidebar_login_form' ) ?>


		<form name="login-form" id="sidebar-login-form" class="standard-form" action="<?php echo site_url( 'wp-login.php', 'login_post' ) ?>" method="post">
			<label><?php _e( 'Username', 'buddypress' ) ?><br />
			<input type="text" name="log" id="sidebar-user-login" class="input" value="<?php echo attribute_escape(stripslashes($user_login)); ?>" /></label>

			<label><?php _e( 'Password', 'buddypress' ) ?><br />
			<input type="password" name="pwd" id="sidebar-user-pass" class="input" value="" /></label>

			<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="sidebar-rememberme" value="forever" /> <?php _e( 'Remember Me', 'buddypress' ) ?></label></p>

			<?php do_action( 'bp_sidebar_login_form' ) ?>
			<input type="submit" name="wp-submit" id="sidebar-wp-submit" value="<?php _e('Log In'); ?>" tabindex="100" />
			<input type="hidden" name="testcookie" value="1" />
		</form>

		<?php do_action( 'bp_after_sidebar_login_form' ) ?>
		
		</div><!-- .padder -->
		
		<?php 
			$blog_details = get_blog_details(1); 
		?>
		
		<div id="register">
			<a href="http://<?php echo $blog_details->domain.$blog_details->path ?>register">
				<?php _e('Neu registrieren', 'bp_learning_diary')?>
			</a>
		</div>

	<?php endif; ?>

	<?php do_action( 'bp_inside_after_sidebar' ) ?>

</div><!-- #sidebar -->

<?php do_action( 'bp_after_sidebar' ) ?>
