//Init jQuery
jQuery(document).ready(function()
{
	//Add copy button
	jQuery('#addCc').click(function()
	{
		hideShow('addSep', true);
		hideShow('addCc', true);
		hideShow('ccCopy', false);
		jQuery('#useCc').val(1);
		
		return false;
	});
	
	//Add hidden copy button
	jQuery('#addBcc').click(function()
	{
		hideShow('addSep', true);
		hideShow('addBcc', true);
		hideShow('bccCopy', false);
		jQuery('#useBcc').val(1);
		
		return false;
	});
	
	//Remove copy link
	jQuery('#remCcLink').click(function()
	{
		jQuery('#useCc').val(0);
		
		if (needShowSep())
			hideShow('addSep', false);
		
		hideShow('addCc', false);
		hideShow('ccCopy', true);
		
		return false;
	});
	
	//Remove hidden copy link
	jQuery('#remBccLink').click(function()
	{
		jQuery('#useBcc').val(0);
		
		if (needShowSep())
			hideShow('addSep', false);
		
		hideShow('addBcc', false);
		hideShow('bccCopy', true);
		
		return false;
	});
	
	//Add events to links
	jQuery('#outbox, #drafts, #trash, #aBook, #tqePagin a, .tqeLink').live('click', function()
	{
		jQuery(this).tinyQEAjax({
			'data'	: {
				'returnType'	: 'json',
				'returnCont'	: jQuery.tinyQE.variables.returnCont.body,
			}
		});
		
		return false;
	});

	jQuery('.tqeNavAllAction').live('click', function()
	{
		
		var extendData = '&post=true';
	
		var temp = [];
		
		jQuery('.tqeSelect:checked').each(function(i) {
			temp[i] = jQuery(this).val();
		});
		
		if (temp.length > 0)
			extendData += '&ids[]=' + temp.join('&ids[]=');
		
		jQuery(this).tinyQEAjax({
			'data'	: 'returnType=json&returnCont=' + (jQuery.tinyQE.variables.returnCont.body | jQuery.tinyQE.variables.returnCont.msg)
			+ extendData
		});

		return false;
	});
	
	//Add category event
	jQuery('#addCategoryTqe').live('click', function()
	{
		jQuery(this).tinyQEAjax({
			'url' 		 : jQuery.tinyQE.variables.ajaxUrl + '&class=Tqe_Ajax_Abook&action=addCat',
			'data'		 : buildCategories(),
			'postAjax' 	 : function(data, opt)
			{
				if (typeof data.error === 'undefined')
					jQuery('#categoryName').val('');
					
				jQuery.tinyQE.defaultPostAjax(opt);
			}
		});
		
		return false;
	});

	//Add contact event
	jQuery('#tqeAddContact').live('click', function()
	{
		jQuery(this).tinyQEAjax({
			'url' 		 : jQuery.tinyQE.variables.ajaxUrl + '&class=Tqe_Ajax_Abook&action=add',
			'data'		 : buildUsers(),
			'postAjax' 	 : function(data, opt)
			{
				if (typeof data.error === 'undefined') {
					jQuery('#firstName').val('');
					jQuery('#middleName').val('');
					jQuery('#lastName').val('');
					jQuery('#email').val('');
					jQuery('#category option[value="-1"]').attr('selected', true);
				}
					
				jQuery.tinyQE.defaultPostAjax(opt);
			}
		});
		
		return false;
	});
	
	jQuery('#tqeEditCategory').live('click', function()
	{
		jQuery(this).tinyQEAjax({
			'url' 		 : jQuery.tinyQE.variables.ajaxUrl + '&class=Tqe_Ajax_Abook&action=editCat',
			'data'		 : jQuery.extend({}, buildCategories(), {'id' : jQuery('#id').val()})
		});
		
		return false;
	});
	
	jQuery('#tqeEditContact').live('click', function()
	{
		jQuery(this).tinyQEAjax({
			'url' 		 : jQuery.tinyQE.variables.ajaxUrl + '&class=Tqe_Ajax_Abook&action=edit',
			'data'		 : jQuery.extend({}, buildUsers(), {'id' : jQuery('#id').val()})
		});
		
		return false;
	});
	
	jQuery('#tqeEditUser').live('click', function()
	{
		jQuery(this).tinyQEAjax({
			'url' 		 : jQuery.tinyQE.variables.ajaxUrl + '&class=Tqe_Ajax_Abook&action=edit',
			'data'		 : buildUsers()
		});
		
		return false;
	});
	
	jQuery('.tqeSelectAll').live('click', function()
	{
		checkAllHelper(this, '.tqeSelectAll', '.tqeSelect');
	});
	
	jQuery('.tqeSelect').live('click', function()
	{
		checkHelper(this, '.tqeSelectAll', '.tqeSelect');
	});

	
	jQuery('.listUsersAction, #tqePaginMail a').live('click', function()
	{
		jQuery(this).tinyQEAjax({
			'data' 			: {
				'returnCont'	: jQuery.tinyQE.variables.returnCont.body
			},
			'successFunc'	: function(data, opt)
			{
				jQuery.tinyQE.tinyQESuccess(data, opt);
				
				var regex = /fieldType=([0-9]+)/;
				
				var fieldId = opt.url.match(regex)[1];
				
				switch (fieldId) {
					case jQuery.tinyQE.variables.ccField :
						fieldId = 1;
						break;
					case jQuery.tinyQE.variables.bccField :
						fieldId = 2;
						break;
					case jQuery.tinyQE.variables.toField :
					default :
						fieldId = 0;
						break;
				}
				
				jQuery.tinyQE.variables.currFieldType = fieldId;
				
				var temp = jQuery.tinyQE.variables.usersInFields[fieldId];
				
				jQuery('.tqeSelectToAddContact').each(function()
				{
					var element = jQuery(this);
					
					if (typeof temp[element.val()] !== 'undefined')
						element.attr('checked', true);
				});
				
				allChecked('.tqeSelectToAddContact', '.tqeSelectAllToAddContact');
			}
		});
		
		return false;
	});
	
	jQuery('.tqeSelectAllToAddContact').live('click', function()
	{
		checkAllHelper(this, '.tqeSelectAllToAddContact', '.tqeSelectToAddContact')
		
		jQuery('.tqeSelectToAddContact').each(function()
		{
			addToArrayAndBuild(jQuery(this).val(), jQuery(this).attr('checked'));
		});
	});
	
	jQuery('.tqeSelectToAddContact').live('click', function()
	{
		checkHelper(this, '.tqeSelectAllToAddContact', '.tqeSelectToAddContact');		
		
		addToArrayAndBuild(jQuery(this).val(), jQuery(this).attr('checked'));
	});
	
	jQuery('#sendMail, #discardMail, #saveMail').click(function() {
		
		var action = jQuery(this).val();
		action = action.toLowerCase();
		title = jQuery(this).attr('title');
		
		if (title !== 'autosave')
			stopTimer();
		
		jQuery(this).tinyQEAjax({
			'url'		: jQuery.tinyQE.variables.ajaxUrl + '&class=Tqe_Ajax_Mail&action=' + action,
			'data'		: buildEmail(action, title),
			'postAjax'	: function(data, opt)
			{
				if (typeof data.error === 'undefined') {
					if (action === 'save')
						jQuery.tinyQE.variables.currEmailId = data.custom.id;
					else
						resetCurrentEmail();
				}
				
				jQuery.tinyQE.defaultPostAjax(opt);
			}
		});
	});
	
	jQuery('#newEmail').live('click', function() {
		resetCurrentEmail();
		jQuery('#messagesReport').hide();
	});
	
	jQuery('#to, #cc, #bcc, #subject, #message').keypress(function () {
		if (!jQuery.tinyQE.variables.autoSave.running) {
			jQuery.tinyQE.variables.autoSave.running = true;
			jQuery.tinyQE.variables.autoSave.timeOutObj = setTimeout('autoSaveTimer()', jQuery.tinyQE.variables.autoSave.delay);
		}		
	});
	
	jQuery('.loadDraftLink').live('click', function() {
		jQuery(this).tinyQEAjax({
			'data'			: {
				'returnCont' : jQuery.tinyQE.variables.returnCont.custom
			},
			'successFunc'	: function(data, opt)
			{
				
				
				if (typeof data.error === 'undefined') {
					
					var draft = data.custom.draft;
					
					jQuery('#to').val(draft.to);
					jQuery('#cc').val(draft.cc);
					jQuery('#bcc').val(draft.bcc);
					jQuery('#subject').val(draft.subject);
					jQuery('#message').val(draft.message);
					
					if (draft.useCc === '1')
						jQuery('#addCc').trigger('click');
					
					if (draft.useBcc === '1')
						jQuery('#addBcc').trigger('click');
					
					jQuery.tinyQE.variables.usersInFields = unserialize(draft.usersInFields);
					jQuery.tinyQE.variables.currEmailId = draft.id;
				}

				jQuery.tinyQE.tinyQESuccess(data, opt);
			}
		});
		
		return false;
	});
	
});

function buildEmail(action, title)
{
	var data = {
		'id' 			: jQuery.tinyQE.variables.currEmailId,
		'returnCont'	: jQuery.tinyQE.variables.returnCont.msg
	};
	
	if (action === 'save') {
		data.returnCont = data.returnCont | jQuery.tinyQE.variables.returnCont.custom;
		data.autosave = title;
	}
	
	if (action !== 'discard')
		data = jQuery.extend({}, data, {
			'to'		: jQuery('#to').val(),
			'useCc'		: jQuery('#useCc').val(),
			'cc'		: jQuery('#cc').val(),
			'useBcc'	: jQuery('#useBcc').val(),
			'bcc'		: jQuery('#bcc').val(),
			'subject'	: jQuery('#subject').val(),
			'message'	: jQuery('#message').val(),
			'users'		: serialize(jQuery.tinyQE.variables.usersInFields)
		});
	
	return data;
}

function autoSaveTimer()
{
	jQuery('#saveMail').attr('title', 'autosave');
	jQuery('#saveMail').trigger('click');
	jQuery('#saveMail').attr('title', '');
	jQuery.tinyQE.variables.autoSave.timeOutObj = setTimeout('autoSaveTimer()', jQuery.tinyQE.variables.autoSave.delay);
}

function stopTimer()
{
	clearTimeout(jQuery.tinyQE.variables.autoSave.timeOutObj);
	jQuery.tinyQE.variables.autoSave.running = false;
}

function checkAllHelper(element, checkAllId, checkId)
{
	
	var checked = jQuery(element).attr('checked');
	
	jQuery(checkAllId).attr('checked', checked);
	
	jQuery(checkId).attr('checked', checked);
}

function checkHelper(element, checkAllId, checkId)
{
	if (jQuery(element).attr('checked') === false)
		jQuery(checkAllId).attr('checked', false);
	else
		allChecked(checkId, checkAllId);
}

function resetCurrentEmail()
{
	jQuery.tinyQE.variables.currEmailId = null;
	jQuery.tinyQE.variables.usersInFields = [[], [], []];
	jQuery('#to').val('');
	jQuery('#cc').val('');
	jQuery('#bcc').val('');
	jQuery('#subject').val('');
	jQuery('#message').val('');
	jQuery('#remCcLink').trigger('click');
	jQuery('#remBccLink').trigger('click');
}

function allChecked(checkId, checkAllId)
{
	var checked = false;
	
	jQuery(checkId).each(function()
	{
		if (jQuery(this).attr('checked') === false) {
			checked = true;
			return false;
		}
	});
	
	if (!checked)
		jQuery(checkAllId).attr('checked', true);
}

function addToArrayAndBuild(userArrayKey, add)
{
	var temp = jQuery.tinyQE.variables.usersInFields[jQuery.tinyQE.variables.currFieldType];
	
	if (add)
		temp[userArrayKey] = jQuery.tinyQE.variables.availUsers[userArrayKey];
	else {
		if (typeof temp[userArrayKey] !== 'undefined')
			delete temp[userArrayKey]
	}
		
	var tempStr = '';
	
	for (k in temp) {
		tempStr += temp[k] + ', ';
	}
	
	tempStr = tempStr.substr(0, tempStr.length - 2);
	
	jQuery(jQuery.tinyQE.variables.fieldIds[jQuery.tinyQE.variables.currFieldType]).val(tempStr);
	
	jQuery('#to').trigger('keypress');
}

function buildCategories()
{
	return {
		'returnType'	: 'json',
		'returnCont'	: jQuery.tinyQE.variables.returnCont.msg,
		'categoryName'	: jQuery('#categoryName').val(),
		'isPost'		: true
	};
}

function buildUsers()
{
	return {
		'returnType'	: 'json',
		'returnCont'	: jQuery.tinyQE.variables.returnCont.msg,
		'firstName'		: jQuery('#firstName').val(),
		'middleName'	: jQuery('#middleName').val(),
		'lastName'		: jQuery('#lastName').val(),
		'email'			: jQuery('#email').val(),
		'category'		: jQuery('#category :selected').val(),
		'isPost'		: true
	};
}

/**
 * Shows or hides element.
 * 
 * @param string id Id string
 * @param bool hide If true hides element, if true shows element
 * @return
 */
function hideShow(id, hide)
{
	if (hide)
		jQuery('#' + id).hide();
	else
		jQuery('#' + id).show();
}

/**
 * Checks if we need to show separator.
 * 
 * @return bool True if we need to show it, false if we do not
 */
function needShowSep()
{
	return ((jQuery('#useCc').val() === '0') && (jQuery('#useBcc').val() === '0'));
}