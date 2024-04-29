import '@/sass/styles.scss';

import $ from 'jquery';
window.$ = window.jQuery = $;

$(document).on('click', '[data-open]', function (e) {
    e.preventDefault();
    var $button = $(this),
        $parent = $button.closest('.group'),
        targetClass = $button.attr('data-open'),
        $target = $parent.find('> .' + targetClass + ',> .body > .' + targetClass),
        open = $parent.hasClass('w-' + targetClass),
        isBadge = $button.hasClass('badge'),
        isEntity = $button.hasClass('name'),
        height;

    if (isBadge && targetClass !== 'body' && !$parent.hasClass('w-body')) {
        $parent.toggleClass('w-' + targetClass, true);
        $parent.find('> .title > .name[data-open]').click();
        return;
    }

    if (!open) {
        if (isEntity && !$parent.is("[class*='w-t-']")) {
            var targetBadgeClass = $parent.find('> .title .badge.primary').first().attr('data-open');

            if (targetBadgeClass === undefined) {
                targetBadgeClass = $parent.find('> .title .badge').first().attr('data-open');
            }

            $parent.toggleClass('w-' + targetBadgeClass, true);
        }

        $target.css({ display: 'block' });
        height = $target.prop('scrollHeight');
        $target.css({ height: height });

        $parent.toggleClass('w-' + targetClass, !open).toggleClass('transitioning', true);

        setTimeout(function () {
            $target.css({ height: '', display: '' });
            $parent.toggleClass('transitioning', false);
        }, 350);
    } else {
        $parent.toggleClass('w-' + targetClass, !open);

        if (isBadge && targetClass !== 'body' && $parent.hasClass('w-body') && !$parent.is("[class*='w-t-']")) {
            $parent.toggleClass('w-' + targetClass, open);
            $parent.find('> .title > .name').click();
            $parent.toggleClass('w-' + targetClass, !open);
        } else {
            $parent.toggleClass('w-' + targetClass, open);

            $target.css({ display: 'block' });
            height = $target.prop('scrollHeight');
            $target.css({ height: height });

            setTimeout(function () {
                $parent.toggleClass('w-' + targetClass, !open);
                $target.css({ height: '' });
                $parent.toggleClass('transitioning', true);

                setTimeout(function () {
                    $target.css({ display: '' });
                    $parent.toggleClass('transitioning', false);
                }, 250);
            }, 1);
        }
    }
});



$(document).on('click', 'a.ref', function (e) {
    e.preventDefault();

    var id = $(this).attr('href'),
        $target = $(id).children('.name'),
        $entity = $(id).parent(),
        $parents = $target.parents('.entity:not(.w-body)'),
        isFramed = $('body').width() > 960,
        $scroller = isFramed ? $(this).closest('div.frame') : $('html,body'),
        windowHeight = isFramed ? $scroller.height() : $(window).height(),
        elHeight = $target.height(),
        $section = $(this).closest('section'),
        sectionOffset = isFramed ? $section.offset().top : 0,
        elOffset = $target.offset().top - sectionOffset;

    window.location.hash = id;

    $parents.each(function () {
        $(this).find('> .title .name').click();
    });

    if (elHeight < windowHeight) {
        offset = elOffset - ((windowHeight / 4) - (elHeight / 2));
    } else {
        offset = elOffset + 50;
    }

    $scroller.animate({ scrollTop: offset }, 700);
});



$(document).on('click', '.string.m.large', function () {
    $(this).toggleClass('show');
});
