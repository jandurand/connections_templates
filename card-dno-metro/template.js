jQuery(document).ready(function() {
	var	bd_category = jQuery("#bd-category");
	var bd_search = jQuery("#bd-search");
	var bd_search_button = jQuery("#bd-search-button");
	
	bd_category.change(function() {
		// Clear search	
		bd_search.val('');
		jQuery(this.form).submit();
	});
	
	bd_search_button.click(function() {
		bd_category.val('');
	});
});