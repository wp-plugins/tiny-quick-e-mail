(function($)
{
	$.fn.extend({
		'tinyQEAjax'			: function(opt) {
		
			if (typeof opt.url === 'undefined')
				opt.url = jQuery(this).attr('href') || null;
		
			opt = $.extend({}, $.tinyQE.defaults, opt);
			
			return $.tinyQE(this, opt);
		}
	});
	
	$.tinyQE = function(element, opt)
	{		
		$.ajax({
			'url'		 : opt.url,
			'type'		 : opt.type,
			'dataType'	 : opt.dataType,
			'data'		 : opt.data,
			'beforeSend' : function(XMLHttpRequest)
			{
				if (opt.preAjax(opt))
					return false;
			},
			'complete'	 : function(XMLHttpRequest, textStatus)
			{
				opt.postAjax($.tinyQE.variables.response, opt);
			},
			'success'	 : function(data, textStatus)
			{
				$.tinyQE.variables.response = data;
				opt.successFunc(data, opt);
			},
			'error'		 : function(XMLHttpRequest, textStatus, errorThrown)
			{
				opt.errorFunc(opt);
			}
		});
	};
	
	$.tinyQE.tinyQESuccess = function(data, opt)
	{
		if (typeof data.error !== 'undefined') {
			$.tinyQE.tinyQEError(opt, data.msg);
		} else {
			
			if (typeof opt.data === 'string') {
				var regex = /returnCont=([0-9]+)/;
				
				returnCont = opt.data.match(regex)[1];
			}
			else {
				returnCont = opt.data.returnCont;
			}
			
			if (returnCont & jQuery.tinyQE.variables.returnCont.body)
				showBody(data.body);
			if (returnCont & jQuery.tinyQE.variables.returnCont.msg)
				showMessage(data.msg, ['updated', 'customUpdated']);
		}
	};
	
	function showBody(body)
	{
		$('#menuHolder').html(body).show();
	}
	
	function showMessage(msg, clss)
	{		
		var temp = $('#messagesReport td p').removeClass().html('');
		for (var k in clss)
			temp.addClass(clss[k]);
		

		if (typeof msg === 'object') {
			var ul = $('<ol>');
			for (var k in msg)
				ul.append(
					$('<li>').text(msg[k])
				);
			temp.append(ul);
		} else 
			temp.text(msg);
		
		$('#messagesReport').show();
	}
	
	$.tinyQE.tinyQEError = function(opt, msg)
	{
		showMessage(msg || 'Error while making AJAX call.', ['error', 'customError']);
	};
	
	$.tinyQE.defaultPostAjax = function(opt)
	{
		$('#loader').hide();			
		$.tinyQE.variables.ajaxRunning = false;
	};
	
	$.tinyQE.variables = {
		'returnCont'	: {
			'body'		: 0x1,
			'msg'		: 0x2,
			'custom'	: 0x4
		},
		'response'		: null,
		'ajaxRunning'   : false,
		'ajaxUrl'		: '',
		'usersInFields'	: [[], [], []],
		'availUsers'	: [],
		'fieldIds'		: ['#to', '#cc', '#bcc'],
		'currFieldType' : 0,
		'currEmailId'	: null,
		'autoSave'		: {
			'running'		: false,
			'delay'			: 30000,
			'timeOutObj'	: null
		}
	};
	
	$.tinyQE.defaults = {
		'dataType'		: 'json',
		'type'			: 'POST',
		'successFunc'	: function(data, opt)
		{
			$.tinyQE.tinyQESuccess(data, opt);
		},
		'errorFunc'		: function(opt)
		{
			$.tinyQE.tinyQEError(opt);
		},
		'preAjax'		: function(opt)
		{
			if($.tinyQE.variables.ajaxRunning)
				return true;
			
			$('#loader').show();
			$('#messagesReport').hide();
			$.tinyQE.variables.ajaxRunning = true;
			
			return false;
		},
		'postAjax'		: function(data, opt)
		{
			$.tinyQE.defaultPostAjax(opt);
		}
	};
	
})(jQuery);