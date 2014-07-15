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


    public $listtype;

	// ----------------------------------------------------------------------------------------------------------------
	// WordPress Overrides
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * Constructor, must call parent
	 */
	function __construct($listtype) {

		global $status, $page;

        $this->listtype = $listtype;

		$args = array(
			'singular' => 'list',
			'plural' => 'lists', // Parent will create bulk nonce: "bulk-{$plural}"
			'ajax' => true,
		);
		parent::__construct( $args );
	}

    function display() {

        wp_enqueue_script( 'lists-list-table', PB_PLUGIN_URL.'assets/js/pblistslisttable.js' );
        echo '<form id="lists-list-table-form" method="post">';
        parent::display();
        echo '</form>';
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

        $tagnames = \PressBooks\Lists\Lists::get_book_lists()[$this->listtype]->getTypes();
        if(is_array($tagnames)){
            $out = '<select name="pb_lists_list_type-'.$item["id"].'">';

                foreach( $tagnames as $tagname ){
                    $selected = '';
                    if( $tagname == $item["type"] ){
                        $selected = ' selected = "selected"';
                    }
                    $out .= '<option value="'.$tagname.'" '.$selected.'>'.$tagname.'</option>';
                }
            $out .= '</select>';
            return($out);
        }else{
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
            '<input type="checkbox" name="%1$s[]" value="%2$s" %3$s/>',
            /*$1%s*/
            "pb_lists_list_active",
            /*$2%s*/
            $item['id'], // The value of the checkbox should be the record's id
            $item['active'] ? "checked" : ""
        );
    }

    function column_number($item) {
        return ListNodeShow::get_number($item);
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


	/**
	 * This method dictates the table's columns and titles.
	 *
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	function get_columns() {

		$columns = array(
			'cb' => '<input type="checkbox" />', // Render a checkbox instead of text
			'id' => __( 'ID', 'pressbooks' ),
			'type' => __( 'Type', 'pressbooks' ),
			'number' => __( 'Number', 'pressbooks' ),
			'caption' => __( 'Caption', 'pressbooks' ),
			'active' => __( 'Active', 'pressbooks' ),
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
            'id' => array( 'id', false ),
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

		// Get data, sort
		$data = $this->getItemsData();
		$valid_cols = $this->get_sortable_columns();

		$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc'; // If no order, default to asc
        if ( isset( $_REQUEST['orderby'] ) && isset( $valid_cols[$_REQUEST['orderby']] ) ) {
            if($_REQUEST['orderby'] != "number"){
			    $data = \PressBooks\Utility\multi_sort( $data, "{$_REQUEST['orderby']}:$order", 'number:asc' );
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
            'listtype' => $this->listtype
        );
        printf( "<script type='text/javascript'>add_list_args = %s;</script>\n", json_encode( $args ) );
	}


	function ajax_response() {

		//parent::ajax_response();
        check_ajax_referer( 'ajax-lists-list-nonce', '_ajax_lists_list_nonce' );

        //TODO rights and check requestvars
        if($_REQUEST["change_action"] == "type" && $_REQUEST["change_value"] && $_REQUEST["change_id"]){
            \PressBooks\Lists\Lists::get_book_lists()[$this->listtype]->changeNodeType($_REQUEST["change_id"], $_REQUEST["change_value"]);
        }else if($_REQUEST["change_action"] == "active" && $_REQUEST["change_value"] && $_REQUEST["change_id"]){
            $value = filter_var($_REQUEST["change_value"], FILTER_VALIDATE_BOOLEAN);
            \PressBooks\Lists\Lists::get_book_lists()[$this->listtype]->setNodeActive($_REQUEST["change_id"], $value);
        }

        $response = $this->getItemsData();
        foreach($response as &$item){
            $item["number"] = ListNodeShow::get_number($item);
            $item["caption"] = ListNodeShow::get_caption($item);
        }
        die( json_encode( $response ) );
	}

    function process_bulk_action() {

        //TODO rights
        //Detect when a bulk action is being triggered...
        if( 'add'===$this->current_action() ) {
            foreach($_REQUEST[$this->_args['singular']] as $item) {
                \PressBooks\Lists\Lists::get_book_lists()[$this->listtype]->setNodeActive($item, true);
            }
        }else if( 'remove'===$this->current_action() ){
            foreach($_REQUEST[$this->_args['singular']] as $item) {
                \PressBooks\Lists\Lists::get_book_lists()[$this->listtype]->setNodeActive($item, false);
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
        $data = $bl[$this->listtype]->getFlatArray();

        return $data;
    }


}
