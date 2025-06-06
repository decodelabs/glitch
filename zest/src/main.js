import '@/sass/styles.scss';

import $ from 'jquery';
window.$ = window.jQuery = $;

// Section toggle
$(document).on('click', 'input[type=radio].section-toggle', function (e) {
    const
        $container = $(this).closest('.container'),
        $toggle = $('#' + $container.attr('id') + '-toggle'),
        $sectionToggle = $('input[name="' + $container.attr('id') + '-section"]:checked'),
        clickedId = $(this).attr('value'),
        selectedId = $sectionToggle.attr('value'),
        open = $toggle.prop('checked'),
        toOpen = open ? clickedId !== selectedId : true;
    $('#' + e.target.value).prop('checked', true);
    $toggle.prop('checked', toOpen);
});

// Target
$(document).on('change', 'input[type=checkbox].container-toggle', function (e) {
    if (
        '#' + this.id === window.location.hash + '-toggle' &&
        !this.checked
    ) {
        window.location.hash = '';
    }
});

// Reference
$(document).on('click', 'a.object-reference', function (e) {
    e.preventDefault();

    var id = $(this).attr('href'),
        $toggle = $(id + '-toggle'),
        $container = $toggle.closest('.container'),
        open = $toggle.prop('checked'),
        scroller = () => $container[0].scrollIntoView({ behavior: 'smooth', block: 'center' });

    window.location.hash = id;
    $toggle.prop('checked', true);

    if (open) {
        scroller();
    } else {
        setTimeout(scroller, 150);
    }
});
