			<section class="third-block-wrap"> 
				<div class="third-block clearfix">
				<h2><?php _e('Table of Contents', 'pressbooks'); ?></h2>
				<?php $book = pb_get_book_structure(); ?>
					<ul class="table-of-content" id="table-of-content">
						<li>
							<ul class="front-matter">
								<?php foreach ($book['front-matter'] as $fm): ?>
								<?php if ($fm['post_status'] != 'publish' || get_post_meta( $fm['ID'], 'invisible-in-toc', true ) == 'on') continue; // Skip ?>
								<li class="front-matter <?php echo pb_get_section_type( get_post($fm['ID']) ) ?>"><a href="<?php echo get_permalink($fm['ID']); ?>"><?php if(($c = pb_get_chapter_number($fm['post_name'])) !== 0) echo '<span class="toc-front-matter-number">'.$c." - ".'</span>'?><?php echo pb_strip_br( $fm['post_title'] );?></a>
                                    <?php $subtitle = \PressBooks\Lists\Lists::get_chapter_list_by_pid("h", $fm['ID'] );
                                    if ( $subtitle && pb_should_parse_sections() ){?>
                                        <?php echo \PressBooks\Lists\ListShow::hierarchical_chapter($subtitle);?>
                                    <?php } ?>
                                </li>
								<?php endforeach; ?>
							</ul>
						</li>
							<?php foreach ($book['part'] as $part):?>
							<li><h4><?php if ( count( $book['part'] ) > 1  && get_post_meta( $part['ID'], 'pb_part_invisible', true ) !== 'on' ) { ?>
							<?php if ( get_post_meta( $part['ID'], 'pb_part_content', true ) ) { ?><a href="<?php echo get_permalink($part['ID']); ?>"><?php } ?>
							<?php echo $part['post_title']; ?>
							<?php if ( get_post_meta( $part['ID'], 'pb_part_content', true ) ) { ?></a><?php } ?>
							<?php } ?></h4></li>
							<li>
								<ul>
									<?php foreach ($part['chapters'] as $chapter) : ?>
										<?php if ($chapter['post_status'] != 'publish' || get_post_meta( $chapter['ID'], 'invisible-in-toc', true ) == 'on') continue; // Skip ?>
										<li class="chapter <?php echo pb_get_section_type( get_post($chapter['ID']) ) ?>"><a href="<?php echo get_permalink($chapter['ID']); ?>"><?php if(($c = pb_get_chapter_number($chapter['post_name'])) !== 0) echo '<span class="toc-chapter-number">'.$c." - ".'</span>'?><?php echo pb_strip_br( $chapter['post_title'] ); ?></a>
										<?php $subtitle = \PressBooks\Lists\Lists::get_chapter_list_by_pid("h", $chapter['ID'] );
										if ( $subtitle && pb_should_parse_sections() ){?>
											<?php echo \PressBooks\Lists\ListShow::hierarchical_chapter($subtitle);?>
										<?php } ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</li>
							<?php endforeach; ?>
							<li><h4><!-- Back-matter --></h4></li>
							<li>
								<ul class="back-matter">
									<?php foreach ($book['back-matter'] as $bm): ?>
									<?php if ($bm['post_status'] != 'publish' || get_post_meta( $bm['ID'], 'invisible-in-toc', true ) == 'on') continue; // Skip ?>
									<li class="back-matter <?php echo pb_get_section_type( get_post($bm['ID']) ) ?>"><a href="<?php echo get_permalink($bm['ID']); ?>"><?php if(($c = pb_get_chapter_number($bm['post_name'])) !== 0) echo '<span class="toc-back-matter-number">'.$c." - ".'</span>'?><?php echo pb_strip_br( $bm['post_title'] );?></a>
                                        <?php $subtitle = \PressBooks\Lists\Lists::get_chapter_list_by_pid("h", $bm['ID'] );
                                        if ( $subtitle && pb_should_parse_sections() ){?>
                                            <?php echo \PressBooks\Lists\ListShow::hierarchical_chapter($subtitle);?>
                                        <?php } ?>
                                    </li>
									<?php endforeach; ?>
								</ul>
							</li>
					</ul><!-- end #toc -->	
						
				</div><!-- end .third-block -->
			</section> <!-- end .third-block -->
