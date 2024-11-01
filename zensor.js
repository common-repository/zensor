function zensor_showPreview(cell, link, postId) {
	if( cell.id == 'zensor_active' ) {
	    // Clicked on the currently active preview, so turn it off
		cell.innerHTML = zensor_preview_on;
		$('zensor_preview').hide();
		cell.id = '';
		return;
	} else if( active = $('zensor_active')){
	    // Another cell is already active, so turn it off first
		active.innerHTML = zensor_preview_on;
		active.id = '';
	}
			
    // Setup the preview frame
	$('zensor_preview_iframe').src = link;
	$('zensor_post_id').value= postId;
	
	// Put the preview frame after the row that is being previewed
	r=cell.parentNode.parentNode;
	if(r.nextSibling) 
	    r.parentNode.insertBefore($('zensor_preview'), r.nextSibling);
	else
	    r.parentNode.appendChild($('zensor_preview'));
	
	// Mark the row being previewed
	cell.innerHTML = zensor_preview_off;
	cell.id = 'zensor_active';

	$('zensor_preview').show();
}