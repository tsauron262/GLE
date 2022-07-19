// JS Spécifique BimpTheme

//The Bimp logo is hidden when the user close menu
function hideBimpLogo(ajax = true) {
    let logo = document.getElementById("logo-img");
    if (logo.style.display === "none") {
        logo.style.display = "block";

        if (ajax) {
            setSessionConf("hideMenu", false);
        }
    } else {
        if (ajax) {
            setSessionConf("hideMenu", true);
        }

        $(".app-sidebar").hide();

        setTimeout(function () {
            $(".app-sidebar").show();
        }, 200);

        $(".header-top").mouseover();
        logo.style.display = "none";
}
}


//sessionHideMenu provient du fichier menu.php
function bimpInit(i) {
    var e = $(".toggle-icon");
    if (sessionHideMenu == 1) {
        var n = i(".app-sidebar"),
                t = i(".sidebar-content"),
                l = i(".wrapper");
        "expanded" === e.attr("data-toggle") ? (l.addClass("nav-collapsed"), i(".nav-toggle").find(".toggle-icon").removeClass("ik-toggle-right").addClass("ik-toggle-left"), e.attr("data-toggle", "collapsed")) : (l.removeClass("nav-collapsed menu-collapsed"), i(".nav-toggle").find(".toggle-icon").removeClass("ik-toggle-left").addClass("ik-toggle-right"), e.attr("data-toggle", "expanded"))
        hideBimpLogo(0);
    }
}


//Hide the responsive button for the menu when the screen is > at 992px
function displayResponsiveButton(mobileScreen) {

    if (mobileScreen.matches) {
        if (parseInt($('#responsiveButton').length) != 0)
            document.getElementById("responsiveButton").style.visibility = 'visible';
    } else {
        if (parseInt($('#responsiveButton').length) != 0)
            document.getElementById("responsiveButton").style.visibility = 'hidden';
    }

    if (mobileScreen.matches) {
        if (parseInt($('#logo-img').length) != 0)
            document.getElementById("logo-img").style.visibility = 'hidden';
    } else {
        if (parseInt($('#logo-img').length) != 0)
            document.getElementById("logo-img").style.visibility = 'visible';
    }

}

let mobileScreen = window.matchMedia("(max-width: 992px)");
displayResponsiveButton(mobileScreen);
mobileScreen.addListener(displayResponsiveButton);

//delete style tag at the GRH Menu
$("a.menu-item").click(function () {
    $(".nav-item.has-sub").removeAttr("style");
});

//hide the left/top menu when you open an iframe
function isInFrame() {
    if (window.location !== window.parent.location) {
        // The page is in an iframe	
        $('header.header-top').hide();
        $('div.app-sidebar.colored').hide();
        $('.main-content').css('padding', '0');
        $('.main-content').css('margin', '0');
    }

}