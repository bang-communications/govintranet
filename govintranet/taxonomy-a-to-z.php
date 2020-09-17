<?php
/**
 * The template for displaying A-Z pages.
 *
 * Uses atoz taxonomy to display lists of pages, teams and tasks
 * 
 */

get_header(); 

	if ( have_posts() )
		the_post();
		
		$thistax = $wp_query->get_queried_object();
		$slug = $thistax->slug; 
		$term_id = $thistax->term_id;
		$blacklist = get_option("options_module_a_to_z_blacklist"); // added v4.2.3
		$whitelist = get_option("options_module_a_to_z_whitelist"); 
		if ( isset($blacklist) && $blacklist ): 
			$stopwords = explode("," ,  strtolower( sanitize_text_field( $blacklist ) ) );
		else:
			$stopwords = array('the','for','and','out'); // words greater than 2 letters to ignore
		endif;
		if ( isset($whitelist) && $whitelist ):
			$gowords = explode("," ,  strtolower( sanitize_text_field( $whitelist ) ) );
		else:
			$gowords = array("hr","it","is","pq" ); // words less than 3 letters to include
		endif;
		?>

		<div class="col-lg-12 col-md-12 white">
			<div class="row">
				<div class='breadcrumbs'>
					<?php if(function_exists('bcn_display') && !is_front_page()) {
						bcn_display();
						}?>
				</div>
			</div>
			<h1><?php _e('A to Z' , 'govintranet' ); ?></h1>
			<ul class="pagination">
			<?php 
			//fill the default a to z array
			$letters = range('a','z');
			$letterlink = array();
			$hasentries = array();
			foreach($letters as $l) { 
				$letterlink[$l] = "<li class='disabled'><a href='#'>".strtoupper($l)."</a></li>";
			}				
			$terms = get_terms('a-to-z',array("hide_empty"=>true,"parent"=>0,"orderby"=>"slug"));
			if ($terms) {
				foreach ((array)$terms as $taxonomy ) {
					$letterlink[$taxonomy->slug] = "<li";
					if (strtolower($slug)==strtolower($taxonomy->slug)) $letterlink[$taxonomy->slug] .=  " class='active'";
					$letterlink[$taxonomy->slug] .=  "><a href='".get_term_link($taxonomy->slug,'a-to-z')."'>".strtoupper($taxonomy->name)."</a></li>";
				}
			}
			echo @implode("",$letterlink); 
			?>
			</ul>
			<h2><?php echo single_cat_title(); ?></h2>
			<?php
			$args = array(
				'posts_per_page' => -1,
				'no_found_rows' => true,
				'post_status' => array('publish'),
				'tax_query' => array(
					array(
						'taxonomy' => 'a-to-z',
						'field' => 'slug',
						'terms' => $slug,
					)
				)
			);
			$postslist = new WP_Query( $args ); 
			$sortedlist = array();
			
			if ( ! $postslist->have_posts() ) { 
				echo "<div class='well'>";
				echo "<h1>";
				_e( 'Not found', 'govintranet' );
				echo "</h1>";
				echo "<p>";
				_e( 'There\'s nothing to show', 'govintranet' );
				echo ".</p>";
				get_search_form(); 
				echo "</div>";
			}
			
			/* highlight words that begin with this letter in the standard post title */

			while ( $postslist->have_posts() ) : $postslist->the_post(); 
				$foundkey = false; //set a flag to see if we get a match
				$oldtitle = get_the_title($post->ID); 
				if ( strpos($oldtitle, " ") ):
					$otwords = explode(" ",$oldtitle); 
				else:
					$otwords = array($oldtitle); 
				endif;
				$newwords = array();
				$newtitle = '';
				$tempot = '';
				foreach ($otwords as $ot){
					$orig_ot = $ot;
					//$ot = preg_replace('/[^a-z\d]+/i', '', $ot); 
					$ot = str_replace('?', '', $ot); 
					$ot = str_replace('!', '', $ot); 
					$ot = str_replace(';', '', $ot); 
					$ot = str_replace(':', '', $ot); 
					$ot = str_replace(',', '', $ot); 
					if ( strtolower(substr($ot, 0, 1)) == strtolower($slug) && (strlen($ot) > 2 || in_array(strtolower($ot), $gowords )) && !in_array(strtolower($ot),$stopwords) ) {
						$newwords[] ="<strong>".$orig_ot."</strong>"; 
						$foundkey = true; 
						if (!isset($sortedlist[get_the_id()]['ID'])){
							$sortedlist[get_the_id()]['alistword'] = strtolower($ot);
							$sortedlist[get_the_id()]['ID'] = get_the_id();
							if ( $ot == strtoupper($ot) ){
								$sortedlist[get_the_id()]['keyword'] = strtoupper($ot);
							} else {
								$sortedlist[get_the_id()]['keyword'] = strtolower($ot);
							}
						}
						if (!$tempot) {
							$tempot = $ot;
						}
					} else {
						$newwords[] = $orig_ot;
					}
				}
				
				if ( count($newwords) ) $newtitle = implode(" ", $newwords);
				if (!$foundkey) $newtitle = ''; 
				$post_type = ucwords($post->post_type);
				$userurl = get_permalink();

				/* if we didn't get a match via the standard post title we'll look in the keywords field for a shortcode [Extra A to Z entry] */

				if (!$foundkey){ 
					
					/* position marker for finding the next [ in keywords */

					$syns = 0; 
					$synpos = true;

					/* load the keywords for this post */

					$synonyms = sanitize_text_field ( get_post_meta(get_the_id(), 'keywords', true) ); 
					
					/* flag to check if we found a match in the shortcode */

					$foundletter = false; 

					/* check iteratively for shortcodes */
					
					while ($synpos && $synonyms){ 
						//get any synonym words
						if ( $foundletter ) {
							break;
						}
						$findtxt = "["; 
						$findstartpos = strpos ($synonyms,$findtxt,$syns);  
						if ($findstartpos > -1){ 
							$syns = $findstartpos+1;
							$findendpos = strpos ($synonyms,"]",$syns); 				
							$synstr = substr($synonyms, $findstartpos+1, $findendpos-$findstartpos-1);
							$otwords = explode(" ",$synstr); //process the shortcode by highlight words
							$newwords = array(); 
							$foundletter = false; //flag to check if we found a match in the shortcode
							foreach ($otwords as $ot){ 
								$orig_ot = $ot;
								if (strtolower(substr($ot, 0, 1)) == strtolower($slug)  && (strlen($ot) > 2 || in_array(strtolower($ot), $gowords )) && !in_array(strtolower($ot),$stopwords)) {
								//don't include tiny words but allow common acronyms
								
									//$ot = preg_replace('/[^a-z\d]+/i', '', $ot); 
									$ot = str_replace('?', '', $ot); 
									$ot = str_replace('!', '', $ot); 
									$ot = str_replace(';', '', $ot); 
									$ot = str_replace(':', '', $ot); 
									$ot = str_replace(',', '', $ot); 
									$foundletter=true;
									$newwords[] = "<strong>".$orig_ot."</strong>"; 
									if (!isset($sortedlist[get_the_id()]['ID'])){
										$sortedlist[get_the_id()]['alistword'] = strtolower($ot);
										$sortedlist[get_the_id()]['ID'] = get_the_id();
										if ( $ot == strtoupper($ot) ){
											$sortedlist[get_the_id()]['keyword'] = strtoupper($ot);
										} else {
											$sortedlist[get_the_id()]['keyword'] = strtolower($ot);
										}
									}
									if (!$tempot) {
										$tempot = $ot;
									}
								} else {
									$newwords[] = $orig_ot; 
								}
							}
							if ($foundletter){
								$newsyn = implode(" ", $newwords); 
								$newtitle .= $newsyn ;
							}
						} else {
							$synpos=false;
						}
					}
					$newtitle = ucfirst($newtitle);
					if ( isset($foundletter) && $foundletter ) {
						$sortedlist[get_the_id()]['newtitle'] = $newtitle;
					}
				} 
				if ( isset($tempot) && $tempot && isset($newtitle) && $newtitle && ( (isset($foundletter) && $foundletter ) || (isset($foundkey) && $foundkey) ) ) {
					$sortedlist[get_the_id()]['newtitle'] = $newtitle;
				}
			endwhile;
			asort($sortedlist,SORT_REGULAR);
			echo "<dl class='dl-atoz row'>";
			//final check to see if we actually found anything
			$lastword = '';
			$stripe = 'even'; 
			foreach ( $sortedlist as $key => $val ){ 
				global $post; 
				$post = get_post($key);
				setup_postdata($post); 
				$post_type = ucfirst($post->post_type);
				if ($post_type=='Attachment'): 
					if ( ucfirst($val['keyword']) != $lastword):
						echo "<dt class='col-sm-2 ".$stripe."'>".str_replace(",", "",  ucfirst($val['keyword']))."</dt>"; 
						$lastword = ucfirst($val['keyword']);
					else:
						echo "<dt>&nbsp;</dt>"; 
					endif;
					?>
					<dd><a href="<?php echo wp_get_attachment_url( $key ); ?>" rel="bookmark"><?php echo $val['newtitle'];  ?></a></dd>
					<?php  
				elseif ($post_type=='User'): 
					if ( ucfirst($val['keyword']) != $lastword):
						echo "<dt class='col-sm-2 ".$stripe."'>".str_replace(",", "",  ucfirst($val['keyword']))."</dt>"; 
						$lastword = ucfirst($val['keyword']);
					else:
						echo "<dt>&nbsp;</dt>"; 
					endif;
					?>
					<dd><a href="<?php echo $userurl; ?>" rel="bookmark"><?php echo $val['newtitle'];  ?></a></dd>
					<?php 
				else: 
					if ( isset( $val['keyword'] ) && ucfirst($val['keyword']) != $lastword && ucfirst($val['keyword']) != $lastword."s" ):
						if ( 'even' == $stripe ) { $stripe = 'odd'; } 
						else { $stripe = 'even'; }
						echo "<dt class='col-sm-2 ".$stripe."'>".str_replace(",", "",  ucfirst($val['keyword']))."</dt>"; 
						$lastword = ucfirst($val['keyword']);
					else:
						echo "<dt class='col-sm-2 ".$stripe."'></dt>"; 
					endif;
						?>
					<dd class='col-sm-10 <?php echo $stripe; ?>'><a href="<?php echo get_the_permalink($key); ?>" rel="bookmark"><?php echo $val['newtitle']; ?></a></dd>
					<?php
				endif;
			}
			echo "</dl>";
			echo '<ul class="pagination">';
			echo @implode("",$letterlink); 
			echo '</ul>';

			?>
		</div>
<?php wp_reset_postdata(); ?>
<?php get_footer(); ?>