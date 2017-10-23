jQuery(function() {
    tinymce.create('tinymce.plugins.es_forms', {
        init : function(ed, url) {
            ed.addButton('es_forms', {
                title : 'add contact form',
                image : url+'/button.png',
                onclick : function() {
                		fullwidth = document.body.clientWidth;
										fullheight = document.body.clientHeight;
	
										xleft = (fullwidth-220)/2;
										xtop = (fullheight-229)/2;
									 jQuery("#es_forms_window").css({display: 'none'});
                	 jQuery("#es_forms_window").css({left: xleft, top: xtop, display: 'block'});
                	 jQuery("#es_form_select_butt").click(function() {
                	 	 if (jQuery("#es_forms_window").css("display") != 'none') {
                	 	 	formname = jQuery("#es_form_name").attr("value");
                  	 	ed.execCommand('mceInsertContent', true, '[contact name="'+formname+'"]</p><p>');
                  	 }
                  	 jQuery("#es_forms_window").css({display: 'none'});
                 	});
                  jQuery("#es_forms_window").children(".title").children(".close").click(function() {
                   	jQuery("#es_forms_window").css({display: 'none'});
                  });                 	
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
        getInfo : function() {
            return {
                longname : "easySite forms manager",
                author : 'Catalin Nita',
                authorurl : 'http://www.easySite.ro/',
                infourl : 'http://www.easySite.ro/',
                version : "1.0"
            };
        }
    });
    tinymce.PluginManager.add('es_forms', tinymce.plugins.es_forms);
});
