$(function() {
    $(document).on('click', '[data-target]', function(e) {
        e.preventDefault();
        var $badge = $(this),
            $entity = $badge.closest('.entity'),
            isName = $badge.hasClass('name'),
            $target = $($badge.attr('data-target')),
            isCollapsed = !$target.hasClass('show'),
            isBody = $badge.hasClass('body'),
            $name = $entity.find('a.name'),
            $body = $($name.attr('data-target')),
            isBodyCollapsed = !$body.hasClass('show'),
            otherChildren = $body.children('div.collapse.show').not($badge.attr('data-target')).length;


        if(isBody) {
            if(!isCollapsed && isBodyCollapsed) {
                $body.collapse('show');
                $name.removeClass('collapsed');
            } else {
                $badge.toggleClass('collapsed', !isCollapsed);

                if(!isCollapsed) {
                    $target.collapse('toggle');

                    // Closing
                    if(!otherChildren) {
                        $body.collapse('hide');
                        $name.addClass('collapsed');
                    }
                } else {
                    // Opening
                    if(isBodyCollapsed) {
                        $target.addClass('show');
                        $body.collapse('show');
                        $name.removeClass('collapsed');
                    } else {
                        $target.collapse('show');
                    }
                }
            }
        } else {
            $badge.toggleClass('collapsed', !isCollapsed);

            if(isName && isCollapsed && !otherChildren) {
                var $first = $entity.find('a.primary:first');

                if(!$first.length) {
                    $first = $entity.find('a.badge:first');
                }

                $first.click();
            } else {
                $target.collapse('toggle');
            }

            if(isName && $entity.hasClass('type-stack')) {
                $entity.find('.badge.stack').toggleClass('collapsed', !isCollapsed);
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

    $(document).on('click', 'ul.stack .dump.trace', function(e) {
        $(this).parent().toggleClass('open', !$(this).hasClass('collapsed'));
    });
});
