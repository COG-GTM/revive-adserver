(function($) {
    $.extend({
        activateDropDown: new function() {
            var active = null;
            var triggerElement = null;

            function onToggle(event) {
                event.stopPropagation();
                
                $(this).parent('.dropDown').each(function() {
                    if ($(this).hasClass('active')) {
                        $(this).removeClass('active');
                        $(this).find('div.panel').attr('aria-hidden', 'true');
                        $(this).children('span').attr('aria-expanded', 'false');
                        $(this).trigger('dropdownClose');
                        if (triggerElement) {
                            triggerElement.focus();
                            triggerElement = null;
                        }
                        active = null;
                    } else {
                        triggerElement = event.target;
                        $(this).addClass('active');
                        $(this).find('div.panel').attr('aria-hidden', 'false');
                        $(this).children('span').attr('aria-expanded', 'true');
                        $(this).trigger('dropdownOpen', [ event.target ]);
                        active = this;
                    }
                });
            }
            
            function onClose() {
                if (active) {
                    $(active).removeClass('active');
                    $(active).find('div.panel').attr('aria-hidden', 'true');
                    $(active).children('span').attr('aria-expanded', 'false');
                    $(active).trigger('dropdownClose');
                    if (triggerElement) {
                        triggerElement.focus();
                        triggerElement = null;
                    }
                    active = null;
                }
            }
            
            function onKey(event) {
                if (event.keyCode == 27 && active) {
                    onClose();
                    event.preventDefault();
                }
            }

			function preventClose(event) {
				event.stopPropagation();
			}

            this.construct = function(settings) {
                return this.each(function() {
                    var $trigger = $(this).children('span');
                    $trigger.attr({
                        'role': 'button',
                        'aria-expanded': 'false',
                        'aria-haspopup': 'true',
                        'tabindex': '0'
                    });
                    $(this).children('div.panel').attr({
                        'role': 'menu',
                        'aria-hidden': 'true'
                    });
                    $trigger.bind('click', onToggle);
                    $trigger.bind('keydown', function(event) {
                        if (event.keyCode == 13 || event.keyCode == 32) {
                            event.preventDefault();
                            onToggle.call(this, event);
                        }
                    });
                    $(this).children('div.mask').bind('click', onToggle);
                    $(this).children('div.panel').children().bind('click', preventClose);
                    $('body').bind('click', onClose);
                    $('body').bind('keydown', onKey);
                    $(this).bind("close", onClose);
                });
            };
        }
    });

    // extend plugin scope
    $.fn.extend({
        activateDropDown: $.activateDropDown.construct
    });

    // extend all forms
    $(document).ready(function() {
        $('.dropDown').activateDropDown();
    });

})(jQuery);
                
