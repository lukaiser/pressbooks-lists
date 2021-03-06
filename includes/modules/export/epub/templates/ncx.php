<?php

// @see: \PressBooks\Export\Export loadTemplate()

if ( ! defined( 'ABSPATH' ) )
	exit;

echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
?>
<!DOCTYPE ncx PUBLIC "-//NISO//DTD ncx 2005-1//EN"
"http://www.daisy.org/z3986/2005/ncx-2005-1.dtd">

<ncx version="2005-1" xml:lang="en" xmlns="http://www.daisy.org/z3986/2005/ncx/">

	<head>
		<!-- The following four metadata items are required for all NCX documents,
		including those conforming to the relaxed constraints of OPS 2.0 -->

		<meta name="dtb:uid" content="<?php echo trim( $dtd_uid ); ?>" /> <!-- same as in .opf -->
		<meta name="dtb:depth" content="4"/> <!-- 1 or higher -->
		<meta name="dtb:totalPageCount" content="0"/> <!-- must be 0 -->
		<meta name="dtb:maxPageNumber" content="0"/> <!-- must be 0 -->
	</head>

	<docTitle>
		<text><?php bloginfo('name'); ?></text>
	</docTitle>

	<?php if ( ! empty( $author ) ): ?>
	<docAuthor>
		<text><?php echo $author; ?></text>
	</docAuthor>
	<?php endif; ?>

	<navMap>
		<?php
		// Map has a [ Part -> Chapter ] <NavPoint> hierarchy
		$i = 1;
		$part_open = false;
		foreach ( $manifest as $k => $v ) {

			if ( true == $part_open && ! preg_match( '/^chapter-/', $k ) ) {
				$part_open = false;
				echo '</navPoint>';
			}

            if(get_post_meta( $v['ID'], 'invisible-in-toc', true ) == 'on'){
                continue;
            }

			if ( get_post_meta( $v['ID'], 'pb_part_invisible', true ) !== 'on' ) {

				$text = strip_tags( \PressBooks\Sanitize\decode( $v['post_title'] ) );
				if ( ! $text ) $text = ' ';

                $cnumber = pb_get_chapter_number($v['post_name']);
                if($cnumber !== 0){
                    $text = $cnumber." - ".$text;
                }
	
				printf( '
					<navPoint id="%s" playOrder="%s">
					<navLabel><text>%s</text></navLabel>
					<content src="OEBPS/%s" />
					', $k, $i, $text, $v['filename'] );
	
				if ( preg_match( '/^part-/', $k ) ) {
					$part_open = true;
				} else {
                    if ( \PressBooks\Export\Export::headingsToTOC() > 0 ) {
                        $subtitle = \PressBooks\Lists\Lists::get_chapter_list_by_pid("h", $v['ID'] );
                        if(is_a($subtitle, "\PressBooks\Lists\ListChapter")){
                            $subtitle = $subtitle->getHierarchicalArray();
                        }
                        if(count($subtitle["childNodes"])>0){
                            foreach($subtitle["childNodes"] as $subtitle){
                                if(array_key_exists("caption",$subtitle) && $subtitle["active"]){
                                    $i++;
                                    $text = \PressBooks\Lists\ListNodeShow::get_number($subtitle);
                                    $text .= $text != "" ? ' - ' : '';
                                    $text .= \PressBooks\Lists\ListNodeShow::get_caption($subtitle);
                                    printf( '
                                <navPoint id="%s" playOrder="%s">
                                <navLabel><text>%s</text></navLabel>
                                <content src="OEBPS/%s" />
                                ', $subtitle["id"], $i, $text, $v['filename']."#".$subtitle["id"] );
                                    if(count($subtitle["childNodes"])>0 && \PressBooks\Export\Export::headingsToTOC() > 1){
                                        foreach($subtitle["childNodes"] as $subtitle){
                                            if(array_key_exists("caption",$subtitle) && $subtitle["active"]){
                                                $i++;
                                                $text = \PressBooks\Lists\ListNodeShow::get_number($subtitle);
                                                $text .= $text != "" ? ' - ' : '';
                                                $text .= \PressBooks\Lists\ListNodeShow::get_caption($subtitle);
                                                printf( '
                                            <navPoint id="%s" playOrder="%s">
                                            <navLabel><text>%s</text></navLabel>
                                            <content src="OEBPS/%s" />
                                            ', $subtitle["id"], $i, $text, $v['filename']."#".$subtitle["id"] );
                                                if(count($subtitle["childNodes"])>0 && \PressBooks\Export\Export::headingsToTOC() > 2){
                                                    foreach($subtitle["childNodes"] as $subtitle){
                                                        if(array_key_exists("caption",$subtitle) && $subtitle["active"]){
                                                            $i++;
                                                            $text = \PressBooks\Lists\ListNodeShow::get_number($subtitle);
                                                            $text .= $text != "" ? ' - ' : '';
                                                            $text .= \PressBooks\Lists\ListNodeShow::get_caption($subtitle);
                                                            printf( '
                                                        <navPoint id="%s" playOrder="%s">
                                                        <navLabel><text>%s</text></navLabel>
                                                        <content src="OEBPS/%s" />
                                                        ', $subtitle["id"], $i, $text, $v['filename']."#".$subtitle["id"] );
                                                            if(count($subtitle["childNodes"])>0 && \PressBooks\Export\Export::headingsToTOC() > 3){
                                                                foreach($subtitle["childNodes"] as $subtitle){
                                                                    if(array_key_exists("caption",$subtitle) && $subtitle["active"]){
                                                                        $i++;
                                                                        $text = \PressBooks\Lists\ListNodeShow::get_number($subtitle);
                                                                        $text .= $text != "" ? ' - ' : '';
                                                                        $text .= \PressBooks\Lists\ListNodeShow::get_caption($subtitle);
                                                                        printf( '
                                                                    <navPoint id="%s" playOrder="%s">
                                                                    <navLabel><text>%s</text></navLabel>
                                                                    <content src="OEBPS/%s" />
                                                                    ', $subtitle["id"], $i, $text, $v['filename']."#".$subtitle["id"] );
                                                                        if(count($subtitle["childNodes"])>0 && \PressBooks\Export\Export::headingsToTOC() > 4){
                                                                            foreach($subtitle["childNodes"] as $subtitle){
                                                                                if(array_key_exists("caption",$subtitle) && $subtitle["active"]){
                                                                                    $i++;
                                                                                    $text = \PressBooks\Lists\ListNodeShow::get_number($subtitle);
                                                                                    $text .= $text != "" ? ' - ' : '';
                                                                                    $text .= \PressBooks\Lists\ListNodeShow::get_caption($subtitle);
                                                                                    printf( '
                                                                                <navPoint id="%s" playOrder="%s">
                                                                                <navLabel><text>%s</text></navLabel>
                                                                                <content src="OEBPS/%s" />
                                                                                ', $subtitle["id"], $i, $text, $v['filename']."#".$subtitle["id"] );
                                                                                    if(count($subtitle["childNodes"])>0 && \PressBooks\Export\Export::headingsToTOC() > 5){
                                                                                        foreach($subtitle["childNodes"] as $subtitle){
                                                                                            if(array_key_exists("caption",$subtitle) && $subtitle["active"]){
                                                                                                $i++;
                                                                                                $text = \PressBooks\Lists\ListNodeShow::get_number($subtitle);
                                                                                                $text .= $text != "" ? ' - ' : '';
                                                                                                $text .= \PressBooks\Lists\ListNodeShow::get_caption($subtitle);
                                                                                                printf( '
                                                                                            <navPoint id="%s" playOrder="%s">
                                                                                            <navLabel><text>%s</text></navLabel>
                                                                                            <content src="OEBPS/%s" />
                                                                                            ', $subtitle["id"], $i, $text, $v['filename']."#".$subtitle["id"] );

                                                                                                echo '</navPoint>';
                                                                                            }
                                                                                        }
                                                                                    }

                                                                                    echo '</navPoint>';
                                                                                }
                                                                            }
                                                                        }

                                                                        echo '</navPoint>';
                                                                    }
                                                                }
                                                            }

                                                            echo '</navPoint>';
                                                        }
                                                    }
                                                }

                                                echo '</navPoint>';
                                            }
                                        }
                                    }
                                    echo '</navPoint>';
                                }
                            }
                        }
                    }
					echo '</navPoint>';
				}
				
			++$i;

			}

		}
		if ( true == $part_open ) {
			echo '</navPoint>';
		}
		?>
	</navMap>
</ncx>