// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details
 *
 * @package block_programer_slider
 * @copyright 2016 Kyriaki Hadjicosta (Coventry University)
 * @copyright
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

$(document).ready(function() {

    // Generate coursesliderp and associate it with courseslidernav.

    $('.coursesliderp').each(function() {
        var instanceid = this.id;
        var courseslidernav = "#" + instanceid + "-nav";

        var navigationgallery = $(this).attr('data-navigationgallery');
        var navigationoption = $(this).attr('data-navigationoption');
        var numberofslides = parseInt($(this).attr('data-numberofslides'), 10);
        var centermode = parseInt($(this).attr('data-centermode'), 10);
        var autoplayspeed = parseInt($(this).attr('data-autoplayspeed'), 10);
        var arrows = (navigationoption == 'Arrows' || navigationoption == 'Arrows and Radio buttons') ? true : false;
        var dots = (navigationoption == 'Radio buttons' || navigationoption == 'Arrows and Radio buttons') ? true : false;
        var coursenav = '';

        centermode = (centermode == 1) ? true : false;
        if (navigationgallery == '1') {
            numberofslides = 1;
            arrows = false;
            dots = false;
            centermode = false;
            coursenav = courseslidernav;
        }

        $(this).slick({
            swipeToSlide : true,
            infinite : true,
            slidesToShow : numberofslides,
            slidesToScroll : 1,
            arrows : arrows,
            dots : dots,
            autoplay : true,
            autoplaySpeed : autoplayspeed,
            focusOnSelect : true,
            centerMode : centermode,
            asNavFor : coursenav,
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 3,
                        infinite: true,
                        dots: true
                    }
            },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 2
                    }
            },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1
                    }
            }

                ]

        });
    });

    // Generate courseslidernav and associate it with coursesliderp.
    $('.coursesliderp-nav').each(function() {
        var instanceid = this.id;
        var coursesliderp = "#" + instanceid.slice(0, -4);

        var navigationgallery = $(this).attr('data-navigationgallery');
        var navigationoption = $(this).attr('data-navigationoption');
        var numberofslides = parseInt($(this).attr('data-numberofslides'), 10);
        var centermode = parseInt($(this).attr('data-centermode'), 10);
        var autoplayspeed = parseInt($(this).attr('data-autoplayspeed'), 10);
        var arrows = (navigationoption == 'Arrows' || navigationoption == 'Arrows and Radio buttons') ? true : false;
        var dots = (navigationoption == 'Radio buttons' || navigationoption == 'Arrows and Radio buttons') ? true : false;

        centermode = (centermode == 1) ? true : false;

        if (navigationgallery == '1') {
            $(this).slick({
                swipeToSlide : true,
                infinite : true,
                slidesToShow : numberofslides,
                slidesToScroll : 1,
                arrows : arrows,
                dots : dots,
                autoplay : true,
                autoplaySpeed : autoplayspeed,
                centerMode : centermode,
                focusOnSelect : true,
                asNavFor : coursesliderp,
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3,
                            infinite: true,
                            dots: true
                        }
                },
                    {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 2
                        }
                },
                    {
                        breakpoint: 480,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                }

                    ]
            });
        }
    });

    // Add mouseenter response.
    $('.coursesliderp-course').mouseenter(function() {

        $(this).addClass('coursesliderp-course-hovered');

        $('.coursesliderp-course-image', this).addClass('coursesliderp-course-image-hovered');

        $('.coursesliderp-course-summary', this).addClass('coursesliderp-course-summary-hovered');

        $('.coursesliderp-course-name', this).addClass('coursesliderp-course-name-hovered');

    });

    // Add mouseleave leave.
    $('.coursesliderp-course').mouseleave(function() {
        $(this).removeClass('coursesliderp-course-hovered');

        $('.coursesliderp-course-image', this).removeClass('coursesliderp-course-image-hovered');

        $('.coursesliderp-course-summary', this).removeClass('coursesliderp-course-summary-hovered');

        $('.coursesliderp-course-name', this).removeClass('coursesliderp-course-name-hovered');

    });

    // Make courselider and coursesliderp-nav visible once they have loaded.
    $('.coursesliderp').addClass('coursesliderp-visible');
    $('.coursesliderp-nav').addClass('coursesliderp-nav-visible');

});


$(window).bind('resize', function(e) {
    var resizeEvt;
    $(window).resize(function() {
        clearTimeout(resizeEvt);
        resizeEvt = setTimeout(function() {
        }, 300);
    });
});
