<?php

// @see: \PressBooks\Export\Export loadTemplate()

if ( ! defined( 'ABSPATH' ) )
	exit;

echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
?>

<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">

	<head>
		<meta http-equiv="default-style" content="text/html; charset=utf-8"/>
		<title><?php bloginfo( 'name' ); ?> </title>
		<?php if ( ! empty( $stylesheet ) ): ?><link rel="stylesheet" href="<?php echo $stylesheet; ?>" type="text/css" /><?php endif; ?>
	</head>

	<body>
		<nav epub:type="toc">
			<h1 class="title">Table of Contents</h1>
			<ol epub:type="list">
				<?php
				// Map has a [ Part -> Chapter ] <NavPoint> hierarchy
				$part_open = false;
				foreach ( $manifest as $k => $v ) {

					if ( true == $part_open && ! preg_match( '/^chapter-/', $k ) ) {
						$part_open = false;
						echo '</ol></li>' . "\n";
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

						if ( preg_match( '/^part-/', $k ) ) {
							echo '<li><a href="OEBPS/' . $v['filename'] . '">' . $text . '</a>' . "\n";
						} else {
							echo '<li><a href="OEBPS/' . $v['filename'] . '">' . $text . '</a>';
                            if ( \PressBooks\Export\Export::headingsToTOC() > 0 ) {
                                $subtitle = \PressBooks\Lists\Lists::get_chapter_list_by_pid("h", $v['ID'] );
                                echo \PressBooks\Lists\ListShow::hierarchical_chapter($subtitle, \PressBooks\Export\Export::headingsToTOC()+1, "OEBPS/".$v['filename'], "ol");
                            }
                            echo '</li>' . "\n";
						}

						if ( preg_match( '/^part-/', $k ) ) {
							$part_open = true;
							echo '<ol>' . "\n";
						}
					}
				}
				if ( true == $part_open ) {
					echo '</ol></li>' . "\n";
				}
				?>
			</ol>
		</nav>
	</body>
</html>