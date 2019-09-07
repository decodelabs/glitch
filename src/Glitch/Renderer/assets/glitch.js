$(function() {
    $(document).on('click', '[data-open]', function(e) {
        e.preventDefault();
        var $button = $(this),
            $parent = $button.closest('.group'),
            targetClass = $button.attr('data-open'),
            $target = $parent.find('> .'+targetClass+',.body > .'+targetClass),
            open = $parent.hasClass('with-'+targetClass),
            isBadge = $button.hasClass('badge'),
            isEntity = $button.hasClass('name'),
            height;

        if(isBadge && !$parent.hasClass('with-body')) {
            $parent.toggleClass('with-'+targetClass, true);
            $parent.find('> .title > .name').click();
            return;
        }

        if(!open) {
            if(isEntity && !$parent.is("[class*='with-type-']")) {
                $parent.find('> .title .badge.primary').first().click();
            } else {
                $target.css({ display: 'block' });
                height = $target.prop('scrollHeight');
                $target.css({ height: height });
                $parent.toggleClass('with-'+targetClass, !open);

                setTimeout(function() {
                    $target.css({ height: '', display: '' });
                }, 350);
            }
        } else {
            $parent.toggleClass('with-'+targetClass, !open);

            if(isBadge && $parent.hasClass('with-body') && !$parent.is("[class*='with-type-']")) {
                $parent.toggleClass('with-'+targetClass, open);
                $parent.find('> .title > .name').click();
                $parent.toggleClass('with-'+targetClass, !open);
            } else {
                $parent.toggleClass('with-'+targetClass, open);

                $target.css({ display: 'block' });
                height = $target.prop('scrollHeight');
                $target.css({ height: height });
                $parent.toggleClass('with-'+targetClass, !open);
                $target.css({ height: '' });

                setTimeout(function() {
                    $target.css({ display: '' });
                }, 200);
            }
        }
    });

    $(document).on('click', 'a.ref', function(e) {
        e.preventDefault();

        var id = $(this).attr('href'),
            $target = $(id).children('.name'),
            $body = $($target.attr('data-target')),
            isBodyCollapsed = !$body.hasClass('show'),
            $parents = $target.parents('.collapse');

        window.location.hash = id;

        $parents.each(function() {
            $('a[data-target="#'+$(this).attr('id')+'"]').removeClass('collapsed');
        }).collapse('show');

        if(isBodyCollapsed) {
            $target.click();
        }

        var elOffset = $target.offset().top,
            elHeight = $target.height(),
            windowHeight = $(window).height(),
            offset;

        if (elHeight < windowHeight) {
            offset = elOffset - ((windowHeight / 4) - (elHeight / 2));
        } else {
            offset = elOffset + 50;
        }

        $('html, body').animate({ scrollTop: offset}, 700);
    });

    $(document).on('click', '.string.m.large', function() {
        $(this).toggleClass('show');
    });
});
