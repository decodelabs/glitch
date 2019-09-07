$(function() {
    $(document).on('click', '[data-open]', function(e) {
        e.preventDefault();
        var $button = $(this),
            $parent = $button.closest('.group'),
            targetClass = $button.attr('data-open'),
            $target = $parent.find('> .'+targetClass+',> .body > .'+targetClass),
            open = $parent.hasClass('w-'+targetClass),
            isBadge = $button.hasClass('badge'),
            isEntity = $button.hasClass('name'),
            height;

        if(isBadge && targetClass !== 'body' && !$parent.hasClass('w-body')) {
            $parent.toggleClass('w-'+targetClass, true);
            $parent.find('> .title > .name[data-open]').click();
            return;
        }

        if(!open) {
            if(isEntity && !$parent.is("[class*='w-t-']")) {
                var targetBadgeClass = $parent.find('> .title .badge.primary').first().attr('data-open');
                $parent.toggleClass('w-'+targetBadgeClass, true);
            }

            $target.css({ display: 'block' });
            height = $target.prop('scrollHeight');
            $target.css({ height: height });
            $parent.toggleClass('w-'+targetClass, !open);

            setTimeout(function() {
                $target.css({ height: '', display: '' });
            }, 350);
        } else {
            $parent.toggleClass('w-'+targetClass, !open);

            if(isBadge && targetClass !== 'body' && $parent.hasClass('w-body') && !$parent.is("[class*='w-t-']")) {
                $parent.toggleClass('w-'+targetClass, open);
                $parent.find('> .title > .name').click();
                $parent.toggleClass('w-'+targetClass, !open);
            } else {
                $parent.toggleClass('w-'+targetClass, open);

                $target.css({ display: 'block' });
                height = $target.prop('scrollHeight');
                $target.css({ height: height });
                $parent.toggleClass('w-'+targetClass, !open);
                $target.css({ height: '' });

                setTimeout(function() {
                    $target.css({ display: '' });
                }, 250);
            }
        }
    });



    $(document).on('click', 'a.ref', function(e) {
        e.preventDefault();

        var id = $(this).attr('href'),
            $target = $(id).children('.name'),
            $entity = $(id).parent(),
            $parents = $target.parents('.entity:not(.w-body)');

        window.location.hash = id;

        $parents.each(function() {
            $(this).find('> .title .name').click();
        });

        var elOffset = $target.offset().top,
            elHeight = $target.height(),
            windowHeight = $(window).height(),
            offset;

        if (elHeight < windowHeight) {
            offset = elOffset - ((windowHeight / 4) - (elHeight / 2));
        } else {
            offset = elOffset + 50;
        }

        $(this).closest('div.frame').animate({ scrollTop: offset}, 700);
    });



    $(document).on('click', '.string.m.large', function() {
        $(this).toggleClass('show');
    });
});
