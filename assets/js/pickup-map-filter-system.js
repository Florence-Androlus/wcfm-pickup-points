jQuery(document).ready(function($) {
    $('#pickup-country').select2();

    $('#pickup-search').on('input', function() {
        const term = $(this).val().toLowerCase().trim();
        $('.wcfmmp-single-store').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(term));
        });
        if (typeof applyFilters === 'function') applyFilters();
    });

    $('.kadence-toggle-shop-layout').on('click', function(e) {
        e.preventDefault();
        const type = $(this).data('archive-toggle');
        $('.kadence-toggle-shop-layout').removeClass('toggle-active');
        $(this).addClass('toggle-active');
        $('#products-wrapper, .product_area, ul.products').removeClass('list grid').addClass(type);
    });
});