/**
 * Created by lukas on 11.07.14.
 */
(function($) {

    lists_list = {


        init: function() {

            $('td.column-type select').change(function(){
                var type = this.value;
                var id = $(this).attr("name");
                id = id.substr(id.indexOf("-") + 1)
                var data = {
                    change_action: "type",
                    change_value: type,
                    change_id: id
                };
                lists_list.update(data);
            });

            $('td.column-active input').change(function(){
                var active = this.checked;
                var id = this.value;
                var data = {
                    change_action: "active",
                    change_value: active,
                    change_id: id
                };
                lists_list.update(data);
            });


        },

        /** AJAX call
         *
         * Send the call and replace table parts with updated version!
         *
         * @param    object    data The data to pass through AJAX
         */
        update: function( data ) {
            $.ajax({
                // /wp-admin/admin-ajax.php
                url: ajaxurl,
                // Add action and nonce to our collected data
                data: $.extend(
                    {
                        _ajax_lists_list_nonce: add_list_args.nonce,
                        list_type: add_list_args.listtype,
                        action: '_ajax_fetch_lists_list',
                        hook_suffix: list_args.screen.id
                    },
                    data
                ),
                // Handle the successful result
                success: function( response ) {

                    // WP_List_Table::ajax_response() returns json
                    var response = $.parseJSON( response );

                    $("td.column-id").each(function(){
                        var id = $(this).text();
                        if(response[id]){
                            $(this).siblings(".column-number").text(response[id]["number"]);
                        }
                    });
                }
            });
        }
    }

// Show time!
    lists_list.init();

})(jQuery);
