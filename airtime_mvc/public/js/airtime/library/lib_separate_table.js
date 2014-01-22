var AIRTIME = (function(AIRTIME) {
	
	if (AIRTIME.library === undefined) {
        AIRTIME.library = {};
    }
    var mod = AIRTIME.library;
    
<<<<<<< HEAD
=======
    //stored in format chosenItems[tabname] = object of chosen ids for the tab.
    var chosenItems = {},
    	LIB_SELECTED_CLASS = "lib-selected",
    	//used for using dbclick vs click events on the library rows.
    	alreadyclicked = false,
    	alreadyclickedTimeout;
    
>>>>>>> CC-5450 : Refactor Media Management (Classes/DB) in Airtime
    function createDatatable(config) {
    	
    	$("#"+config.id).dataTable({
    		"aoColumns": config.columns,
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": config.source,
			"sAjaxDataProp": config.prop,
			"fnServerData": function ( sSource, aoData, fnCallback ) {
               
                aoData.push( { name: "format", value: "json"} );
               
                $.ajax( {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": fnCallback
                } );
            },
			"oLanguage": datatables_dict,
			"aLengthMenu": [[5, 10, 15, 20, 25, 50, 100], [5, 10, 15, 20, 25, 50, 100]],
			"iDisplayLength": 25,
			"sPaginationType": "full_numbers",
			"bJQueryUI": true,
			"bAutoWidth": true,
			"sDom": 'Rl<"#library_display_type">f<"dt-process-rel"r><"H"<"library_toolbar"C>><"dataTables_scrolling"t><"F"ip>',
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) {
	           $(nRow).data("aData", aData);
	        }
		});
    }
    
    function sendContextMenuRequest(data) {
    	
    	var callback = data.callback;
    	
    	data.requestData["format"] = "json";
    	
    	$.ajax({
            url: data.requestUrl,
            type: data.requestType,
            data: data.requestData,
            dataType: "json",
            async: false,
            success: function(json) {
            	
            	var f = callback.split("."),
            		i,
            		len,
            		obj = window;
            	
            	for (i = 0, len = f.length; i < len; i++) {
            		
            		obj = obj[f[i]];
            	}
            	
            	obj(json);
            }
        });
    }
     
    mod.onReady = function () {
    	
    	var $library = $("#library_content");

    	var tabsInit = {
    		"lib_audio": {
		    	initialized: false,
		    	initialize: function() {
		    		
		    	},
		    	navigate: function() {
		    		
		    	},
		    	always: function() {
		    		
		    	},
		    	localColumns: "datatables-audiofile-aoColumns",
		    	tableId: "audio_table",
		    	source: baseUrl+"media/audio-file-feed",
		    	dataprop: "audiofiles"
		    }
    	};

    	
    	$("#lib_tabs").tabs({
    		show: function( event, ui ) {
    			var tab = tabsInit[ui.panel.id];
    			
    			if (tab.initialized) {
    				
    			}
    			else {
    				
    				var columns = JSON.parse(localStorage.getItem(tab.localColumns));
    				createDatatable({
    					id: tab.tableId, 
    					columns: columns,
    					prop: tab.dataprop,
    					source: tab.source
    				});
    			}
    			
    			tab.always();
			},
			select: function( event, ui ) {
				var x;
			}
    	});
    	
    	$library.on("click", "#lib_new_playlist", function(e) {
    		var url = baseUrl+"playlist/new",
    			data = {format: "json"};
    		
    		$.post(url, data, function(json) {
    			AIRTIME.playlist.drawPlaylist(json);
    		});
    	});
    	
<<<<<<< HEAD
=======
    	$library.on("click", "input[type=checkbox]", function(ev) {
            
            var $cb = $(this),
                $prev,
                $tr = $cb.parents("tr"),
                $trs;
            
            if ($cb.is(":checked")) {
                
                if (ev.shiftKey) {
                    $prev = $library.find("tr."+LIB_SELECTED_CLASS+":visible").eq(-1);
                    $trs = $prev.nextUntil($tr);
                    
                    $trs.each(function(i, el){
                        mod.selectItem($(el));
                    });
                }

                mod.selectItem($tr);
            }
            else {
                mod.deselectItem($tr);  
            }
        });
    	
    	// call the context menu so we can prevent the event from
        // propagating.
    	$library.on("click", 'td:not(.library_checkbox)', function(e) {
    		
            var $el = $(this);
            
            if (mod.alreadyclicked) {
            	
            	// reset
            	mod.alreadyclicked = false;
                // prevent this from happening
                clearTimeout(mod.alreadyclickedTimeout); 
    
                // do what needs to happen on double click.
                $tr = $el.parent();
                data = $tr.data("aData");
                mod.dblClickAdd(data);
            }
            else
            {
            	mod.alreadyclicked = true;
            	mod.alreadyclickedTimeout = setTimeout(function() {
            		// reset when it happens
            		mod.alreadyclicked = false;
                    // do what needs to happen on single click.
                    $el.contextMenu({x: e.pageX, y: e.pageY});
                }, 200); // <-- dblclick tolerance here
            }
            return false;
        });
    	
>>>>>>> CC-5450 : Refactor Media Management (Classes/DB) in Airtime
    	 // begin context menu initialization.
        $.contextMenu({
            selector: '#lib_tabs td',
            trigger: "left",
            ignoreRightClick: true,
            
            build: function($el, e) {
                var data, items, $tr;
                
                $tr = $el.parent();
                data = $tr.data("aData");
                 
                $.ajax({
                  url: baseUrl+"library/context-menu",
                  type: "GET",
                  data: {id : data.Id, format: "json"},
                  dataType: "json",
                  async: false,
                  success: function(json) {
                      items = json.items;
                  }
                });
    
                return {
                    items: items,
                    callback: function(key, options) {
                        var m = "clicked: " + key;
                        window.console && console.log(m);
                        sendContextMenuRequest(options.commands[key]);
                    }
                };
            }
        });
    };

	return AIRTIME;
	
}(AIRTIME || {}));

$(document).ready(AIRTIME.library.onReady);