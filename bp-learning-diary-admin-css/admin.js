jQuery(document).ready(function($) {
	// move the menu from #adminmenuwrap to #wpbody as befor wp 3.2
	$('#wpbody').prepend( $('#adminmenuwrap > ul') );
	// remove now unused divs
	$('#adminmenuback').remove()
	$('#adminmenuwrap').remove()
	// remove (wrong sided) arrow
	$('.wp-menu-arrow').remove()
	// remove thank you footer
	$('#footer-left').remove()
	// remove collapse menu
	$('#collapse-menu').remove()
})