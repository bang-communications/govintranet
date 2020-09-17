<?php
/* Template name: Staff directory flexible */

get_header(); 
wp_enqueue_script( 'jquery-masonry','',array('jquery'),'',true );
wp_register_script( 'scripts_search', get_template_directory_uri() . '/js/ht-scripts-search.js','' ,'' ,true );
wp_enqueue_script( 'scripts_search' );
wp_register_script( 'scripts_grid', get_template_directory_uri() . '/js/ht-scripts-grid.js',array('jquery-masonry'),'' ,true );
wp_enqueue_script( 'scripts_grid' );

					
$fulldetails=get_option('options_full_detail_staff_cards'); // 1 = show
$directorystyle = get_option('options_staff_directory_style'); // 0 = squares, 1 = circles
$showgrade = get_option('options_show_grade_on_staff_cards'); // 1 = show 
$showmobile = get_option('options_show_mobile_on_staff_cards'); // 1 = show
$sort= "";
if ( isset( $_GET["sort"] ) ) $sort = $_GET["sort"]; 
if (!$sort) $sort = strtolower(get_post_meta($post->ID,'staff_directory_order',true));
if (!$sort) $sort = "first";
if ( $sort != "first" && $sort != "last" ) $sort = "first";
$requestshow = "A";
if ( isset( $_REQUEST['show'] ) ) $requestshow = substr( esc_attr( $_REQUEST['show'] ), 0 , 1 );
$grade = '';

if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
	<a class="sr-only sr-only-focusable" href="#gridcontainer"><?php echo _x('Skip to staff','Screen reader text','govintranet'); ?></a>
	<div class="row">
		<div class="col-lg-8 col-md-8 col-sm-12">
			<div class='breadcrumbs'>
				<?php if(function_exists('bcn_display') && !is_front_page()) bcn_display(); ?>
			</div>
			<div class="col-lg-12 col-md-12 col-sm-12">
				<h1><?php the_title(); ?></h1>
			</div>
			<div class="col-sm-12 well well-sm" id="staff-search">
				<div class="col-sm-8">
					<form class="form-horizontal" id="searchform2" name="searchform2" action="<?php if ( function_exists('relevanssi_do_query') ) { echo site_url('/'); } else { echo site_url( '/search-staff/' ); } ?>">
						<div class="input-group">
							<label for="s2" class="sr-only"><?php _e('Search staff' , 'govintranet' ); ?></label>
					    	<input type="text" class="form-control pull-left" placeholder="<?php _e('Name, job title, skills, team, number...' , 'govintranet' ); ?>" name="<?php if ( function_exists('relevanssi_do_query') ) { echo "s"; } else { echo "q"; } ?>" id="s2">
					    	<input type="hidden" name="include" value="user">
					    	<input type="hidden" name="post_types[]" value="team">
					    	<input type="hidden" name="post_types[]" value="user">
							<span class="input-group-btn">
							<label for="searchbutton2" class="sr-only"><?php _e('Search','govintranet'); ?></label>
							<button class="btn btn-primary" type="submit" id="searchbutton2"><i class="dashicons dashicons-search"></i></button>
							 </span>
						</div><!-- /input-group -->
					</form>
				</div>
				<div class="col-sm-4">
					<?php
					$teams = get_posts('post_type=team&posts_per_page=-1&post_parent=0&orderby=title&order=ASC');
					if ($teams) {
						$otherteams='';
				  		foreach ((array)$teams as $team ) {
				  			$otherteams.= " <li><a href='".get_permalink($team->ID)."'>".get_the_title($team->ID)."</a></li>";
				  		}  
				  		$teamdrop = get_option('options_team_dropdown_name');
				  		if ($teamdrop=='') $teamdrop = __("Browse teams","govintranet");
				  		echo '
						<div class="dropdown">
						  <button class="btn btn-primary pull-right dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
						    ' . $teamdrop . '
						    <span class="caret"></span>
						  </button>
						  <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu1">' . $otherteams . '
						  </ul>
						</div>					
						';
					}
					?>
				</div>
			</div>
		</div><!--end-->	
		<div class="col-lg-4 col-md-4 col-sm-12">
			<!-- intentionally left blank -->
		</div>
		<div class="col-lg-12 col-md-12 col-sm-12">
			<?php 
			global $wpdb;
			if ($sort == 'last' || $sort == 'first'){
				if ($sort == 'first') :
					$q = "select distinct left(meta_value,1) as letter from $wpdb->usermeta where meta_key = 'first_name' group by meta_value;";
					$liveletters = $wpdb->get_results($q,ARRAY_A);
				endif;
				if ($sort == 'last') :
					$q = "select distinct left(meta_value,1) as letter from $wpdb->usermeta where meta_key = 'last_name' group by meta_value;";
					$liveletters = $wpdb->get_results($q,ARRAY_A);
				endif;
				$live = array();
				foreach ($liveletters as $ll){
					$live[] = $ll['letter'];
				}
				$letters = range('A','Z');
				$activeletter = $requestshow;
				foreach($letters as $l) {
					if ($l == $activeletter) {
						$letterlink[$l] = "<li  class='{$l} active'><a href='?show=".$l."&amp;sort={$sort}'>".$l."</a></li>";
					} else {
						if (in_array($l, $live)){
							$letterlink[$l] = "<li  class='{$l}'><a href='?show=".$l."&amp;sort={$sort}'>".$l."</a></li>";
						} else {
								$letterlink[$l] = "<li  class='{$l} disabled'><a href='?show=".$l."&amp;sort={$sort}'>".$l."</a></li>";
						}
					}						
				}
				?>	
			<div class="col-lg-12 col-md-12 col-sm-12">
				<ul id='atozlist' class="pagination">
					<?php
					if ($sort == 'last'){
						$q = "SELECT e.ID, p.meta_value as lname, h.meta_value as fname 
							   FROM $wpdb->users e 
							   INNER JOIN $wpdb->usermeta h ON h.user_id=e.ID 
							   INNER JOIN $wpdb->usermeta p ON p.user_id=h.user_id 
							   WHERE h.meta_key = 'first_name' 
							   AND p.meta_key = 'last_name' 
							   AND ucase(left(p.meta_value,1)) = '".strtoupper($requestshow)."' 
							   GROUP BY e.ID 
							   ORDER BY lname, fname
							";
					} elseif ($sort == "first"){
						$q = "SELECT e.ID, p.meta_value as fname, h.meta_value as lname 
							   FROM $wpdb->users e 
							   INNER JOIN $wpdb->usermeta h ON h.user_id=e.ID 
							   INNER JOIN $wpdb->usermeta p ON p.user_id=h.user_id 
							   WHERE h.meta_key = 'last_name' 
							   AND p.meta_key = 'first_name' 
							   AND ucase(left(p.meta_value,1)) = '".strtoupper($requestshow)."' 
							   GROUP BY e.ID 
							   ORDER BY fname, lname
							";
					}					
					$userq = $wpdb->get_results($q,ARRAY_A);
					$html="<div class='row'>";
					foreach ((array)$userq as $u){ 
						$userid = $u['ID'];
						if ( get_user_meta($userid, 'user_hide', true ) ) continue; 
						$usergrade = get_user_meta($userid,'user_grade',true); 
						$gradecode = '';
						if ( $usergrade ) $gradecode = get_option('grade_'.$usergrade.'_grade_code', '');
						if ($sort == 'last') { $title = $u['lname']; } else { $title = $u['fname']; }
						$thisletter = strtoupper(substr($title,0,1));	
						$user_info = get_userdata($userid);
						if ( isset( $hasentries[$thisletter] ) ):
							$hasentries[$thisletter] = $hasentries[$thisletter] + 1;
						else: 
							$hasentries[$thisletter] = 1;
						endif;
						if (!$requestshow || (strtoupper($thisletter) == strtoupper($requestshow) ) ) {
							if ($sort == 'last'){
								if ( $u['lname'] && $u['fname'] ){
									$displayname = $u['lname'].", ".$u['fname'];	
								} else {
									$displayname = trim($u['lname']).trim($u['fname']);	
								}
							} else {
								if ( $u['lname'] && $u['fname'] ){
									$displayname = $u['fname']." ".$u['lname'];	
								} else {
									$displayname = trim($u['fname']).trim($u['lname']);	
								}
							} 
							if ( ( ( isset( $usergrade['slug'] ) && $usergrade['slug'] == $grade ) && ( $grade ) ) || ( !$grade ) ) {
								$gradedisplay='';
								if ($gradecode && $showgrade){
									$gradedisplay = "<span class='badge pull-right'>".$gradecode."</span>";
								}
								$avstyle="";
								if ( $directorystyle==1 ) $avstyle = " img-circle ";
								$avatarhtml = get_avatar($userid,66);
								$avatarhtml = str_replace(" photo", " photo alignleft ".$avstyle, $avatarhtml);
								if ($fulldetails){
									$profile_url = gi_get_user_url($userid); 
									$html .= "<div class='col-lg-4 col-md-6 col-sm-6 col-xs-12 pgrid-item'><div class='media well well-sm'><a href='".$profile_url."' aria-label='".esc_attr($displayname)."'>".$avatarhtml."</a><div class='media-body'><p><a href='".$profile_url."'><strong>".$displayname."</strong>".$gradedisplay."</a><br>";
									// display team name(s)
									$poduser = get_userdata($userid);
									$team = get_user_meta($userid ,'user_team',true );
									if ($team) {				
										foreach ((array)$team as $t ) { 
								  		    $theme = get_post($t);
											if ( $theme ) $html.= "<a href='".get_permalink($theme->ID)."'>".get_the_title($theme->ID)."</a><br>";
								  		}
									}  
									
									if ( get_user_meta($userid ,'user_job_title',true )) : 
										$meta = get_user_meta($userid ,'user_job_title',true );
										if ( $meta ) $html .= '<span class="small">'.$meta.'</span><br>';
									endif; 
		
								
									if ( get_user_meta($userid ,'user_telephone',true )) $html.= '<span class="small"><i class="dashicons dashicons-phone"></i> '.get_user_meta($userid ,'user_telephone',true )."</span><br>";
									if ( get_user_meta($userid ,'user_mobile',true ) && $showmobile ) $html.= '<span class="small"><i class="dashicons dashicons-smartphone"></i> '.get_user_meta($userid ,'user_mobile',true )."</span><br>";
									$html .= '<span class="small"><a href="mailto:'.$user_info->user_email.'">Email '. $user_info->first_name. '</a></span></p></div></div></div>';
									$counter++;
								} else {
									$avstyle="";
									if ( $directorystyle==1 ) $avstyle = " img-circle ";
									$avatarhtml = get_avatar($userid,66);
									$avatarhtml = str_replace(" photo", " photo alignleft ".$avstyle, $avatarhtml);
									$profile_url = gi_get_user_url($userid);
									$html .= "<div class='col-lg-4 col-md-6 col-sm-6 col-xs-12 pgrid-item'><div class='indexcard'><a href='".$profile_url."' aria-label='".esc_attr($displayname)."'><div class='media'>".$avatarhtml."<div class='media-body'><strong>".$displayname."</strong>".$gradedisplay."<br>";
									// display team name(s)
									$team = get_user_meta($userid,'user_team',true);
									if ($team){
										foreach ((array)$team as $t ) { 
								  		    $theme = get_post($t);
											if ( $theme ) $html.= get_the_title($theme->ID)."<br>";
							  			}
							  		}
									if ( get_user_meta($userid ,'user_job_title',true )) {
										$meta = get_user_meta($userid ,'user_job_title',true );
										if ( $meta ) $html .= '<span class="small">'.$meta."</span><br>";
									}
									if ( get_user_meta($userid ,'user_telephone',true )) $html.= '<span class="small"><i class="dashicons dashicons-phone"></i> '.get_user_meta($userid ,'user_telephone',true )."</span><br>";
									if ( get_user_meta($userid ,'user_mobile',true ) && $showmobile ) $html.= '<span class="small"><i class="dashicons dashicons-smartphone"></i> '.get_user_meta($userid ,'user_mobile',true )."</span>";
									$html .= "</div></div></a></div></div>";
								}																							
							}
						}
					}
					echo @implode("",$letterlink); 
					?>
				</ul>
			</div>
		</div>
		<div class="col-lg-12 col-md-12 col-sm-12">
			<div id="sortfilter">
				<div class="col-lg-4 col-md-5 col-sm-6">
					<div class="input-group">
						<div class="input-group-btn">
						<?php if ($sort=="first") : ?>
								<a class='btn btn-primary' href="<?php the_permalink(); ?>?sort=last&amp;show=<?php echo $requestshow ?>" aria-describedby="directory-sort"><?php _e('Switch to last name order' , 'govintranet' ); ?></a>
						<?php else : ?>
								<a class='btn btn-primary' href="<?php the_permalink(); ?>?sort=first&amp;show=<?php echo $requestshow ?>" aria-describedby="directory-sort"><?php _e('Switch to first name order' , 'govintranet'); ?></a>
						<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>	
		<div class="col-lg-12 col-md-12 col-sm-12">
	  		<?php 
		  	$output='<div id="gridcontainer"><div class="grid-sizer"></div>'.$html."</div>";
			echo $output;
			?>
		</div>
	<?php
	}
	?>
		<div class="col-lg-12 col-md-12 col-sm-12">
			<div class="col-lg-12 col-md-12 col-sm-12">
				<?php the_content(); ?>
			</div>
		</div>
	</div>
</div>
<?php endwhile; ?>

<?php get_footer(); ?>