(function ($) {
	
	"use strict";

	// Window Resize Mobile Menu Fix
	mobileNav();

	// Scroll animation init
	window.sr = new scrollReveal();

	// Menu Dropdown Toggle
	if($('.menu-trigger').length){
		$(".menu-trigger").on('click', function() {	
			$(this).toggleClass('active');
			$('.header-area .nav').slideToggle(200);
		});
	}

	// Menu elevator animation
	$('a[href*=\\#]:not([href=\\#])').on('click', function() {
		if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
			var target = $(this.hash);
			target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
			if (target.length) {
				var width = $(window).width();
				if(width < 991) {
					$('.menu-trigger').removeClass('active');
					$('.header-area .nav').slideUp(200);	
				}				
				$('html,body').animate({
					scrollTop: (target.offset().top) - 130
				}, 700);
				return false;
			}
		}
	});

	$(document).ready(function () {
	    $(document).on("scroll", onScroll);

	    // Smooth scroll
	    $('a[href^="#"]').on('click', function (e) {
	        e.preventDefault();
	        $(document).off("scroll");

	        $('a').each(function () {
	            $(this).removeClass('active');
	        });
	        $(this).addClass('active');

	        var target = this.hash;
	        var menu = target;
	        var target = $(this.hash);
	        $('html, body').stop().animate({
	            scrollTop: (target.offset().top) - 130
	        }, 500, 'swing', function () {
	            window.location.hash = target;
	            $(document).on("scroll", onScroll);
	        });
	    });
	});

	function onScroll(event){
	    var scrollPos = $(document).scrollTop();
	    $('.nav a').each(function () {
	        var currLink = $(this);
	        var refElement = $(currLink.attr("href"));
	        if (refElement.position().top <= scrollPos && refElement.position().top + refElement.height() > scrollPos) {
	            $('.nav ul li a').removeClass("active");
	            currLink.addClass("active");
	        } else {
	            currLink.removeClass("active");
	        }
	    });
	}

	// Home separator
	if($('.home-seperator').length) {
		$('.home-seperator .left-item, .home-seperator .right-item').imgfix();
	}

	// Home number counterup
	if($('.count-item').length){
		$('.count-item strong').counterUp({
			delay: 10,
			time: 1000
		});
	}

// 	Page loading animation
    $(window).on('load', function() {
        if ($('.cover').length) {
            $('.cover').parallax({
                imageSrc: $('.cover').data('image'),
                zIndex: '1'
            });
        }
    
        $("#preloader").animate({
            'opacity': '0'
        }, 1000, function() {
            setTimeout(function() {
                $("#preloader").css("visibility", "hidden").fadeOut();
            }, 100); // Ubah timeout menjadi 500 ms
        });
    });


	// Window Resize Mobile Menu Fix
	$(window).on('resize', function() {
		mobileNav();
	});

	// Window Resize Mobile Menu Fix
	function mobileNav() {
		var width = $(window).width();
		$('.submenu').on('click', function() {
			if(width < 992) {
				$('.submenu ul').removeClass('active');
				$(this).find('ul').toggleClass('active');
			}
		});
	}

	// Kirim pesan WhatsApp
	$('#form-submit').on('click', function(event) {
		event.preventDefault();
		var name = $('#name').val();
		var perihal = $('#perihal').val();
		var message = $('#message').val();

		var whatsappNumber = '6281297006284'; // Nomor WhatsApp dengan kode negara '62' untuk Indonesia
		var whatsappMessage = `Nama: ${name}%0APerihal: ${perihal}%0APesan: ${message}`; // Pesan dienkripsi untuk URL

		var whatsappURL = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(whatsappMessage)}`;

		window.open(whatsappURL, '_blank');
	});

})(window.jQuery);