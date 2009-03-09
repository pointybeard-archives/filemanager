
	var regex = /(symphony\/extension\/filemanager\/new\/)[^\/]+/;
	
	function typeRedirect(value){
		location = location.href.replace(regex, "$1" + value);
	}