var AIRTIME = (function(AIRTIME) {
	
	if (AIRTIME.library === undefined) {
        AIRTIME.library = {};
    }
    var mod = AIRTIME.library;
    
    //stored in format chosenItems[tabname] = object of chosen ids for the tab.
    var chosenItems = {},
    	LIB_SELECTED_CLASS = "lib-selected";
    
    function makeWebstreamDialog(html) {
		var $wsDialogEl = $(html);
		
		function removeDialog() {
    		$wsDialogEl.dialog("destroy");
        	$wsDialogEl.remove();
    	}
		
		function saveDialog() {
			var data = {
				name: $wsDialogEl.find("#ws_name").val(),
				hours: $wsDialogEl.find("#ws_hours").val(),
				mins: $wsDialogEl.find("#ws_mins").val(),
				description: $wsDialogEl.find("#ws_description").val(),
				url: $wsDialogEl.find("#ws_url").val(),
				id: $wsDialogEl.find("#ws_id").val(),
				format: "json"
			},
			url = baseUrl + "webstream/save";
			
			if (data.id === "") {
				delete data.id;
			}
			
			$.post(url, data, function(json) {
				
				if (json.errors) {
					$wsDialogEl.empty()
						.append($(json.html).unwrap());
				}
				else {
					removeDialog();
				}
			});
		}
		
		$wsDialogEl.dialog({	       
	        title: $.i18n._("Webstream"),
	        modal: true,
	        show: 'clip',
            hide: 'clip',
            width: 600,
            height: 350,
	        buttons: [
				{text: $.i18n._("Cancel"), class: "btn btn-small", click: removeDialog},
				{text: $.i18n._("Save"),  class: "btn btn-small btn-inverse", click: saveDialog}
			],
	        close: removeDialog
	    });
	}
    
    function buildEditMetadataDialog (html){
    	var $mdDialog = $(html);
		
		function removeDialog() {
			$mdDialog.dialog("destroy");
			$mdDialog.remove();
    	}
		
		function saveDialog() {
			var mediaId = $('#MDATA_ID').val(),
            	data = $("#edit-md-dialog form").serializeArray();
        
	        $.post(baseUrl+'library/edit-file-md', 
	        	{format: "json", id: mediaId, data: data}, 
	        	function(json) {
	        		
	        		if (json.errors) {
						$mdDialog
							.empty()
							.append($(json.html));
					}
					else {
						removeDialog();
					}
	        });
		}
         
		$mdDialog.dialog({
            title: $.i18n._("Edit Metadata"),
            width: 460,
            height: 660,
            buttons: [
  				{text: $.i18n._("Cancel"), class: "btn btn-small", click: removeDialog},
  				{text: $.i18n._("Save"),  class: "btn btn-small btn-inverse", click: saveDialog}
  			],
            modal: true,
            close: removeDialog
        });
    }
    	
    function createDatatable(config) {
    	
    	var table = $("#"+config.type + "_table").dataTable({
    		"aoColumns": config.columns,
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": config.source,
			"sAjaxDataProp": "media",
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
            //save the tables based on tableId
            "bStateSave": true,
            "fnStateSaveParams": function (oSettings, oData) {
                // remove oData components we don't want to save.
                delete oData.oSearch;
                delete oData.aoSearchCols;
            },
            "fnStateSave": function (oSettings, oData) {
                localStorage.setItem('datatables-'+ config.type, JSON.stringify(oData));
                
                $.ajax({
                    url: baseUrl+"usersettings/set-"+ config.type + "-datatable",
                    type: "POST",
                    data: {settings : oData, format: "json"},
                    dataType: "json"
                  });
                
                colReorderMap = oData.ColReorder;
            },
            "fnStateLoad": function fnLibStateLoad(oSettings) {
                var settings = localStorage.getItem('datatables-'+ config.type);
               
                try {
                    return JSON.parse(settings);
                } catch (e) {
                    return null;
                }
            },
            "fnStateLoadParams": function (oSettings, oData) {
                var i,
                    length,
                    a = oData.abVisCols;
                
                if (a) {
                    // putting serialized data back into the correct js type to make
                    // sure everything works properly.
                    for (i = 0, length = a.length; i < length; i++) {
                        if (typeof(a[i]) === "string") {
                            a[i] = (a[i] === "true") ? true : false;
                        } 
                    }
                }
                    
                a = oData.ColReorder;
                if (a) {
                    for (i = 0, length = a.length; i < length; i++) {
                        if (typeof(a[i]) === "string") {
                            a[i] = parseInt(a[i], 10);
                        }
                    }
                }
                
                oData.iEnd = parseInt(oData.iEnd, 10);
                oData.iLength = parseInt(oData.iLength, 10);
                oData.iStart = parseInt(oData.iStart, 10);
                oData.iCreate = parseInt(oData.iCreate, 10);
            },
			"oLanguage": datatables_dict,
			"aLengthMenu": [[5, 10, 15, 20, 25, 50, 100], [5, 10, 15, 20, 25, 50, 100]],
			"iDisplayLength": 25,
			"sPaginationType": "full_numbers",
			"bJQueryUI": true,
			"bAutoWidth": true,
			
			"sDom": 'Rl<"#library_display_type">f<"dt-process-rel"r><"H"<"library_toolbar"C>><"dataTables_scrolling"t><"F"ip>',
			
			"oColVis": {
				"sAlign": "right",
                "aiExclude": [ 0 ],
                "buttonText": $.i18n._("Show / hide columns"),
                //use this to show/hide advanced search fields.
                //"fnStateChange": setFilterElement
            },
            
            "oColReorder": {
                "iFixedColumns": 1
            },
            
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) {
				$(nRow).data("aData", aData);
	        }
		});
    	
    	table.fnSetFilteringDelay(350);
    }
    
    mod.downloadMedia = function(data) {
    	console.log("downloading media " + data.id);
    	
    	document.location.href = data.url;
    };
    
    mod.previewMedia = function(data) {
    	var mediaId = data.id;
    	
    	console.log("previewing media " + mediaId);
    	
    	AIRTIME.playerPreview.previewMedia(mediaId);
    };
    
    mod.editMetadata = function(data) {
    	
    	$.get(data.url, {format: "json"}, function(json){
            buildEditMetadataDialog(json.dialog);
        });
    };
    
    function sendContextMenuRequest(data) {
    	
    	console.log(data);
    	
    	if (data.callback !== undefined) {
    		mod[data.callback](data);
    	}
    }
    
    function getActiveTabId() {
    	var $tab = $("div.ui-tabs-panel").not(".ui-tabs-hide");
    	
    	return $tab.attr("id");
    }
    
    //$el is a select table row <tr>
    mod.addToChosen = function($el) {
        var data = $el.data('aData'),
        	tabId = getActiveTabId();
        
        
        if (chosenItems[tabId] === undefined) {
        	chosenItems[tabId] = {};
        }
        
        chosenItems[tabId][data.Id] = $el.data('aData');
    };
    
    //$el is a select table row <tr>
    mod.removeFromChosen = function($el) {
    	var data = $el.data('aData'),
    		tabId = getActiveTabId();
        
        // used to not keep dragged items selected.
        if (!$el.hasClass(LIB_SELECTED_CLASS)) {
            delete chosenItems[tabId][data.Id];
        }   
    };
    
    //$el is a select table row <tr>
    mod.highlightItem = function($el) {
        var $input = $el.find("input");
    
        $input.attr("checked", true);
        $el.addClass(LIB_SELECTED_CLASS);
    };
    
    //$el is a select table row <tr>
    mod.unHighlightItem = function($el) {
        var $input = $el.find("input");
    
        $input.attr("checked", false);
        $el.removeClass(LIB_SELECTED_CLASS);
    };
    
  //$el is a select table row <tr>
    mod.selectItem = function($el) {
        
        mod.highlightItem($el);
        mod.addToChosen($el);
        
        mod.checkToolBarIcons();
    };
    
  //$el is a select table row <tr>
    mod.deselectItem = function($el) {
        
        mod.unHighlightItem($el);
        mod.removeFromChosen($el);
        
        mod.checkToolBarIcons();
    };
    
    /*
     * selects all items which the user can currently see. (behaviour taken from
     * gmail)
     * 
     * by default the items are selected in reverse order so we need to reverse
     * it back
     */
    mod.selectCurrentPage = function() {
        $.fn.reverse = [].reverse;
        var $inputs = $libTable.find("tbody input:checkbox"),
            $trs = $inputs.parents("tr").reverse();
            
        $inputs.attr("checked", true);
        $trs.addClass(LIB_SELECTED_CLASS);

        $trs.each(function(i, el){
            $el = $(this);
            mod.addToChosen($el);
        });

        mod.checkToolBarIcons();     
    };
    
    /*
     * deselects all items that the user can currently see. (behaviour taken
     * from gmail)
     */
    mod.deselectCurrentPage = function() {
        var $inputs = $libTable.find("tbody input:checkbox"),
            $trs = $inputs.parents("tr"),
            id;
        
        $inputs.attr("checked", false);
        $trs.removeClass(LIB_SELECTED_CLASS);
        
        $trs.each(function(i, el){
            $el = $(this);
            id = $el.attr("id");
            delete chosenItems[id];
        });
        
        mod.checkToolBarIcons();     
    };
    
    mod.selectNone = function() {
        var $inputs = $libTable.find("tbody input:checkbox"),
            $trs = $inputs.parents("tr");
        
        $inputs.attr("checked", false);
        $trs.removeClass(LIB_SELECTED_CLASS);
        
        chosenItems = {};
        
        mod.checkToolBarIcons();
    };
    
    mod.createToolbarButtons = function() {
        var $menu = $("<div class='btn-toolbar' />");
        
        $menu
            .append("<div class='btn-group'>" +
                        "<button class='btn btn-small dropdown-toggle' data-toggle='dropdown'>" +
                            $.i18n._("Select")+" <span class='caret'></span>" +
                        "</button>" +
                        "<ul class='dropdown-menu'>" +
                            "<li id='sb-select-page'><a href='#'>"+$.i18n._("Select this page")+"</a></li>" +
                            "<li id='sb-dselect-page'><a href='#'>"+$.i18n._("Deselect this page")+"</a></li>" +
                            "<li id='sb-dselect-all'><a href='#'>"+$.i18n._("Deselect all")+"</a></li>" +
                        "</ul>" +
                    "</div>")
            .append("<div class='btn-group'>" +
                        "<button class='btn btn-small ui-state-disabled' disabled='disabled'>" +
                            "<i class='icon-white icon-plus'></i>" +
                            //"<span id='lib-plus-text'></span>" +
                        "</button>" +
                    "</div>")
            .append("<div class='btn-group'>" +
                        "<button class='btn btn-small ui-state-disabled' disabled='disabled'>" +
                            "<i class='icon-white icon-trash'></i>" +
                        "</button>" +
                    "</div>");
        
        return $menu;
    };
     
    mod.onReady = function () {
    	
    	var $library = $("#library_content"),
    		$body = $("body");

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
		    	source: baseUrl+"media/audio-file-feed",
		    	type: "audio"
		    },
		    "lib_webstreams": {
		    	initialized: false,
		    	initialize: function() {
		    		
		    	},
		    	navigate: function() {
		    		
		    	},
		    	always: function() {
		    		
		    	},
		    	localColumns: "datatables-webstream-aoColumns",
		    	source: baseUrl+"media/webstream-feed",
		    	type: "webstream"
		    },
		    "lib_playlists": {
		    	initialized: false,
		    	initialize: function() {
		    		
		    	},
		    	navigate: function() {
		    		
		    	},
		    	always: function() {
		    		
		    	},
		    	localColumns: "datatables-playlist-aoColumns",
		    	source: baseUrl+"media/playlist-feed",
		    	type: "playlist"
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
    					columns: columns,
    					prop: tab.dataprop,
    					source: tab.source,
    					type: tab.type
    				});
    				
    				mod.setupToolbar(ui.panel.id);
    				tab.initialized = true;
    			}
    			
    			tab.always();
			},
			select: function( event, ui ) {
				var x;
			}
    	});
    	
    	$library.on("click", "#lib_new_webstream", function(e) {
    		var url = baseUrl+"webstream/new/format/json";
    		
    		e.preventDefault();
    		e.stopPropagation();
    		
    		$.get(url, function(json) {
    			makeWebstreamDialog(json.html);
    		}, "json");
    	});
    	
    	$library.on("click", "#lib_new_playlist", function(e) {
    		var url = baseUrl+"playlist/new",
    			data = {format: "json"};
    		
    		$.post(url, data, function(json) {
    			AIRTIME.playlist.drawPlaylist(json);
    		});
    	});
    	
    	$library.on("click", "input[type=checkbox]", function(e) {
    		e.preventDefault();
    		e.stopPropagation();
            
            var $cb = $(this),
                $prev,
                $tr = $cb.parents("tr"),
                $trs;
            
            if ($cb.is(":checked")) {
                
                if (e.shiftKey) {
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
    	
    	$library.on("mousedown", 'td:not(.library_checkbox)', function(e) {
    		e.preventDefault();
    		e.stopPropagation();
    		
    		//only trigger context menu on right click.
    		if (e.which === 3) {
    			var $el = $(this);
    			
    			$el.contextMenu({x: e.pageX, y: e.pageY});
    		}
    	});
    	
    	//perform the double click action on an item row.
    	$library.on("dblclick", 'td:not(.library_checkbox)', function(e) {
    		e.preventDefault();
    		e.stopPropagation();
    		
    		var $el = $(this),
    			$tr,
    			data;
    		
    		$tr = $el.parent();
            data = $tr.data("aData");
            mod.dblClickAdd(data);
    	});
    	
    	//events for the edit metadata dialog
    	/*
        $('#edit-md-dialog').live("keyup", function(event) {
            if (event.keyCode === 13) {
                $('#editmdsave').click();
            }
        });
        */
        //end of events fo the edit metadata dialog.
    	
    	 // begin context menu initialization.
        $.contextMenu({
            selector: '#lib_tabs td',
            trigger: "none",
            ignoreRightClick: false,
            
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