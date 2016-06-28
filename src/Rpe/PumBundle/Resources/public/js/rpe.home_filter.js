var isDev = window.location.pathname.indexOf('app_dev.php');
if (isDev > -1) {
	var pathHome = "/app_dev.php";
	var pathDashboard = "/app_dev.php/dashboard";
} else {
	var pathHome = "/";
	var pathDashboard = "/dashboard";
}


if (localStorage.getItem("home_filter") === "dashboard" ) {
	if (window.location.pathname !== pathDashboard) {
		window.location.replace(pathDashboard);
	}
}
if (localStorage.getItem("home_filter") === "home" ) {
	if (window.location.pathname !== pathHome) {
		window.location.replace(pathHome);
	}
}
$(document).on('ready', function(){	
	// Big button, remove small class from cards
    $('#filter_dashboard').on('click',function(event){
        localStorage.setItem("home_filter", "dashboard");
    });

    // Small button, add small class on cards
    $('#filter_home, #logo a').on('click',function(event){
        localStorage.setItem("home_filter", "home");
    });
});