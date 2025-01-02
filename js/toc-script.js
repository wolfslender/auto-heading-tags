jQuery(document).ready(function($) {
    // Estado inicial
    var isOpen = true;
    $('.modern-toc-content').show();
    
    // Toggle del menú
    $('.modern-toc-toggle').on('click', function() {
        var $content = $(this).closest('.modern-toc-container').find('.modern-toc-content');
        var $button = $(this);
        
        isOpen = !isOpen;
        
        if (isOpen) {
            $content.slideDown(300);
            $button.html('✕').attr('aria-expanded', 'true');
        } else {
            $content.slideUp(300);
            $button.html('☰').attr('aria-expanded', 'false');
        }
    });

    // Smooth scroll a los encabezados
    $('.modern-toc-list a').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $('html, body').animate({
            scrollTop: $(target).offset().top - 50
        }, 500);
    });

    // Resaltar sección actual mientras se hace scroll
    $(window).scroll(function() {
        var scrollPosition = $(window).scrollTop();

        $('.modern-toc-list a').each(function() {
            var currentLink = $(this);
            var refElement = $(currentLink.attr('href'));
            
            if (refElement.position().top <= scrollPosition + 100 && 
                refElement.position().top + refElement.height() > scrollPosition + 100) {
                $('.modern-toc-list a').removeClass('active');
                currentLink.addClass('active');
            }
        });
    });
});
