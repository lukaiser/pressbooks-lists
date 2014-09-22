<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Export\Xhtml;


use PressBooks\Export\Export;
use PressBooks\Sanitize;

require_once( PB_PLUGIN_DIR . 'symbionts/htmLawed/htmLawed.php' );

class Xhtml11 extends Export {


	/**
	 * Timeout in seconds.
	 * Used with wp_remote_get()
	 *
	 * @var int
	 */
	public $timeout = 90;


	/**
	 * Service URL
	 *
	 * @var string
	 */
	public $url;


	/**
	 * Endnotes storage container.
	 * Use when overriding the footnote shortcode.
	 *
	 * @var array
	 */
	protected $endnotes = array();


	/**
	 * Sometimes the user will omit an introduction so we must inject the style in either the first
	 * part or the first chapter ourselves.
	 *
	 * @var bool
	 */
	protected $hasIntroduction = false;

    /**
     * Position of lists
     *
     * 0 = Don not display
     * 1 = In front matter
     * 2 = In back matter
     * @var int
     */
    protected $listsPosition = 0;


	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

		if ( ! defined( 'PB_XMLLINT_COMMAND' ) )
			define( 'PB_XMLLINT_COMMAND', '/usr/bin/xmllint' );

		$defaults = array(
			'endnotes' => false,
		);
		$r = wp_parse_args( $args, $defaults );

		// Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";

		// Append endnotes to URL?
		if ( $r['endnotes'] )
			$this->url .= '&endnotes=true';

		// HtmLawed: id values not allowed in input
		foreach ( $this->reservedIds as $val ) {
			$fixme[$val] = 1;
		}
		if ( isset( $fixme ) )
			$GLOBALS['hl_Ids'] = $fixme;

	}


	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {

		// Get XHTML

		$output = $this->queryXhtml();

		if ( ! $output ) {
			return false;
		}

		// Save XHTML as file in exports folder

		$filename = $this->timestampedFileName( '.html' );
		file_put_contents( $filename, $output );
		$this->outputPath = $filename;

		return true;
	}


	/**
	 * Check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	function validate() {

		// Xmllint params
		$command = PB_XMLLINT_COMMAND . ' --html --valid --noout ' . escapeshellcmd( $this->outputPath ) . ' 2>&1';

		// Execute command
		$output = array();
		$return_var = 0;
		exec( $command, $output, $return_var );

		// Is this a valid XHTML?
		if ( count( $output ) ) {
			$this->logError( implode( "\n", $output ) );

			return false;
		}

		return true;
	}


	/**
	 * Procedure for "format/xhtml" rewrite rule.
	 */
	function transform() {

		// Check permissions

		if ( ! current_user_can( 'manage_options' ) ) {
			$timestamp = absint( @$_REQUEST['timestamp'] );
			$hashkey = @$_REQUEST['hashkey'];
			if ( ! $this->verifyNonce( $timestamp, $hashkey ) ) {
				wp_die( __( 'Invalid permission error', 'pressbooks' ) );
			}
		}

        //Filters for the chapter numbers and the books structure, do to the rearangements in the front-matter
        add_filter( 'pb_get_chapter_number', array($this, "get_chapter_number"), 10, 2 );
        add_filter( 'pb_get_chapter_number_section', array($this, "get_chapter_number_section"), 10, 2 );
        add_filter( 'pb_getBookStructure', array($this, "getBookStructure"), 10);
        $this->themeOptionsOverrides();

		// Override footnote shortcode
		if ( ! empty( $_GET['endnotes'] ) ) {
			add_shortcode( 'footnote', array( $this, 'endnoteShortcode' ) );
		} else {
			add_shortcode( 'footnote', array( $this, 'footnoteShortcode' ) );
		}


		// ------------------------------------------------------------------------------------------------------------
		// XHTML, Start!

		$metadata = \PressBooks\Book::getBookInformation();
		$book_contents = $this->preProcessBookContents( \PressBooks\Book::getBookContents() );

		$this->echoDocType( $book_contents, $metadata );

		echo "<head>\n";
		echo '<meta content="text/html; charset=UTF-8" http-equiv="content-type" />' . "\n";
		echo '<base href="' . trailingslashit( site_url( '', 'http' ) ) . '" />' . "\n";

		$this->echoMetaData( $book_contents, $metadata );

		echo '<title>' . get_bloginfo( 'name' ) . "</title>\n";
		echo "</head>\n<body>\n";

		// Before Title Page
		$this->echoBeforeTitle( $book_contents, $metadata );

		// Half-title
		$this->echoHalfTitle( $book_contents, $metadata );

		// Cover
		$this->echoCover( $book_contents, $metadata );

		// Title
		$this->echoTitle( $book_contents, $metadata );

		// Copyright
		$this->echoCopyright( $book_contents, $metadata );

		// Dedication and Epigraph (In that order!)
		$this->echoDedicationAndEpigraph( $book_contents, $metadata );

		// Table of contents
		$this->echoToc( $book_contents, $metadata );

        // Lists
        if($this->listsPosition == 1){
            $this->echoLists( $book_contents, $metadata);
        }

		// Front-matter
		$this->echoFrontMatter( $book_contents, $metadata );

		// Promo
		$this->createPromo( $book_contents, $metadata );

		// Parts, Chapters
		$this->echoPartsAndChapters( $book_contents, $metadata );

        // Lists
        if($this->listsPosition == 2){
            $this->echoLists( $book_contents, $metadata);
        }

		// Back-matter
		$this->echoBackMatter( $book_contents, $metadata );

		// XHTML, Stop!
		echo "</body>\n</html>";

        remove_filter( 'pb_get_chapter_number', array($this, "get_chapter_number"), 10);
        remove_filter( 'pb_get_chapter_number_section', array($this, "get_chapter_number_section"), 10);
        remove_filter( 'pb_getBookStructure', array($this, "getBookStructure"), 10);
	}


	/**
	 * Add $this->url as additional log info, fallback to parent.
	 *
	 * @param $message
	 * @param array $more_info (unused, overridden)
	 */
	function logError( $message, array $more_info = array() ) {

		$more_info = array(
			'url' => $this->url,
		);

		parent::logError( $message, $more_info );
	}


	/**
	 * Wrap footnotes for Prince compatibility
	 *
	 * @see http://www.princexml.com/doc/8.1/footnotes/
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	function footnoteShortcode( $atts, $content = null ) {

		return '<span class="footnote">' . trim( $content ) . '</span>';
	}


	/**
	 * Convert footnotes to endnotes by moving them to the end of the_content()
	 *
	 * @see doEndnotes
	 *
	 * @param array $atts
	 * @param null  $content
	 *
	 * @return string
	 */
	function endnoteShortcode( $atts, $content = null ) {

		global $id;

		if ( ! $content ) {
			return '';
		}

		$this->endnotes[$id][] = trim( $content );

		return '<sup class="endnote">' . count( $this->endnotes[$id] ) . '</sup>';
	}


	/**
	 * Style endnotes.
	 *
	 * @see endnoteShortcode
	 *
	 * @param $id
	 *
	 * @return string
	 */
	function doEndnotes( $id ) {

		if ( ! isset( $this->endnotes[$id] ) || ! count( $this->endnotes[$id] ) )
			return '';

		$e = '<div class="endnotes">';
		$e .= '<hr />';
		$e .= '<h3>' . __( 'Notes', 'pressbooks' ) . '</h3>';
		$e .= '<ol>';
		foreach ( $this->endnotes[$id] as $endnote ) {
			$e .= "<li><span>$endnote</span></li>";
		}
		$e .= '</ol></div>';

		return $e;
	}


	/**
	 * Query the access protected "format/xhtml" URL, return the results.
	 *
	 * @return bool|string
	 */
	protected function queryXhtml() {

		$args = array( 'timeout' => $this->timeout );
		$response = wp_remote_get( $this->url, $args );

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			$this->logError( $response->get_error_message() );

			return false;
		}

		// Server error?
		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			$this->logError( wp_remote_retrieve_response_message( $response ) );

			return false;
		}

		return wp_remote_retrieve_body( $response );
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Sanitize book
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * @param $book_contents
	 *
	 * @return mixed
	 */
	protected function preProcessBookContents( $book_contents ) {

		// We need to change global $id for shortcodes, the_content, ...
		global $id;
		$old_id = $id;

		// Do root level structures first.
		foreach ( $book_contents as $type => $struct ) {

			if ( preg_match( '/^__/', $type ) )
				continue; // Skip __magic keys

			foreach ( $struct as $i => $val ) {

				if ( isset( $val['post_content'] ) ) {
					$id = $val['ID'];
					$book_contents[$type][$i]['post_content'] = $this->preProcessPostContent( $val['post_content'] );
				}
				if ( isset( $val['post_title'] ) ) {
					$book_contents[$type][$i]['post_title'] = Sanitize\sanitize_xml_attribute( $val['post_title'] );
				}
				if ( isset( $val['post_name'] ) ) {
					$book_contents[$type][$i]['post_name'] = $this->preProcessPostName( $val['post_name'] );
				}

				if ( 'part' == $type ) {

					// Do chapters, which are embedded in part structure
					foreach ( $book_contents[$type][$i]['chapters'] as $j => $val2 ) {

						if ( isset( $val2['post_content'] ) ) {
							$id = $val2['ID'];
							$book_contents[$type][$i]['chapters'][$j]['post_content'] = $this->preProcessPostContent( $val2['post_content'] );
						}
						if ( isset( $val2['post_title'] ) ) {
							$book_contents[$type][$i]['chapters'][$j]['post_title'] = Sanitize\sanitize_xml_attribute( $val2['post_title'] );
						}
						if ( isset( $val2['post_name'] ) ) {
							$book_contents[$type][$i]['chapters'][$j]['post_name'] = $this->preProcessPostName( $val2['post_name'] );
						}

					}
				}
			}
		}

		$id = $old_id;
		return $book_contents;
	}


	/**
	 * @param string $content
	 *
	 * @return string
	 */
	protected function preProcessPostContent( $content ) {

		$content = apply_filters( 'the_content', $content );
		$content = $this->fixAnnoyingCharacters( $content );
        $content = $this->kneadHtml($content);
		$content = $this->tidy( $content );

		return $content;
	}


	/**
	 * Tidy HTML
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected function tidy( $html ) {

		// Make XHTML 1.1 strict using htmlLawed

		$config = array(
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
			'unique_ids' => 'fixme-',
			'hook' => '\PressBooks\Sanitize\html5_to_xhtml11',
			'tidy' => -1,
		);

		return htmLawed( $html, $config );
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Echo Functions
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoDocType( $book_contents, $metadata ) {

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . "\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">' . "\n";
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoMetaData( $book_contents, $metadata ) {

		foreach ( $metadata as $name => $content ) {
			$name = Sanitize\sanitize_xml_id( str_replace( '_', '-', $name ) );
			$content = trim( strip_tags( html_entity_decode( $content ) ) ); // Plain text
			$content = preg_replace( '/\s+/', ' ', preg_replace( '/\n+/', ' ', $content ) ); // Normalize whitespaces
			$content = Sanitize\sanitize_xml_attribute( $content );
			printf( '<meta name="%s" content="%s" />', $name, $content );
			echo "\n";
		}

	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoCover( $book_contents, $metadata ) {
		// Does nothing.
		// Is here for child classes to override if ever needed.
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoBeforeTitle( $book_contents, $metadata ) {

		$front_matter_printf = '<div class="front-matter %s" id="%s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%s</h3><h1 class="front-matter-title">%s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%s</div>%s';
		$front_matter_printf .= '</div>';

		foreach ( array( 'before-title' ) as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] )
					continue; // Skip

				$id = $front_matter['ID'];
				$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

				if ( $compare != $subclass )
					continue; //Skip

				$slug = $front_matter['post_name'];
				$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $front_matter['post_content'];

				printf( $front_matter_printf,
					$subclass,
					$slug,
                    pb_get_chapter_number($slug),
					Sanitize\decode( $title ),
					$content,
					$this->doEndnotes( $id ) );

				echo "\n";
			}
		}
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoHalfTitle( $book_contents, $metadata ) {

		echo '<div id="half-title-page">';
		echo '<h1 class="title">' . get_bloginfo( 'name' ) . '</h1>';
		echo '</div>' . "\n";

	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoTitle( $book_contents, $metadata ) {

		// Look for custom title-page

		$content = '';
		foreach ( $book_contents['front-matter'] as $front_matter ) {

			if ( ! $front_matter['export'] )
				continue; // Skip

			$id = $front_matter['ID'];
			$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

			if ( 'title-page' != $subclass )
				continue; // Skip

			$content = $front_matter['post_content'];
			break;
		}

		// HTML

		echo '<div id="title-page">';
		if ( $content ) {
			echo $content;
		} else {
			printf( '<h1 class="title">%s</h1>', get_bloginfo( 'name' ) );
			printf( '<h2 class="subtitle">%s</h2>', @$metadata['pb_subtitle'] );
			printf( '<div class="logo"></div>' );
			printf( '<h3 class="author">%s</h3>', @$metadata['pb_author'] );
			printf( '<h4 class="publisher">%s</h4>', @$metadata['pb_publisher'] );
			printf( '<h5 class="publisher-city">%s</h5>', @$metadata['pb_publisher_city'] );
		}
		echo "</div>\n";
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoCopyright( $book_contents, $metadata ) {

		echo '<div id="copyright-page"><div class="ugc">';

		if ( ! empty( $metadata['pb_custom_copyright'] ) ) {
			echo $this->tidy( $metadata['pb_custom_copyright'] );
		} else {
			echo '<p>';
			echo get_bloginfo( 'name' ) . ' ' . __( 'Copyright', 'pressbooks' ) . ' &#169; ';
			echo ( ! empty( $metadata['pb_copyright_year'] ) ) ? $metadata['pb_copyright_year'] : date( 'Y' );
			if ( ! empty( $metadata['pb_copyright_holder'] ) ) echo ' ' . __( 'by', 'pressbooks' ) . ' ' . $metadata['pb_copyright_holder'] . '. ';
			echo '</p>';
		}

		echo "</div></div>\n";
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoDedicationAndEpigraph( $book_contents, $metadata ) {

		$front_matter_printf = '<div class="front-matter %s" id="%s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%s</h3><h1 class="front-matter-title">%s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%s</div>%s';
		$front_matter_printf .= '</div>';


		foreach ( array( 'dedication', 'epigraph' ) as $compare ) {
			foreach ( $book_contents['front-matter'] as $front_matter ) {

				if ( ! $front_matter['export'] )
					continue; // Skip

				$id = $front_matter['ID'];
				$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

				if ( $compare != $subclass )
					continue; //Skip

				$slug = $front_matter['post_name'];
				$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $front_matter['post_content'];

				printf( $front_matter_printf,
					$subclass,
					$slug,
                    pb_get_chapter_number($slug),
					Sanitize\decode( $title ),
					$content,
					$this->doEndnotes( $id ) );

				echo "\n";
			}
		}
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoToc( $book_contents, $metadata ) {

		echo '<div id="toc"><h1>' . __( 'Contents', 'pressbooks' ) . '</h1><ul>';
		foreach ( $book_contents as $type => $struct ) {

			$s = 1; // Start section counter
			
			if ( preg_match( '/^__/', $type ) )
				continue; // Skip __magic keys

			if ( 'part' == $type ) {
				foreach ( $struct as $part ) {
					$slug = $part['post_name'];
					$title = Sanitize\strip_br( $part['post_title'] );
					if ( count( $book_contents['part'] ) > 1 && $this->atLeastOneExport( $part['chapters'] ) && get_post_meta( $part['ID'], 'pb_part_invisible', true ) !== 'on' ) {
						printf( '<li class="part"><a href="#%s">%s</a></li>',
							$slug,
							Sanitize\decode( $title ) );
					} else {
						printf( '<li class="part display-none"><a href="#%s">%s</a></li>',
							$slug,
							Sanitize\decode( $title ) );
					}
					foreach ( $part['chapters'] as $j => $chapter ) {

						if ( ! $chapter['export'] || get_post_meta( $chapter['ID'], 'invisible-in-toc', true ) == 'on')
							continue;

						$subclass = \PressBooks\Taxonomy\chapter_type( $chapter['ID'] );
						$slug = $chapter['post_name'];
						$title = Sanitize\strip_br( $chapter['post_title'] );
						$subtitle = trim( get_post_meta( $chapter['ID'], 'pb_subtitle', true ) );
						$author = trim( get_post_meta( $chapter['ID'], 'pb_section_author', true ) );

                        $cnumber = pb_get_chapter_number($slug);
                        if($cnumber === 0){
                            printf( '<li class="chapter %s"><a href="#%s"><span class="toc-chapter-title">%s</span>', $subclass, $slug, Sanitize\decode( $title ) );
                        }else{
                            printf( '<li class="chapter %s"><a href="#%s"><span class="toc-chapter-title"><span class="toc-%s-number">%s - </span>%s</span>', $subclass, $slug, "chapter", $cnumber, Sanitize\decode( $title ) );
                        }

						if ( $subtitle )
							echo ' <span class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</span>';

						if ( $author )
							echo ' <span class="chapter-author">' . Sanitize\decode( $author ) . '</span>';
												
						echo '</a>';

                        if ( \PressBooks\Export\Export::headingsToTOC() > 0 ) {
                            // Display headlines
                            $subtitle = \PressBooks\Lists\Lists::get_chapter_list_by_pid("h", $chapter['ID'] );
                            echo \PressBooks\Lists\ListShow::hierarchical_chapter($subtitle, \PressBooks\Export\Export::headingsToTOC()+1, '');
                        }
													
						echo '</li>';
					}
				}
			} else {
                if(('back-matter' == $type && $this->listsPosition == 2)){
                    $typetype = $type." loi";
                    $slug = "loi";
                    $title = __( 'List of Illustrations', 'pressbooks' );
                    $cnumber = pb_get_chapter_number($slug);
                    if($cnumber === 0){
                        printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title">%s</span>', $typetype, $slug, Sanitize\decode( $title ) );
                    }else{
                        printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title"><span class="toc-%s-number">%s - </span>%s</span>', $typetype, $slug, $type, $cnumber, Sanitize\decode( $title ) );
                    }
                    $typetype = $type." lot";
                    $slug = "lot";
                    $title = __( 'List of Tables', 'pressbooks' );
                    $cnumber = pb_get_chapter_number($slug);
                    if($cnumber === 0){
                        printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title">%s</span>', $typetype, $slug, Sanitize\decode( $title ) );
                    }else{
                        printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title"><span class="toc-%s-number">%s - </span>%s</span>', $typetype, $slug, $type, $cnumber, Sanitize\decode( $title ) );
                    }
                }

                $first_frontmatter = true;
				foreach ( $struct as $val ) {

					if ( ! $val['export']  || get_post_meta( $val['ID'], 'invisible-in-toc', true ) == 'on')
						continue;

					$typetype = '';
					$subtitle = '';
					$author = '';
					$slug = $val['post_name'];
					$title = Sanitize\strip_br( $val['post_title'] );

					if ( 'front-matter' == $type ) {
						$subclass = \PressBooks\Taxonomy\front_matter_type( $val['ID'] );
						if ('title-page' == $subclass) {
                            continue; // Skip
						} else {
                            if('dedication' != $subclass && 'epigraph' != $subclass && 'before-title' != $subclass && $first_frontmatter){
                                if($this->listsPosition == 1){
                                $typetype = $type." loi";
                                    $slugl = "loi";
                                    $titlel = __( 'List of Illustrations', 'pressbooks' );
                                    $cnumber = pb_get_chapter_number($slugl);
                                    if($cnumber === 0){
                                        printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title">%s</span>', $typetype, $slugl, Sanitize\decode( $titlel ) );
                                    }else{
                                        printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title"><span class="toc-%s-number">%s - </span>%s</span>', $typetype, $slugl, $type, $cnumber, Sanitize\decode( $titlel ) );
                                    }
                                    $typetype = $type." lot";
                                    $slugl = "lot";
                                    $titlel = __( 'List of Tables', 'pressbooks' );
                                    $cnumber = pb_get_chapter_number($slugl);
                                    if($cnumber === 0){
                                        printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title">%s</span>', $typetype, $slugl, Sanitize\decode( $titlel ) );
                                    }else{
                                        printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title"><span class="toc-%s-number">%s - </span>%s</span>', $typetype, $slugl, $type, $cnumber, Sanitize\decode( $titlel ) );
                                    }
                                }
                                $first_frontmatter = false;
                            }
							$typetype = $type . ' ' . $subclass;
							$subtitle = trim( get_post_meta( $val['ID'], 'pb_subtitle', true ) );
							$author = trim( get_post_meta( $val['ID'], 'pb_section_author', true ) );
						}
					} elseif ( 'back-matter' == $type ) {
						$typetype = $type . ' ' . \PressBooks\Taxonomy\back_matter_type( $val['ID'] );
						$subtitle = trim( get_post_meta( $val['ID'], 'pb_subtitle', true ) );
						$author = trim( get_post_meta( $val['ID'], 'pb_section_author', true ) );
					}

                    $cnumber = pb_get_chapter_number($slug);
                    if($cnumber === 0){
                        printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title">%s</span>', $typetype, $slug, Sanitize\decode( $title ) );
                    }else{
                        printf( '<li class="%s"><a href="#%s"><span class="toc-chapter-title"><span class="toc-%s-number">%s - </span>%s</span>', $typetype, $slug, $type, $cnumber, Sanitize\decode( $title ) );
                    }

					if ( $subtitle )
						echo ' <span class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</span>';

					if ( $author )
						echo ' <span class="chapter-author">' . Sanitize\decode( $author ) . '</span>';

					echo '</a>';

                    if ( \PressBooks\Export\Export::headingsToTOC() > 0 ) {
                        // Display headlines
                        $subtitle = \PressBooks\Lists\Lists::get_chapter_list_by_pid("h", $val['ID'] );
                        echo \PressBooks\Lists\ListShow::hierarchical_chapter($subtitle, \PressBooks\Export\Export::headingsToTOC()+1, '');
                    }

                    echo '</li>';
				}
			}
		}
		echo "</ul></div>\n";

	}

    /**
     * @param array $book_contents
     * @param array $metadata
     */
    protected function echoLists( $book_contents, $metadata ) {

        if($this->listsPosition == 1){
            $lists_printf = '<div class="front-matter %s" id="%s">';
            $lists_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%s</h3><h1 class="front-matter-title">%s</h1></div>';
            $lists_printf .= '<div class="ugc front-matter-ugc">%s</div>%s';
            $lists_printf .= '</div>';
        }else{
            $lists_printf = '<div class="back-matter %s" id="%s">';
            $lists_printf .= '<div class="back-matter-title-wrap"><h3 class="back-matter-number">%s</h3><h1 class="back-matter-title">%s</h1></div>';
            $lists_printf .= '<div class="ugc back-matter-ugc">%s</div>%s';
            $lists_printf .= '</div>';
        }


        $slug = "loi";
        $title = __( 'List of Illustrations', 'pressbooks' );
        $content = "[LOI]";
        $content = $this->preProcessPostContent($content);


        printf( $lists_printf,
            "loi",
            $slug,
            pb_get_chapter_number($slug),
            Sanitize\decode( $title ),
            $content,
            "" );

        echo "\n";

        $slug = "lot";
        $title = __( 'List of Tables', 'pressbooks' );
        $content = "[LOT]";
        $content = $this->preProcessPostContent($content);


        printf( $lists_printf,
            "lot",
            $slug,
            pb_get_chapter_number($slug),
            Sanitize\decode( $title ),
            $content,
            "" );

        echo "\n";
    }


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoFrontMatter( $book_contents, $metadata ) {

		$front_matter_printf = '<div class="front-matter %s" id="%s">';
		$front_matter_printf .= '<div class="front-matter-title-wrap"><h3 class="front-matter-number">%s</h3><h1 class="front-matter-title">%s</h1></div>';
		$front_matter_printf .= '<div class="ugc front-matter-ugc">%s</div>%s';
		$front_matter_printf .= '</div>';

		foreach ( $book_contents['front-matter'] as $front_matter ) {

			if ( ! $front_matter['export'] )
				continue; // Skip

			$id = $front_matter['ID'];
			$subclass = \PressBooks\Taxonomy\front_matter_type( $id );

			if ( 'dedication' == $subclass || 'epigraph' == $subclass || 'title-page' == $subclass || 'before-title' == $subclass )
				continue; // Skip

			if ( 'introduction' == $subclass )
				$this->hasIntroduction = true;

			$slug = $front_matter['post_name'];
			$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $front_matter['post_title'] : '<span class="display-none">' . $front_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
			$content = $front_matter['post_content'];

			$short_title = trim( get_post_meta( $id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $id, 'pb_subtitle', true ) );
			$author = trim( get_post_meta( $id, 'pb_section_author', true ) );

			if ( $author ) {
				$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
			}

			if ( $subtitle ) {
				$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
			}

			if ( $short_title ) {
				$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
			}

			printf( $front_matter_printf,
				$subclass,
				$slug,
                pb_get_chapter_number($slug),
				Sanitize\decode( $title ),
				$content,
				$this->doEndnotes( $id ) );

			echo "\n";
		}
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function createPromo( $book_contents, $metadata ) {

		$promo_html = apply_filters( 'pressbooks_pdf_promo', '' );
		if ( $promo_html ) {
			echo $promo_html;
		}
	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoPartsAndChapters( $book_contents, $metadata ) {

		$part_printf = '<div class="part %s" id="%s">';
		$part_printf .= '<div class="part-title-wrap"><h3 class="part-number">%s</h3><h1 class="part-title">%s</h1></div>';
		$part_printf .= '</div>';

		$chapter_printf = '<div class="chapter %s" id="%s">';
		$chapter_printf .= '<div class="chapter-title-wrap"><h3 class="chapter-number">%s</h3><h2 class="chapter-title">%s</h2></div>';
		$chapter_printf .= '<div class="ugc chapter-ugc">%s</div>%s';
		$chapter_printf .= '</div>';

		$i = 1;
		foreach ( $book_contents['part'] as $part ) {

			$invisibility = ( get_post_meta( $part['ID'], 'pb_part_invisible', true ) == 'on' ) ? 'invisible' : '';

			$part_printf_changed = '';
			$slug = $part['post_name'];
			$title = $part['post_title'];

			// Inject introduction class?
			if ( ! $this->hasIntroduction && count( $book_contents['part'] ) > 1 ) {
				$part_printf_changed = str_replace( '<div class="part %s" id=', '<div class="part introduction %s" id=', $part_printf );
				$this->hasIntroduction = true;
			}

			// Inject part content?
			$part_content = trim( get_post_meta( $part['ID'], 'pb_part_content', true ) );
			if ( $part_content ) {
				$part_content = $this->preProcessPostContent( $part_content );
				$part_printf_changed = str_replace( '</h1></div></div>', "</h1></div><div class=\"ugc part-ugc\">{$part_content}</div></div>", $part_printf );
			}

			$m = ( $invisibility == 'invisible' ) ? '' : $i;
			$my_part = sprintf(
				( $part_printf_changed ? $part_printf_changed : $part_printf ),
				$invisibility,
				$slug,
				$m,
				Sanitize\decode( $title ) ) . "\n";

			$my_chapters = '';

			foreach ( $part['chapters'] as $chapter ) {

				if ( ! $chapter['export'] )
					continue; // Skip

				$chapter_printf_changed = '';
				$id = $chapter['ID'];
				$subclass = \PressBooks\Taxonomy\chapter_type( $id );
				$slug = $chapter['post_name'];
				$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $chapter['post_title'] : '<span class="display-none">' . $chapter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
				$content = $chapter['post_content'];

				$short_title = trim( get_post_meta( $id, 'pb_short_title', true ) );
				$subtitle = trim( get_post_meta( $id, 'pb_subtitle', true ) );
				$author = trim( get_post_meta( $id, 'pb_section_author', true ) );

				if ( $author ) {
					$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
				}

				if ( $subtitle ) {
					$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
				}

				if ( $short_title ) {
					$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
				}

				// Inject introduction class?
				if ( ! $this->hasIntroduction ) {
					$chapter_printf_changed = str_replace( '<div class="chapter %s" id=', '<div class="chapter introduction %s" id=', $chapter_printf );
					$this->hasIntroduction = true;
				}

				$my_chapters .= sprintf(
					( $chapter_printf_changed ? $chapter_printf_changed : $chapter_printf ),
					$subclass,
					$slug,
                    pb_get_chapter_number($slug),
					Sanitize\decode( $title ),
					$content,
					$this->doEndnotes( $id ) ) . "\n";

			}

			// Echo with parts?
			if ( $my_chapters ) {
				if ( count( $book_contents['part'] ) > 1 ) {
					echo $my_part . $my_chapters;
					if ( $invisibility !== 'invisible' ) ++$i;
				} else {
					echo $my_chapters;
				}
			}

			// Did we actually inject the introduction class?
			if ( $part_printf_changed && empty( $my_chapters ) ) {
				$this->hasIntroduction = false;
			}

		}

	}


	/**
	 * @param array $book_contents
	 * @param array $metadata
	 */
	protected function echoBackMatter( $book_contents, $metadata ) {

		$back_matter_printf = '<div class="back-matter %s" id="%s">';
		$back_matter_printf .= '<div class="back-matter-title-wrap"><h3 class="back-matter-number">%s</h3><h1 class="back-matter-title">%s</h1></div>';
		$back_matter_printf .= '<div class="ugc back-matter-ugc">%s</div>%s';
		$back_matter_printf .= '</div>';

		foreach ( $book_contents['back-matter'] as $back_matter ) {

			if ( ! $back_matter['export'] ) continue;

			$id = $back_matter['ID'];
			$subclass = \PressBooks\Taxonomy\back_matter_type( $id );
			$slug = $back_matter['post_name'];
			$title = ( get_post_meta( $id, 'pb_show_title', true ) ? $back_matter['post_title'] : '<span class="display-none">' . $back_matter['post_title'] . '</span>' ); // Preserve auto-indexing in Prince using hidden span
			$content = $back_matter['post_content'];

			$short_title = trim( get_post_meta( $id, 'pb_short_title', true ) );
			$subtitle = trim( get_post_meta( $id, 'pb_subtitle', true ) );
			$author = trim( get_post_meta( $id, 'pb_section_author', true ) );

			if ( $author ) {
				$content = '<h2 class="chapter-author">' . Sanitize\decode( $author ) . '</h2>' . $content;
			}

			if ( $subtitle ) {
				$content = '<h2 class="chapter-subtitle">' . Sanitize\decode( $subtitle ) . '</h2>' . $content;
			}

			if ( $short_title ) {
				$content = '<h6 class="short-title">' . Sanitize\decode( $short_title ) . '</h6>' . $content;
			}

			printf( $back_matter_printf,
				$subclass,
				$slug,
                pb_get_chapter_number($slug),
				Sanitize\decode( $title ),
				$content,
				$this->doEndnotes( $id ) );

			echo "\n";
		}

	}


	/**
	 * Does array of chapters have at least one export? Recursive.
	 *
	 * @param array $chapters
	 *
	 * @return bool
	 */
	protected function atLeastOneExport( array $chapters ) {

		foreach ( $chapters as $key => $val ) {
			if ( is_array( $val ) ) {
				$found = $this->atLeastOneExport( $val );
				if ( $found ) return true;
				else continue;
			} elseif ( 'export' == (string) $key && $val ) {
				return true;
			}
		}

		return false;
	}


    /**
     * Override based on Theme Options
     */
    protected function themeOptionsOverrides() {


        // --------------------------------------------------------------------
        // Hacks

        $hacks = array();
        $hacks = apply_filters( 'pb_pdf_hacks', $hacks );

        if ( @$hacks['lists_position'] ) {
            $this->listsPosition = @$hacks['lists_position'];
        }

    }

    /**
     * Pummel the HTML
     *
     * @param string $html
     * @param string $type front-matter, part, chapter, back-matter, ...
     * @param int $pos (optional) position of content, used when creating filenames like: chapter-001, chapter-002, ...
     *
     * @return string
     */
    protected function kneadHtml( $html ) {
        if(trim($html) == ''){
            return($html);
        }

        libxml_use_internal_errors( true );

        // Load HTMl snippet into DOMDocument using UTF-8 hack
        $utf8_hack = '<?xml version="1.0" encoding="UTF-8"?>';
        $doc = new \DOMDocument();
        $doc->loadHTML( $utf8_hack . $html );

        // Deal with <a href="">, <a href=''>, and other mutations
        $doc = $this->kneadHref( $doc );

        // If you are storing multi-byte characters in XML, then saving the XML using saveXML() will create problems.
        // Ie. It will spit out the characters converted in encoded format. Instead do the following:
        $html = $doc->saveXML( $doc->documentElement );

        // Remove auto-created <html> <body> and <!DOCTYPE> tags.
        $html = preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array( '<html>', '</html>', '<body>', '</body>' ), array( '', '', '', '' ), $html ) );

        $errors = libxml_get_errors(); // TODO: Handle errors gracefully
        libxml_clear_errors();

        return $html;
    }

    /**
     * Change hrefs
     *
     * @param \DOMDocument $doc
     * @return \DOMDocument
     */
    protected function kneadHref( \DOMDocument $doc ) {

        $urls = $doc->getElementsByTagName( 'a' );
        foreach ( $urls as $url ) {

            $current_url = '' . $url->getAttribute( 'href' ); // Stringify

            // Don't touch empty urls
            if ( ! trim( $current_url ) )
                continue;

            // Determine if we are trying to link to our own internal content
            $internal_url = $this->fuzzyHrefMatch( $current_url );
            if ( false !== $internal_url ) {
                $url->setAttribute( 'href', $internal_url );
                continue;
            }

            // Canonicalize, fix typos, remove garbage
            if ( '#' != @$current_url[0] ) {
                $url->setAttribute( 'href', \PressBooks\Sanitize\canonicalize_url( $current_url ) );
            }

        }

        return $doc;
    }

    /**
     * Try to determine if a URL is pointing to internal content.
     *
     * @param $url
     * @return bool|string
     */
    protected function fuzzyHrefMatch( $url ) {

        $purl = parse_url( $url );
        if(array_key_exists("host", $purl)){
            $domain2 = parse_url( wp_guess_url() );
            if ( $purl['host'] != @$domain2['host'] ) return false;
            if(!array_key_exists('path', $purl) && array_key_exists('path', $domain2)) return false;
            if(array_key_exists('path', $purl) && array_key_exists('path', $domain2) && 0 !== strpos($purl['path'], $domain2['path'])) return false;
        }
        if(array_key_exists('query', $purl)){
            parse_str($purl['query'], $params);
            if(array_key_exists("p", $params)){
                $post_name = pb_get_post_name($params["p"]);
                if($post_name){
                    $slug = $post_name;
                }else{
                    return false;
                }
            }
        }
        if(!isset($slug)){
            if(array_key_exists('path', $purl)){
                $path = explode( '/', $purl['path'] );
                if(count($path) > 0){
                    $slug = array_pop($path);
                    if(trim($slug) == ''){
                        if(count($path) > 0){
                            $slug = array_pop($path);
                        }else{
                            return false;
                        }
                    }
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }

        $lookup = \PressBooks\Book::getBookStructure();
        $lookup = $lookup['__export_lookup'];

        if ( ! array_key_exists($slug, $lookup ) )
            return false;



        if ( array_key_exists('fragment', $purl)){
            return "#".$purl['fragment'];
        }
        $new_url = "#".$slug;

        return $new_url;
    }

    /**
     * A Hook handler for the chapter number, do to the rearrangements in the front matters
     * @param $i the original number
     * @param $post_name the post name
     * @return int
     */
    function get_chapter_number($i, $post_name){

        $lookup = \PressBooks\Book::getBookStructure();
        $section = @$lookup['__export_lookup'][$post_name];

        // Special for loi and lot
        if($post_name == "loi"){
            if($this->listsPosition == 2){
                return 1;
            }else if($this->listsPosition == 1){
                $section = "front-matter";
            }
        }
        if($post_name == "lot"){
            if($this->listsPosition == 2){
                return 2;
            }else if($this->listsPosition == 1){
                $section = "front-matter";
            }
        }

        if($i == 0){
            return $i;
        }

        // Handle the different types
        if($section == 'chapter'){
            return $i;
        }else if($section == 'back-matter'){
            //If lists are in the back matter add to the number
            if($this->listsPosition == 2){
                return $i+2;
            }else{
                return $i;
            }
        }else if($section == 'front-matter'){
            //Handle the numbers if the lists ar in the front matter
            //The rearrangement does not need to be addressed, it is handled in the getBookStructure hook
            if($this->listsPosition == 1){
                $i = 0;
                foreach ( $lookup['front-matter'] as $chapter ) {
                    $p = get_post($chapter['ID']);
                    if(!$p){
                        return 0;
                    }
                    $type = pb_get_section_type( $p );
                    if ( $type !== 'numberless' && get_post_meta( $chapter['ID'], 'invisible-in-toc', true ) !== 'on')  $i++;
                    $subclass = \PressBooks\Taxonomy\front_matter_type( $chapter['ID'] );
                    if ( 'dedication' == $subclass || 'epigraph' == $subclass || 'title-page' == $subclass || 'before-title' == $subclass ){
                        if($chapter['post_name'] == $post_name){
                            return ($type !== 'numberless' && get_post_meta( $chapter['ID'], 'invisible-in-toc', true ) !== 'on') ? $i : 0;
                        }
                    }else{
                        if($chapter['post_name'] == $post_name){
                            return ($type !== 'numberless' && get_post_meta( $chapter['ID'], 'invisible-in-toc', true ) !== 'on') ? $i+2 : 0;
                        }
                        if($post_name == "loi"){
                            return $i;
                        }else if($post_name == "lot"){
                            return $i+1;
                        }
                    }
                }
            }else{
                return $i;
            }
        }
        return 0;
    }

    /**
     * Returns the section type of a post hook - for loi and lot
     * @param $section the default section
     * @param $post_name the post name
     * @return string
     */
    function get_chapter_number_section($section, $post_name){
        if($post_name == "loi" || $post_name == "lot"){
            if($this->listsPosition == 1){
                return "front-matter";
            }else if($this->listsPosition == 2){
                return "back-matter";
            }
        }
        return $section;
    }

    /**
     * Rearanges the book structure for the getBookStructure Hook. Needed because of the rearrangements in the front matter
     * @param $book_structure the original book structure
     * @return mixed
     */
    function getBookStructure($book_structure){

        $fm = $book_structure["front-matter"];
        $fmn = array();

        foreach ( $fm as $chapter ) {
            if ( ! $chapter['export'] )
                continue; // Skip
            $subclass = \PressBooks\Taxonomy\front_matter_type( $chapter['ID'] );
            if ( 'before-title' != $subclass )
                continue; //Skip
            $fmn[] = $chapter;
        }

        foreach ( array( 'dedication', 'epigraph' ) as $compare ) {
            foreach ( $fm as $chapter ) {
                if ( ! $chapter['export'] )
                    continue; // Skip
                $subclass = \PressBooks\Taxonomy\front_matter_type( $chapter['ID'] );
                if ( $compare != $subclass )
                    continue; //Skip
                $fmn[] = $chapter;
            }
        }

        foreach ( $fm as $chapter ) {
            if ( ! $chapter['export'] )
                continue; // Skip
            $subclass = \PressBooks\Taxonomy\front_matter_type( $chapter['ID'] );
            if ( 'dedication' == $subclass || 'epigraph' == $subclass || 'title-page' == $subclass || 'before-title' == $subclass )
                continue; // Skip
            $fmn[] = $chapter;
        }

        $book_structure["front-matter"] = $fmn;


        $c = $book_structure["chapter"];
        $cn = array();

        foreach ( $c as $chapter ) {
            if ( ! $chapter['export'] )
                continue; // Skip
            $cn[] = $chapter;
        }

        $book_structure["chapter"] = $cn;



        $bm = $book_structure["back-matter"];
        $bmn = array();

        foreach ( $bm as $chapter ) {
            if ( ! $chapter['export'] )
                continue; // Skip
            $bmn[] = $chapter;
        }

        $book_structure["back-matter"] = $bmn;

        return $book_structure;
    }

}
