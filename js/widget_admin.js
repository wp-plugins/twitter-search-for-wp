jQuery(document).ready(function(e){

	jQuery("a.search-query-help").live("click",function(e){
		e.preventDefault();
		jQuery(this).parents('.widget').find(".search-query-help-div").toggle();
	});
	
	jQuery("a.search-template-help").live("click",function(e){
		e.preventDefault();
		jQuery(this).parents('.widget').find(".search-template-help-div").toggle();
	});
	
});
