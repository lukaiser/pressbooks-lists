/**
 * Created by lukas on 11.07.14.
 */
(function($) {

    lists_list = {


        init: function() {

            $('td.column-type select').change(function(){
                var type = this.value;
                var id = $(this).attr("name");
                id = id.substr(id.indexOf("-") + 1);
                var data = {
                    change_action: "type",
                    change_value: type,
                    change_id: id
                };
                lists_list.update(data);
            });

            $('td.column-active input').change(function(){
                var id = this.value;
                var row = $(this).closest("tr");
                /* Promt if list item and chapter should be activated if the chapter is deactivated */
                if($(row).hasClass("chapterinactive")){
                    var chapterTitle = add_list_args.listdata[id]["chapterTitle"];
                    var promt = PBL10.chapter_activate_popup.split("%s");
                    promt = promt.join(chapterTitle);
                    if(!confirm(promt)){
                        $(this).prop('checked', false);
                        return;
                    }
                }
                var active = this.checked;
                var data = {
                    change_action: "active",
                    change_value: active,
                    change_id: id
                };
                lists_list.update(data);
            });

            /* Promt reverence link*/
            $('td.column-reverence a').click(function() {
                var promt = PBL10.copy_reverence_popup;
                var id = $(this).closest("tr").attr('id');
                id = id.substr(id.indexOf("-") + 1);
                window.prompt(promt, '[rev id="'+id+'"/]');
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

                    $("tr").each(function(){
                        var id = $(this).attr('id');
                        if(response[id]){
                            $(this).find(".column-number").text(response[id]["number"]);
                            if(response[id]["active"]){
                                $(this).removeClass("inactive");
                                $(this).find("td.column-active input").prop('checked', true);
                            }else{
                                $(this).addClass("inactive");
                                $(this).find("td.column-active input").prop('checked', false);
                            }
                            if(response[id]["chapterActive"]){
                                $(this).removeClass("chapterinactive");
                            }else if(response[id]["chapterActive"] == false){
                                $(this).addClass("chapterinactive");
                            }
                        }
                    });
                }
            });
        }
    }

// Show time!
    lists_list.init();

})(jQuery);
