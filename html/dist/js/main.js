$(document).ready(function(){
    $('.featured-works-slider').slick({
        infinite: true,
        slidesToShow: 3,
        slidesToScroll: 1,
        arrows: true,
        dots: false,
        autoplaySpeed: 1000,
        speed: 1000, 
        responsive:[
            {
                breakpoint: 480,
                settings: {
                  slidesToShow: 2,
                  slidesToScroll: 1,
                }
            },
        ]
    });
    $('.info-image').slick({
        infinite: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: true,
        dots: true,
        autoplaySpeed: 1000,
        speed: 1000,
    });
    $('.partner-slider').slick({
        infinite: true,
        slidesToShow: 4,
        slidesToScroll: 1,
        arrows: false,
        dots: false,
        autoplaySpeed: 1000,
        speed: 500,
        responsive: [
            {
                breakpoint: 991,
                settings: {
                  slidesToShow: 3,
                  slidesToScroll: 1,
                  infinite: true,
                }
            },
            {
                breakpoint: 480,
                settings: {
                  slidesToShow: 2,
                  slidesToScroll: 1,
                  infinite: true,
                }
            },
        ]
    });
    $('.btn-menu').click(function(){
        $('.menu').toggleClass("active-menu")
        $('.over-lay-mobie').toggleClass("active-overlay")
    })
    $('.over-lay-mobie').click(function(){
        $('.menu').toggleClass("active-menu")
        $('.over-lay-mobie').toggleClass("active-overlay")
    })
    $('.close').click(function(){
        $('.menu').toggleClass("active-menu")
        $('.over-lay-mobie').toggleClass("active-overlay")
    })
    $('.btn-show-info').click(function(){
        $('.pockup-info').fadeIn()
        $('.overlay').fadeIn()
    })
    $('.overlay').click(function(){
        $('.pockup-info').fadeOut()
        $('.overlay').fadeOut()
    })
});
$(document).ready(function () {
    var accToggles = document.getElementsByClassName('tabs-child');
    var tabClick = function(el){
        var targetParent = el.target.closest('.tabs-child');
        Array.prototype.forEach.call(accToggles, function(tog){
            var tabParent = tog.closest('.tabs-child');
            if (tabParent != targetParent){
                tabParent.classList.remove('showw');
            } else {
                targetParent.classList.toggle('showw');
            }
        });
    };
    Array.prototype.forEach.call(accToggles, function(tog, index) {
        tog.addEventListener('click', tabClick, false);
    });
});
function openCity(evt, tabsName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabs-content");
    for (i = 0; i < tabcontent.length; i++) {
      tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tabs-link");
    for (i = 0; i < tablinks.length; i++) {
      tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabsName).style.display = "block";
    evt.currentTarget.className += " active";
    document.getElementById("defaultOpen").click();
  }
  
