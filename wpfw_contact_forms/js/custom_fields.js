jQuery(document).ready(function() {
	
	/*
	jQuery(".SelectBoxOptions").each(function() {
		var selectBoxHeaderWidth = parseInt(jQuery(this).width())-10;
		var selectBoxWidth = selectBoxHeaderWidth+33;
				
		jQuery(this).css({width: selectBoxWidth});
		jQuery(this).parent().children(".SelectBoxHeader").css({width: selectBoxHeaderWidth});
				
	});*/
	
	jQuery(".SelectBoxHeader").click(function() {
		
		if (jQuery(this).parent().children(".SelectBoxOptions").css("display") == 'none') {
			jQuery(".SelectBoxHeader").css({zIndex: 2});
			var pos = jQuery(".SelectBoxHeader").offset();
			var l = parseInt(pos.left)+1;
			jQuery(".SelectBoxOptions").css({zIndex: 1, marginTop: 0});
			jQuery(".SelectBoxOptions").slideUp(0);
			
			
			jQuery(this).css({zIndex: '10'});
			jQuery(this).parent().children(".SelectBoxOptions").css({zIndex: '9'});
			jQuery(this).parent().children(".SelectBoxOptions").slideDown(100);
		}
		else {
			jQuery(this).css({zIndex: '2'});
			jQuery(this).parent().children(".SelectBoxOptions").slideUp(100, function() {
				jQuery(this).css({zIndex: '1'});
			});
		}
		
	});
	
	jQuery(".SelectBoxOption, .RadioButtonOption, .CheckboxOption, .SelectBoxHeader").mouseover(function() {
		jQuery(this).addClass("over");		
	}).mouseout(function() {
		jQuery(this).removeClass("over");		
	});
	
	jQuery(".SelectBoxOption").click(function() {	
		
		var optionValue = jQuery(this).attr("rel");
		var optionText = jQuery(this).attr("id");
		
		jQuery(this).parent("ul").parent().children(":hidden").val(optionValue);
		jQuery(this).parent("ul").parent().children(".SelectBoxHeader").html('<span class="arrow"><span></span></span>'+optionText.replace(/_/gi, " "));
		jQuery(this).parent("ul").fadeOut(0);
		jQuery(this).parent("ul").parent().children(".SelectBoxHeader").css({zIndex: '2'});
		jQuery(this).parent("ul").css({zIndex: '1'});
		
	});
	/*
	jQuery(".RadioButtonOption").click(function() {	
		var optionValue = jQuery(this).attr("id");
		jQuery(this).parent("li").parent("ul").parent().children(":hidden").val(optionValue);
		
		jQuery(this).parent("li").parent("ul").parent().children("ul").children("li").children(".RadioButtonOption").removeClass("Selected");
		jQuery(this).addClass("Selected");
	});
	*/
	jQuery(".RadioButtonOption").click(function() {	
		var optionValue = jQuery(this).attr("id");
		jQuery(this).parent("li").parent("ul").parent().children("select").val(optionValue).change();
		jQuery(this).parent("li").parent("ul").parent().children("ul").children("li").children(".RadioButtonOption").removeClass("Selected");
		jQuery(this).addClass("Selected");
	});
	jQuery(".CheckboxOption").click(function() {	
		var optionValue = jQuery(this).attr("id");
		
		if (jQuery(this).parent("li").children(":hidden").val() != optionValue) {
			jQuery(this).parent("li").children(":hidden").val(optionValue);
			jQuery(this).addClass("Selected");
		}
		else {
			jQuery(this).parent("li").children(":hidden").val("");
			jQuery(this).removeClass("Selected");
		}

	});
	
	
	jQuery(".RadioButtonOption").each(function() {
		
		var optionValue = jQuery(this).parent("li").parent("ul").parent(".field").children(":hidden").val();
		
		jQuery(this).parent("li").parent("ul").children("li").children(".RadioButtonOption").not(".Selected").removeClass("Selected");
		
		if (jQuery(this).attr("id") == optionValue) {
			jQuery(this).addClass("Selected");
		}
		
	});
	
	/* woocommerce sorting */
	jQuery(".select_order").children(".woocommerce_ordering").children(".field").children(".SelectBoxOptions").children(".SelectBoxOption").click(function() {
		jQuery(".woocommerce_ordering").submit();
	});
	
	
	
});