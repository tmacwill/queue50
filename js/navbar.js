$(function() {
    $('.change-app, .change-app-dropdown').on('mouseover', function() {
        $('.change-app, .change-app-dropdown').addClass('hover');
    });

    $('.change-app, .change-app-dropdown').on('mouseout', function() {
        $('.change-app, .change-app-dropdown').removeClass('hover');
    });

    $('.dropdown-toggle').on('mouseover', function() {
        $(this).siblings('.dropdown-menu').show();
    });

    $('.dropdown-toggle').on('mouseout', function() {
        $(this).siblings('.dropdown-menu').hide();
    });

    $('.dropdown-menu').on('mouseover', function() {
        $(this).show();
        $(this).siblings('.dropdown-toggle').addClass('hover');
    });

    $('.dropdown-menu').on('mouseout', function() {
        $(this).hide();
        $(this).siblings('.dropdown-toggle').removeClass('hover');
    });

    $('.navbar-header, .header-dropdown-arrow, .header-dropdown').on('mouseover', function() {
        $('.header-dropdown').css({ marginLeft: '-' + ($('a.brand').width() + 53) + 'px' }).show();
        $('.header-dropdown-arrow, .navbar-header .brand').addClass('hover');
    });

    $('.navbar-header, .header-dropdown-arrow, .header-dropdown').on('mouseout', function() {
        $('.header-dropdown').hide();
        $('.header-dropdown-arrow, .navbar-header .brand').removeClass('hover');
    });
});
