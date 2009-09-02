jQuery(document).ready(function()
{	
	jQuery('#saveSettings').click(function() {
		jQuery(this).tinyQEAjax({
			'url'			: jQuery.tinyQE.variables.ajaxUrl + '&class=Tqe_Ajax_Settings&action=save',
			'data'			: {
				'returnCont' 			: jQuery.tinyQE.variables.returnCont.msg,
				'displayContacts'		: jQuery('#displayContacts :selected').val(),
				'displayCategories'		: jQuery('#displayCategories :selected').val(),
				'displayEmails'			: jQuery('#displayEmails :selected').val(),
				'autoSaveEvery'			: jQuery('#autoSaveEvery').val(),
				'deleteTrashAfter'		: jQuery('#deleteTrashAfter').val()
			}
		});
		
		return false;
	});

	jQuery('#defaultSettings').click(function() {
		jQuery(this).tinyQEAjax({
			'url'			: jQuery.tinyQE.variables.ajaxUrl + '&class=Tqe_Ajax_Settings&action=default',
			'data'			: {
				'returnCont' 			: jQuery.tinyQE.variables.returnCont.msg | jQuery.tinyQE.variables.returnCont.custom
			},
			'successFunc'	: function(data, opt)
			{
				jQuery.tinyQE.tinyQESuccess(data, opt);
				
				if (typeof data.error === 'undefined') {
					jQuery('#displayContacts option[value="' + data.custom.displayContacts + '"]').attr('selected', true);
					jQuery('#displayCategories option[value="' + data.custom.displayCategories + '"]').attr('selected', true);
					jQuery('#displayEmails option[value="' + data.custom.displayEmails + '"]').attr('selected', true);
					jQuery('#autoSaveEvery').val(data.custom.autoSaveEvery)
					jQuery('#deleteTrashAfter').val(data.custom.deleteTrashAfter);
				}
			}
		});
		
		return false;
	});
	


	jQuery('#uninstallTQE').click(function() {
		jQuery(this).tinyQEAjax({
			'url'			: jQuery.tinyQE.variables.ajaxUrl + '&class=Tqe_Ajax_Uninstall&action=uninstall',
			'data'			: {
				'returnCont' 			: jQuery.tinyQE.variables.returnCont.msg
			}
		});
		
		return false;
	});
});