$(document).ready(function() {
	if (window.hit_already_counted === undefined && window.hit_url !== undefined) {
		Biscuit.Ajax.Request(window.hit_url+'?count_hit=1','server_action',{type: 'get'});
	}
});