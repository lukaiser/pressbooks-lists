<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Lists;




if ( ! class_exists( 'WP_List_Table' ) ) {
	require( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * @see http://codex.wordpress.org/Class_Reference/WP_List_Table
 * Same structure as \PressBooks\Catalog_List_Table
 */
class Lists_List_Table extends \WP_List_Table {


    /**
     * Witch list
     * @var string
     */
    public $listtype;

    /**
     * If there should be displayed too
     * @var bool
     */
    public $displayChapter;

	// ----------------------------------------------------------------------------------------------------------------
	// WordPress Overrides
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * Constructor, must call parent
	 */
	function __construct($listtype, $displayChapter=false) {

		global $status, $page;

        $this->listtype = $listtype;
        $this->displayChapter = $displayChapter;

		$args = array(
			'singular' => 'list',
			'plural' => 'lists', // Parent will create bulk nonce: "bulk-{$plural}"
			'ajax' => true,
		);
		parent::__construct( $args );
	}

    function display() {
        wp_enqueue_style('qtip', PB_PLUGIN_URL.'assets/css/jquery.qtip.min.css', null, false, false);
        wp_enqueue_script('qtip', PB_PLUGIN_URL.'assets/js/jquery.qtip.min.js', array('jquery'), false, true);

        wp_enqueue_style( 'lists-list-table', PB_PLUGIN_URL.'assets/css/pblistslisttable.css' );
        wp_register_script( 'lists-list-table', PB_PLUGIN_URL.'assets/js/pblistslisttable.js' );
        $translation_array = array( 'chapter_activate_popup' => __( 'This list item is inactive because the containing chapter "%s" is not in the table of content. Do you want to activate both?', 'pressbooks' ),  'copy_reference_popup' => __( 'Copy to clipboard: Ctrl+C, Enter', 'pressbooks' ));
        wp_localize_script( 'lists-list-table', 'PBL10', $translation_array );
        wp_enqueue_script( 'lists-list-table' );

        //Info panel if chapter numbers are hidden
        $options = get_option( 'pressbooks_theme_options_global' );
        if($this->listtype == "h" && !@$options['chapter_numbers']){
            $url = get_bloginfo( 'url' ) . '/wp-admin/themes.php?page=pressbooks_theme_options';
            printf('<div class="pressbooks-admin-panel chapter-number-panel"><div><div class="dashicons dashicons-info"></div>%1$s</div></div>', sprintf(__( 'Chapter numbers are disabled in the <a href="%s">Theme Options</a>', 'pressbooks' ), $url));
        }

        //CSS class for the current selected ordering
        $orderby = "";
        $order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
        if ( (isset( $_REQUEST['orderby'] ) && $_REQUEST['orderby'] == "number") || !isset( $_REQUEST['orderby']) ) {
            $orderby = ' class="order-number-'.$order.'"';
        }

        echo '<form id="lists-list-table-form" method="post"'.$orderby.'>';
        parent::display();
        echo '</form>';
    }

    /**
    * Generates content for a single row of the table
    *
    * @since 3.1.0
    * @access protected
    *
    * @param object $item The current item
    */
    function single_row( $item ) {
        //CSS classes representing the state of the list item and type
        static $row_class = '';
        $row_class = ( $row_class == '' ? ' alternate' : '' );
        $row_classes = $item["type"];
        if(array_key_exists("chapterActive", $item)){
            $row_classes .= $item["chapterActive"] ? '' : ' chapterinactive';
        }
        $row_classes .= $item["active"] ? '' : ' inactive';
        $row_classes .= $row_class;
        if(isset($item["lastFrontMatter"])){
            $row_classes .= " lastFrontMatter";
        }
        if(isset($item["firstBackMatter"])){
            $row_classes .= " firstBackMatter";
        }
        $row_classes = ' class="'.$row_classes.'"';
        $row_id = ' id="'.$item["id"].'"';

        echo '<tr' . $row_classes . $row_id . '>';
        $this->single_row_columns( $item );
        echo '</tr>';
    }


	/**
	 * This method is called when the parent class can't find a method
	 * for a given column. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title()
	 * exists. If it doesn't this one will be used.
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 *
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	function column_default( $item, $column_name ) {

		return esc_html( $item[$column_name] );
	}

    function column_type($item){
        if($item["type"] == "chapter" || $item["type"] == "front-matter" || $item["type"] == "back-matter" || $item["type"] == "part"){
            return $this->get_nice_type($item["type"]);
        }
        $tagnames = \PressBooks\Lists\Lists::get_book_lists()[$this->listtype]->getTypes();
        if(is_array($tagnames)){
            $out = '<select name="pb_lists_list_type-'.$item["id"].'">';

                foreach( $tagnames as $tagname ){
                    $selected = '';
                    if( $tagname == $item["type"] ){
                        $selected = ' selected = "selected"';
                    }
                    $tagtitle = $this->get_nice_type($tagname);
                    if($tagtitle == ""){
                        $tagtitle = $tagname;
                    }
                    $out .= '<option value="'.$tagname.'" '.$selected.'>'.$tagtitle.'</option>';
                }
            $out .= '</select>';
            return($out);
        }else{
            $name = $this->get_nice_type($item["type"]);
            if($name != ""){
                return $name;
            }
            return $item["type"];
        }
    }

    function column_caption($item){

        /*return sprintf(
            '<input name="%1$s%2$s" type="text" value="%3$s">',
            //$1%s
            "pb_lists_list_caption_", // Let's simply repurpose the table's singular label ("book")
            //$2%s
            $item['id'], // The value of the checkbox should be the record's id
            $item['caption']
        );*/
        return ListNodeShow::get_caption($item);
    }

    function column_active($item) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" class="%4$s" %3$s/>',
            /*$1%s*/
            "pb_lists_list_active",
            /*$2%s*/
            $item['id'], // The value of the checkbox should be the record's id
            $item['active'] ? "checked" : "",
            "type-".$item["type"]
        );
    }

    function column_number($item) {
        $options = get_option( 'pressbooks_theme_options_global' );
        if($this->listtype == "h" && !@$options['chapter_numbers']){
            return '<span class="dashicons dashicons-info" title="'.__( 'Chapter numbers are disabled in the Theme Options', 'pressbooks' ).'"></a>';
        }
        if(!Lists::add_numbers_to_list_elements() && $item["type"] != "chapter" && $item["type"] != "part" && $item["type"] != "front-matter" && $item["type"] != "back-matter"){
            return '<span class="dashicons dashicons-info" title="'.sprintf(__( 'The theme you have selected displays no number for %1$s.', 'pressbooks' ), $this->get_nice_type($item["type"])).'"></a>';
        }
        $hlevel = Lists::add_numbers_to_heading_levels();
        if (($item["type"] == "h1" && $hlevel < 1) || ($item["type"] == "h2" && $hlevel < 2) || ($item["type"] == "h3" && $hlevel < 3) || ($item["type"] == "h4" && $hlevel < 4) || ($item["type"] == "h5" && $hlevel < 5) || ($item["type"] == "h6" && $hlevel < 6)){
            return '<span class="dashicons dashicons-info" title="'.sprintf(__( 'The theme you have selected displays no number for %1$s.', 'pressbooks' ), $this->get_nice_type($item["type"])).'"></a>';
        }
        if(!$item["active"]){
            return "";
        }
        return ListNodeShow::get_number($item);
    }

    function column_reference($item) {
        return '<a class="dashicons dashicons-admin-links" title="'.__( 'Get Reference Shortcode', 'pressbooks' ).'"></a>';
    }





	/**
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 *
	 * @return string Text to be placed inside the column <td>
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/
			$this->_args['singular'], // Let's simply repurpose the table's singular label ("book")
			/*$2%s*/
            $item["id"] // The value of the checkbox should be the record's id
		);
	}

    function get_nice_type($type){
        if($type == "h1"){
            return __( 'Heading 1', 'pressbooks' );
        }elseif($type == "h2"){
            return __( 'Heading 2', 'pressbooks' );
        }elseif($type == "h3"){
            return  __( 'Heading 3', 'pressbooks' );
        }elseif($type == "h4"){
            return  __( 'Heading 4', 'pressbooks' );
        }elseif($type == "h5"){
            return  __( 'Heading 5', 'pressbooks' );
        }elseif($type == "h6"){
            return  __( 'Heading 6', 'pressbooks' );
        }else if($type == "img"){
            return __( 'Image', 'pressbooks' );
        }elseif($type == "table"){
            return __( 'Table', 'pressbooks' );
        }else if($type == "chapter"){
            return __( 'Chapter', 'pressbooks' );
        }else if($type == "front-matter"){
            return __( 'Front-Matter', 'pressbooks' );
        }else if($type == "back-matter"){
            return __( 'Back-Matter', 'pressbooks' );
        }else if($type == "part"){
            return __( 'Part', 'pressbooks' );
        }
        return "";
    }

    function extra_tablenav( $which ) {
        if($this->listtype == "h"){
            if ( $which == "top" ){
                $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
                $current_url = remove_query_arg( 'heading-filter', $current_url );
                $toc = pb_headings_to_toc();
                $selected = array_key_exists('heading-filter', $_GET) ? $_GET['heading-filter'] : $toc;
                $selected = $selected == 0 ? 1: $selected;
                echo '<div class="filter">';
                    echo'<lable>'.__( 'Show: ', 'pressbooks' ).'</lable>';
                    echo'<select name="heading-filter" class="heading-filter">';
                        $url = add_query_arg('heading-filter', '1', $current_url );
                        echo '<option value="'.$url.'"'.($selected == 1 ? ' selected = "selected"' : '').'>'.__( 'Heading 1', 'pressbooks' ).($toc == 1?' ('.__( 'Output', 'pressbooks' ).')':'').'</option>';
                        $url = add_query_arg('heading-filter', '2', $current_url );
                        echo '<option value="'.$url.'"'.($selected == 2 ? ' selected = "selected"' : '').'>'.__( 'Heading 2 and higher', 'pressbooks' ).($toc == 2?' ('.__( 'Output', 'pressbooks' ).')':'').'</option>';
                        $url = add_query_arg('heading-filter', '3', $current_url );
                        echo '<option value="'.$url.'"'.($selected == 3 ? ' selected = "selected"' : '').'>'.__( 'Heading 3 and higher', 'pressbooks' ).($toc == 3?' ('.__( 'Output', 'pressbooks' ).')':'').'</option>';
                        $url = add_query_arg('heading-filter', '4', $current_url );
                        echo '<option value="'.$url.'"'.($selected == 4 ? ' selected = "selected"' : '').'>'.__( 'Heading 4 and higher', 'pressbooks' ).($toc == 4?' ('.__( 'Output', 'pressbooks' ).')':'').'</option>';
                        $url = add_query_arg('heading-filter', '5', $current_url );
                        echo '<option value="'.$url.'"'.($selected == 5 ? ' selected = "selected"' : '').'>'.__( 'Heading 5 and higher', 'pressbooks' ).($toc == 5?' ('.__( 'Output', 'pressbooks' ).')':'').'</option>';
                        $url = add_query_arg('heading-filter', '6', $current_url );
                        echo '<option value="'.$url.'"'.($selected == 6 ? ' selected = "selected"' : '').'>'.__( 'Heading 6 and higher', 'pressbooks' ).($toc == 6?' ('.__( 'Output', 'pressbooks' ).')':'').'</option>';
                    echo '</select>';
                echo '</div>';
            }
        }
    }


	/**
	 * This method dictates the table's columns and titles.
	 *
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	function get_columns() {

		$columns = array(
			'cb' => '<input type="checkbox" />', // Render a checkbox instead of text
            'number' => __( 'Number', 'pressbooks' ),
            'caption' => __( 'Title', 'pressbooks' ),
            'type' => __( 'Type', 'pressbooks' ),
			//'id' => __( 'ID', 'pressbooks' ),
			'active' => __( 'In List', 'pressbooks' ),
            'reference' => __( 'Reference', 'pressbooks' ),
		);

		return $columns;
	}


	/**
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting.
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 */
	function get_sortable_columns() {

		$sortable_columns = array(
//            'id' => array( 'id', false ),
			'type' => array( 'type', false ),
			'number' => array( 'number', false ),
			'caption' => array( 'caption', false ),
			'active' => array( 'active', false )
		);

		return $sortable_columns;
	}



	/**
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	function get_bulk_actions() {

		$actions = array(
			'add' => __( 'Show in List', 'pressbooks' ),
			'remove' => __( 'Hide in List', 'pressbooks' ),
		);

		return $actions;
	}


	/**
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args()
	 */
	function prepare_items() {

        $this->process_bulk_action();
		// Define Columns
		$columns = $this->get_columns();
		$hidden = $this->getHiddenColumns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Get data, filter
		$data = $this->getItemsData();
        if($this->listtype == "h"){
            $hlevel = array_key_exists('heading-filter', $_GET) ? $_GET['heading-filter'] : pb_headings_to_toc();
            $hlevel = $hlevel == 0 ? 1: $hlevel;
            $data2 = array();
            foreach($data as $item){
                if(($item["type"] != "h1" && $item["type"] != "h2" && $item["type"] != "h3" && $item["type"] != "h4" && $item["type"] != "h5" && $item["type"] != "h6")
                    || ($item["type"] == "h1" && $hlevel >= 1) || ($item["type"] == "h2" && $hlevel >= 2) || ($item["type"] == "h3" && $hlevel >= 3) || ($item["type"] == "h4" && $hlevel >= 4) || ($item["type"] == "h5" && $hlevel >= 5) || ($item["type"] == "h6" && $hlevel >= 6)){
                    $data2[] = $item;
                }
            }
            $data = $data2;
        }

        // sort
		$valid_cols = $this->get_sortable_columns();

		$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc'; // If no order, default to asc
        if ( isset( $_REQUEST['orderby'] ) && isset( $valid_cols[$_REQUEST['orderby']] ) ) {
            if($_REQUEST['orderby'] != "number"){
			    $data = \PressBooks\Utility\multi_sort( $data, "{$_REQUEST['orderby']}:$order" );
            }else if($order == "desc"){
                $data = array_reverse($data);
            }
		}

		// Pagination
		$per_page = 1000;
		$current_page = $this->get_pagenum();
		$total_items = count( $data );

		/* The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );


		/* REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;

		/* REQUIRED. We also have to register our pagination options & calculations.
		 */
		$args = array(
			'total_items' => $total_items, // WE have to calculate the total number of items
			'per_page' => $per_page, // WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page ) // WE have to calculate the total number of pages
		);
		$this->set_pagination_args( $args );

	}


	/**
	 * Form is POST not GET. Override parent method to compensate.
	 *
	 * @param bool $with_id
	 */
	function print_column_headers( $with_id = true ) {

		if ( empty( $_GET['s'] ) && ! empty( $_POST['s'] ) )
			$_SERVER['REQUEST_URI'] = add_query_arg( 's', $_POST['s'] );

		if ( empty( $_GET['orderby'] ) && ! empty( $_POST['orderby'] ) )
			$_GET['orderby'] = $_POST['orderby'];

		if ( empty( $_GET['order'] ) && ! empty( $_POST['order'] ) )
			$_GET['order'] = $_POST['order'];

		parent::print_column_headers( $with_id );
	}


	function _js_vars() {

		parent::_js_vars();
        $args = array(
            'nonce'  => wp_create_nonce("ajax-lists-list-nonce"),
            'listtype' => $this->listtype,
            'listdata' => $this->getItemsData(),
        );
        printf( "<script type='text/javascript'>add_list_args = %s;</script>\n", json_encode( $args ) );
	}


	function ajax_response() {

		//parent::ajax_response();
        check_ajax_referer( 'ajax-lists-list-nonce', '_ajax_lists_list_nonce' );
        if(!current_user_can("edit_posts")){
            die( json_encode( "No rights!" ) );
        }

        if($_REQUEST["change_id"]){
            list($type, $id) = explode('-', $_REQUEST["change_id"], 2);
        }

        if($_REQUEST["change_action"] == "type" && $_REQUEST["change_value"] && $_REQUEST["change_id"]){
            \PressBooks\Lists\Lists::get_book_lists()[$this->listtype]->changeNodeType($id, $_REQUEST["change_value"]);
        }else if($_REQUEST["change_action"] == "active" && $_REQUEST["change_value"] && $_REQUEST["change_id"]){
            //Handle list item activation change
            if($type == "n"){
                $value = filter_var($_REQUEST["change_value"], FILTER_VALIDATE_BOOLEAN);
                $list = \PressBooks\Lists\Lists::get_book_lists()[$this->listtype];
                $list->setNodeActive($id, $value);
                //if chapter is inactive and a list item of it is activated, the chapter gets activated too
                $node = $list->getNodeById($id);
                if(get_post_meta( $node->pid, 'invisible-in-toc', true ) == 'on' && $value){
                    $id = $node->pid;
                    $type = "c";
                }
            }
            //handle chapter activation change
            if($type == "c"){
                $value = filter_var($_REQUEST["change_value"], FILTER_VALIDATE_BOOLEAN);
                $value = $value ? '':'on';
                update_post_meta( $id, 'invisible-in-toc', $value );
            }
            //handle part activation change
            if($type == "p"){
                $value = filter_var($_REQUEST["change_value"], FILTER_VALIDATE_BOOLEAN);
                $value = $value ? '':'on';
                update_post_meta( $id, 'pb_part_invisible', $value );
            }
        }

        $response = $this->getItemsData();
        $hlevel = Lists::add_numbers_to_heading_levels();
        $options = get_option( 'pressbooks_theme_options_global' );
        foreach($response as &$item){
            if($this->listtype == "h" && !@$options['chapter_numbers']){
                $item["number"] = '<span class="dashicons dashicons-info" title="'.__( 'Chapter numbers are disabled in the Theme Options', 'pressbooks' ).'"></a>';
            }else if(!Lists::add_numbers_to_list_elements() && $item["type"] != "chapter" && $item["type"] != "part" && $item["type"] != "front-matter" && $item["type"] != "back-matter"){
                $item["number"] = '<span class="dashicons dashicons-info" title="'.sprintf(__( 'The theme you have selected displays no number for %1$s.', 'pressbooks' ), $this->get_nice_type($item["type"])).'"></a>';
            }else if (($item["type"] == "h1" && $hlevel < 1) || ($item["type"] == "h2" && $hlevel < 2) || ($item["type"] == "h3" && $hlevel < 3) || ($item["type"] == "h4" && $hlevel < 4) || ($item["type"] == "h5" && $hlevel < 5) || ($item["type"] == "h6" && $hlevel < 6)){
                $item["number"] = '<span class="dashicons dashicons-info" title="'.sprintf(__( 'The theme you have selected displays no number for %1$s.', 'pressbooks' ), $this->get_nice_type($item["type"])).'"></a>';
            }else if($item["active"]){
                $item["number"] = ListNodeShow::get_number($item);
            }else{
                $item["number"] = "";
            }
            $item["caption"] = ListNodeShow::get_caption($item);
        }
        die( json_encode( $response ) );
	}

    function process_bulk_action() {

        if(!current_user_can("edit_posts")){
            return;
        }
        //Detect when a bulk action is being triggered...
        if( 'add'===$this->current_action() ) {
            foreach($_REQUEST[$this->_args['singular']] as $item) {
                list($type, $id) = explode('-', $item, 2);
                if($type == "n"){
                    \PressBooks\Lists\Lists::get_book_lists()[$this->listtype]->setNodeActive($id, true);
                }
                if($type == "c"){
                    update_post_meta( $id, 'invisible-in-toc', '' );
                }
                if($type == "p"){
                    update_post_meta( $id, 'pb_part_invisible', '' );
                }
            }
        }else if( 'remove'===$this->current_action() ){
            foreach($_REQUEST[$this->_args['singular']] as $item) {
                list($type, $id) = explode('-', $item, 2);
                if($type == "n"){
                    \PressBooks\Lists\Lists::get_book_lists()[$this->listtype]->setNodeActive($id, false);
                }
                if($type == "c"){
                    update_post_meta( $id, 'invisible-in-toc', 'on' );
                }
                if($type == "p"){
                    update_post_meta( $id, 'pb_part_invisible', 'on' );
                }
            }
        }

    }


    /**
     * TODO: This isn't well documented, not sure i'm doing it right...
     *
     * @return array
     */
    protected function getHiddenColumns() {

        $hidden_columns = array(
            'featured',
        );

        return $hidden_columns;
    }

    /**
     * @return array
     */
    protected function getItemsData() {
        $bl = \PressBooks\Lists\Lists::get_book_lists(true);
        if($this->displayChapter){
            $data = $bl[$this->listtype]->getFlatArrayWithChapter();
        }else{
            $data = $bl[$this->listtype]->getFlatArray();
        }
        $book_structure = \PressBooks\Book::getBookStructure();
        $hasParts = (count( $book_structure['part'] ) > 1 );
        $out = array();
        $lastChapter = false;
        $i = 0;
        foreach($data as $item){
            //add data for chapters and parts
            if($item["type"] == "chapter" || $item["type"] == "part" || $item["type"] == "front-matter" || $item["type"] == "back-matter"){
                if($item["type"] != "part"){
                    $item["id"] = "c-".$item['pid'];
                    $item["active"] = get_post_meta( $item['pid'], 'invisible-in-toc', true ) !== 'on';
                    $lastChapter = $item;
                    $out[$item["id"]] = $item;
                }else{
                    if($hasParts){
                        $item["id"] = "p-".$item['pid'];
                        $out[$item["id"]] = $item;
                    }
                }
            }else{
                // Add information about the chapter to list items
                if(!$lastChapter || $item["pid"] != $lastChapter["pid"]){
                    $lastChapter = array();
                    $lastChapter["pid"] = $item['pid'];
                    $lastChapter["active"] = get_post_meta( $item['pid'], 'invisible-in-toc', true ) !== 'on';
                    $p = get_post($item["pid"]);
                    $lastChapter["caption"] = $p->post_title;
                }
                $item["chapterActive"] = $lastChapter["active"];
                if(!$lastChapter["active"]){
                    $item["active"] = false;
                }
                $item["chapterTitle"] = $lastChapter["caption"];
                $item["id"] = "n-".$item["id"];
                $out[$item["id"]] = $item;
            }

        }

        //identify the last front matter and the first back matter for css properties
        $lastFront = false;
        $lastFrontKey = "";
        foreach($out as $key => &$item){
            if(!$lastFront){
                if($item["type"] != "part" && $item["type"] != "chapter"){
                    $lastFrontKey = $key;
                }else{
                    if($lastFrontKey != ""){
                        $out[$lastFrontKey]["lastFrontMatter"] = true;
                    }
                    $lastFront = true;
                }
            }
            if($item["type"] == "back-matter"){
                $item["firstBackMatter"] = true;
                break;
            }
        }

        return $out;
    }


}
