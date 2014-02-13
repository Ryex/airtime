var AIRTIME = (function(AIRTIME) {
    var mod;

    if (AIRTIME.library === undefined) {
        AIRTIME.library = {};
    }

    mod = AIRTIME.library;
    
    mod.setupToolbar = function(tabId) {
        var $toolbar = $("#"+tabId+" .fg-toolbar:first"),
        	$menu = mod.createToolbarButtons();

        $toolbar.append($menu);
        
    };
    
    mod.checkAddButton = function(tabId) {
    	
    };
    
    mod.checkDeleteButton = function(tabId) {
    	
    };
    
    mod.checkToolBarIcons = function(tabId) {
    	
    	mod.checkAddButton();
        mod.checkDeleteButton();
    };
    
  //data is the aData of the tr element.
    mod.dblClickAdd = function(data) {
    	
    	AIRTIME.playlist.addItems(data.Id);
    };
    
    return AIRTIME;

}(AIRTIME || {}));