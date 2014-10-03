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
                    var pid = add_list_args.listdata[id]["pid"];
                    pid = "c-"+pid;
                    var chapterTitle = add_list_args.listdata[pid]["caption"];
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

            /* Promt reference link*/
            $('td.column-reference a').click(function() {
                var promt = PBL10.copy_reference_popup;
                var id = $(this).closest("tr").attr('id');
                var parts = id.split("-", 2);
                if(parts[0] == "c" || parts[0] == "p"){
                    id = "p-"+parts[1];
                }else{
                    id = parts[1];
                }
                window.prompt(promt, '[ref id="'+id+'"/]');
            });

            $('.dashicons-info').qtip();

            $('.heading-filter').live('change', function(){
                var headingFilter = $(this).val();
                if( headingFilter != '' ){
                    document.location.href = headingFilter;
                }
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
                }
            });

            var id = data.change_id;
            if(data.change_action == "type"){
                add_list_args.listdata[id]["type"] = data.change_value;
            }else if(data.change_action == "active"){
                add_list_args.listdata[id]["active"] = data.change_value;
                if(data.change_value){
                    var type = add_list_args.listdata[id]["type"]
                    if(type != "chapter" && type != "part" && type != "front-matter" && type != "back-matter"){
                        var pid = add_list_args.listdata[id]["pid"];
                        pid = "c-"+pid;
                        add_list_args.listdata[pid]["active"] = true;
                    }
                }
            }
            this.recalculateNumbers();
        },
        recalculateNumbers: function(){
            var cna = [];
            cna[cna.length] = 0;
            $.each( add_list_args.types, function( key, value ) {
                cna[cna.length] = 0;
            });
            var partNumber = 0;
            var onGoingNumber = 0;
            var self = this;
            var firstchapter = false;
            var firstbackmatter = false;
            $.each( add_list_args.listdata, function( key, value ) {
                if( add_list_args.ongoingnumbering ){
                    if(value["active"] && value["type"] != "front-matter"  && value["type"] != "chapter" && value["type"] != "part" && value["type"] != "back-matter"){
                        onGoingNumber ++;
                        value["number"] = onGoingNumber;
                    }else{
                        value["number"] = "";
                    }
                }else{
                    if(value["type"] == "part"){
                        if(value["active"]){
                            partNumber ++;
                            value["number"] = partNumber;
                        }else{
                            value["number"] = "";
                        }
                    }else{
                        if(value["active"] && add_list_args.listdata["c-"+value["pid"]]["active"]){
                            if(!firstchapter && value["type"] == "chapter"){
                                cna[0] = 0;
                                firstchapter = true;
                            }
                            if(!firstbackmatter && value["type"] == "back-matter"){
                                cna[0] = 0;
                                firstbackmatter = true;
                            }
                            var nn = self.getDepthOfTagname(value["type"]);
                            cna[nn] ++;
                            for(var i = nn+1; i < 7; i++){
                                cna[i] = 0;
                            }
                            var cnan = cna.slice(0, nn+1);
                            if(value["type"] != "front-matter"  && value["type"] != "chapter" && value["type"] != "part" && value["type"] != "back-matter"){
                                cnan[0] = add_list_args.listdata["c-"+value["pid"]]["number"];
                            }else{
                                if(value["type"] == "front-matter"){
                                    cnan[0] = self.romanize(cnan[0]);
                                }else if(value["type"] == "back-matter"){
                                    cnan[0] = self.abcize(cnan[0]);
                                }
                            }
                            value["number"] = cnan.join(".");
                        }else{
                            value["number"] = "";
                        }
                    }
                }
            });
            $("tr").each(function(){
                var id = $(this).attr('id');
                if(add_list_args.listdata[id]){
                    if($(this).find(".column-number").html().indexOf("perma") == -1){
                        if ((add_list_args.listdata[id]["type"] == "h1" && add_list_args.hlevel < 1) || (add_list_args.listdata[id]["type"] == "h2" && add_list_args.hlevel < 2) || (add_list_args.listdata[id]["type"] == "h3" && add_list_args.hlevel < 3) || (add_list_args.listdata[id]["type"] == "h4" && add_list_args.hlevel < 4) || (add_list_args.listdata[id]["type"] == "h5" && add_list_args.hlevel < 5) || (add_list_args.listdata[id]["type"] == "h6" && add_list_args.hlevel < 6)){
                            var type = PBL10[add_list_args.listdata[id]["type"]];
                            var promt = PBL10.not_displayed.split("%s");
                            promt = promt.join(type);
                            $(this).find(".column-number").html('<span class="dashicons dashicons-info" title="'+promt+'"></a>');
                        }else if(!add_list_args.addNumbers && add_list_args.listdata[id]['type'] != 'front-matter' && add_list_args.listdata[id]['type'] != 'chapter' && add_list_args.listdata[id]['type'] != 'back-matter' && add_list_args.listdata[id]['type'] != 'part'){
                            var type = PBL10[add_list_args.listdata[id]["type"]];
                            var promt = PBL10.not_displayed.split("%s");
                            promt = promt.join(type);
                            $(this).find(".column-number").html('<span class="dashicons dashicons-info" title="'+promt+'"></a>');
                        }else{
                            $(this).find(".column-number").html(add_list_args.listdata[id]["number"]);
                        }
                    }
                    if(add_list_args.listdata[id]["active"] && (add_list_args.listdata[id]['type'] == 'part' || add_list_args.listdata["c-"+add_list_args.listdata[id]["pid"]]["active"])){
                        $(this).removeClass("inactive");
                        $(this).find("td.column-active input").prop('checked', true);
                    }else{
                        $(this).addClass("inactive");
                        $(this).find("td.column-active input").prop('checked', false);
                    }
                    if(add_list_args.listdata[id]['type'] != 'front-matter' && add_list_args.listdata[id]['type'] != 'chapter' && add_list_args.listdata[id]['type'] != 'back-matter' && add_list_args.listdata[id]['type'] != 'part'){
                        if(add_list_args.listdata["c-"+add_list_args.listdata[id]["pid"]]["active"]){
                            $(this).removeClass("chapterinactive");
                        }else{
                            $(this).addClass("chapterinactive");
                        }
                    }
                }
            });
            $('.dashicons-info').qtip();
        },
        getDepthOfTagname: function(type){
            if(type == "front-matter" || type == "chapter" || type == "back-matter"){
                return(0);
            }
            return(add_list_args.types.indexOf(type)+1);
        },
        romanize: function(n) {
            var r = '',
                decimals = [1000, 900, 500, 400, 100, 90, 50, 40, 10, 9, 5, 4, 1],
                roman = ['M', 'CM', 'D', 'CD', 'C', 'XC', 'L', 'XL', 'X', 'IX', 'V', 'IV', 'I'];
            for (var i = 0; i < decimals.length; i++) {
                while (n >= decimals[i]) {
                    r += roman[i];
                    n -= decimals[i];
                }
            }
            return r;
        },
        abcize: function (n) {
            var s = "";
            while(n >= 0) {
                s = String.fromCharCode(n % 26 + 64) + s;
                n = Math.floor(n / 26) - 1;
            }
            return s;
        }

    }

// Show time!
    lists_list.init();
    $(window).load(function() {
        $(".loader").fadeOut("slow");
    })

})(jQuery);
