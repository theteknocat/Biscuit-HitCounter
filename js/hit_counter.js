$(document).observe("dom:loaded",function() {
	if (window.hit_already_counted === undefined && window.hit_url !== undefined) {
		new Ajax.Request(window.hit_url+'?count_hit=1',{
			method: 'get',
			requestHeaders: Biscuit.Ajax.RequestHeaders('server_action')
		});
	}
});