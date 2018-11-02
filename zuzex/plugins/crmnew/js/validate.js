











		$(document).ready(function(){
		$('#checkall').click(function(){
				if($(this).prop("checked")) {
					$(".checkBoxClass").prop("checked", true);
				} else {
					$(".checkBoxClass").prop("checked", false);
				}                
			});
	});
	$(document).click(function (e) {
     var $tgt = $(e.target);
		if (!$tgt.closest(".modal").length) {
			if($(".modal").show()){
				$(".modal").hide();
			}
		}   

	});
		function view_all(urls){
			window.location.href = urls;
		}
		function change_status(valstatus,ids,rowcounters,changestatusurl){//alert(valstatus);
			txtcolor = "";
			txtcolor1 = "";
			if(valstatus=="Classic"){
				txtcolor="black";
				$("#status"+rowcounters+"").css('color',''+txtcolor+'');
			}else if(valstatus=="Prec"){
				txtcolor1="orange";
				$("#status"+rowcounters+"").css('color',''+txtcolor1+'');
			}else if(valstatus=="Grandprec"){
				txtcolor1="red";
				$("#status"+rowcounters+"").css('color',''+txtcolor1+'');
			}
			 if(document.getElementById("img-"+rowcounters+"")){
				 document.getElementById("img-"+rowcounters+"").style.display="block";
			} 
			  
			$.ajax({
		  type: "POST",
		  url: changestatusurl,
		  data:{"id":ids,"valstatus":valstatus},
		  success: function(msg121){
			 //alert(msg121);
			 var response = $.parseJSON(msg121);	
			 //$("#pr"+ids+"").text(response['prime_prof']);
			 $("#crpr"+ids+"").text(response['class_prime']);
			 $("#crpb"+ids+"").text(response['class_bonus']);
			  $("#crpb"+ids+"").css('color',''+txtcolor+'');
			 //$("#prpr"+ids+"").text(response['prec_prime']);
			 $("#prpb"+ids+"").text(response['prec_bonus']);
			  $("#prpb"+ids+"").css('color',''+txtcolor1+'');
			$("#prfits"+ids+"").text(response['profits']);
			//$("#sum1").html("<strong>"+response['sum1']+"</strong>");
			//$("#sum2").html("<strong>"+response['sum2']+"</strong>");
			//$("#sum3").html("<strong>"+response['sum3']+"</strong>");
			$("#sum4").html("<strong>"+response['sum4']+"</strong>");
		  },error: function(){
				//alert("Error");
		  }
	}); 
	}
	
	function delete_records(pageurl,delpostid){
		if(confirm("Do you want to delete?")){
			document.location.href=""+pageurl+"/wp-admin/admin.php?page=crmnew&post_id="+delpostid+"";
		}
	}
	
	function multipledelete(){
	len=document.getElementsByName('delid[]').length;
	//alert(len);
	var flag = false; 
	for(i=0;i<len;i++){
		if(document.getElementById('delid'+i).checked  == true){
			flag = true;
			confirm_delete();	
			 break;
			//alert("A");
		}
	}
	if(flag==false){
		alert("Please select value for  delete.....");
		return false;
	}
}
function confirm_delete(){
	 if(confirm("Do you want to delete?")){
		 document.getElementById("frmevents").submit();
	 } 
}

function keywords_search(keywordsvals,pageurl){
	$.ajax({
		  type: "POST",
		  url: pageurl,
		  data:{"posttitle":keywordsvals},
		  success: function(msg1){
			 //alert(msg1);
			  $("#jobpost_cataware").css({"border":"solid #e0e0e0","padding":"4"});
			  $('#jobpost_cataware').show();
			 $('#jobpost_cataware').html(msg1);
		  },error: function(){
				//alert("Error");
		  }
	});
}

function senddivvalue(divid){
	if(divid!=""){
		document.getElementById("keywords").value = document.getElementById("divcat"+divid+"").innerHTML;
		document.getElementById("jobsearchsujjestion").style.display="none";
		$("#jobpost_cataware").css({"border":"none","padding":"0"});		 
	}
}
$(document).click(function(e) {
  if(e.target.id!="jobpost_cataware"){  // if click is not in 'mydiv'
    $('#jobpost_cataware').hide();
  }
});

function update_docusign(urldocs){
	window.location.href = urldocs;
}
function open_box(boxid,ids,pageurls){
	 document.getElementById("myModal").style.display="block";
	view_details(boxid,ids,pageurls);
}
function close_box(myModal){
	document.getElementById("myModal").style.display="none";
}
function view_details(boxid,ids,pageurls){
	$.ajax({
		  type: "POST",
		  url: pageurls,
		  data:{"id":ids,"boxid":boxid},
		  success: function(msg1){
		    //alert(msg1);
			 $("#myModal").show();
			 $("#myModal").html(msg1);
		  },error: function(){
				//alert("Error");
		  }
	});
}

function filterbycolumns(field_name,pageurl){
	$.ajax({
		  type: "POST",
		  url: pageurl,
		  data:{"field_name":field_name},
		  success: function(msg1){
			 //alert(msg1);
			  $('#'+field_name+'').css({"border":"solid #e0e0e0","padding":"4"});
			  $('#'+field_name+'').show();
			 $('#'+field_name+'').html(msg1);
		  },error: function(){
				//alert("Error");
		  }
	});
}


function filter_results(fieldnames,pageurl){
	//alert(fieldnames);	 
	$.ajax({
		  type: "POST",
		  url: pageurl,
		  data:$('.'+fieldnames+'ids:checked').serialize()+"&field_name="+fieldnames+"",
		  success: function(msg1){
			  //alert(msg1);
			$("#maintables").html(msg1);   
		  },error: function(){
				//alert("Error");
		  }
	});
}

function fetchsortby(sortby,fieldname,pageurl){	 
	$.ajax({
		  type: "POST",
		  url: pageurl,
		  data:{"sortby":sortby,"field_name":fieldname},
		  success: function(msg1){
			  //alert(msg1);
			$("#maintables").html(msg1);   
		  },error: function(){
				//alert("Error");
		  }
	});
}

function upload_attachment(ids,rownos,fields,filenos,docs,pageurls){
	// alert("A");	
	var file_data = $('#'+fields+''+rownos+'').prop('files')[0];
	//alert(file_data);
	var form_data = new FormData(); 
	form_data.append('file', file_data);
	form_data.append('id', ids);
	form_data.append('filenos', filenos);
	form_data.append('docs', docs);
	$("#divs"+fields+""+rownos+"").hide();
	$("#divsdate"+fields+""+rownos+"").hide();
	$("#divssending"+fields+""+rownos+"").show();
	$("#divssending"+fields+""+rownos+"").text("Sending.....");
	$("#divsdoc"+fields+""+rownos+"").hide();
	
	$.ajax({
		url: pageurls, // point to server-side PHP script
		dataType: 'text', // what to expect back from the PHP script, if anything
		cache: false,
		contentType: false,
		processData: false,
		data: form_data,
		type: 'post',
		success: function(datas){
			var objdatas = $.parseJSON(datas);	
			if(docs=="documentclassifieds"){
				$("#divsdocdocclassifieds"+rownos+"").addClass("afteruplodimg");
				$("#tdclassfieds"+rownos+"").addClass("afteruplodctdclassifieds");				
			}
			$("#"+fields+""+rownos+"").val("");
			$("#divssending"+fields+""+rownos+"").hide();
			//$("#divsdate"+fields+""+rownos+"").show();
			//$("#divsdate"+fields+""+rownos+"").text(objdatas['senddates']);
			$("#divs"+fields+""+rownos+"").show();
			$("#divsdoc"+fields+""+rownos+"").show();
			$("#divsdoc"+fields+""+rownos+"").html(objdatas['docs']);
			$("#divs" + fields + rownos + " > label").hide(); // Hide the first icon to prevent duplication.
			$("#divssub"+fields+""+rownos+"").show();
		}
	});
}

function upload_sign_attachment(ids,rownos,fields,filenos,docs,pageurls){
	// alert("A");	
	var file_data = $('#'+fields+''+rownos+'').prop('files')[0];
	//alert(file_data);
	var form_data = new FormData(); 
	form_data.append('file', file_data);
	form_data.append('id', ids);
	form_data.append('filenos', filenos);
	$("#divsigndocssending"+rownos+"").show();
	$("#divsignuploaddocs"+rownos+"").hide();
	$("#divopendate"+rownos+"").hide();
	$("#divsigndate"+rownos+"").hide();
	$("#divsigndocssending"+rownos+"").text("Sending.....");
	$("#divsdoc"+fields+""+rownos+"").hide();
	
	$.ajax({
		url: pageurls, // point to server-side PHP script
		dataType: 'text', // what to expect back from the PHP script, if anything
		cache: false,
		contentType: false,
		processData: false,
		data: form_data,
		type: 'post',
		success: function(datas){
			var objdatas = $.parseJSON(datas);
			//$("#divsignuploaddocs"+rownos+"").show();
			$("#divsigndocssending"+rownos+"").hide();
			$("#divsigndocssending"+rownos+"").text("");
			$("#divopendate"+rownos+"").show();
			$("#divopendate"+rownos+"").html(objdatas['opendate']);
			$("#divsigndate"+rownos+"").show();
			$("#divsigndate"+rownos+"").html(objdatas['signdate']);
			$("#tbody"+rownos+"").css("background-color","#e2f0d9");
			$("#divsigndate-1"+rownos+"").html(objdatas['certificates']);
		}
	});
}

$(document).ready(function() {
	$('.frsttblesectfr').scroll(function() { // Horizontal scroll table - move the cells
		fixateColumn();
	});

	$('tbody').scroll(function() { // Vertical scroll table - For cells that are visible on the screen, adjust the position
		$('tbody td.daupsec08').each(function() {
			if(isScrolledIntoView($(this))){
				$(this).addClass('td-visible');
			} else {
				$(this).removeClass('td-visible');
			}
		});
		fixateColumn();
	});
	  
	function isScrolledIntoView(elem) { // Check if the cells are displayed on the screen
		var $elem = $(elem);
		var $window = $(window);
	  
		var docViewTop = $window.scrollTop();
		var docViewBottom = docViewTop + $window.height();
	  
		var elemTop = $elem.offset().top;
		var elemBottom = elemTop + $elem.height();
	  
		return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
	}

	function fixateColumn() { // Fixate the first column of tdbody
		$('.td-visible').css("left", $(".frsttblesectfr").scrollLeft() - 5);
		if ($(".frsttblesectfr").scrollLeft() > 0) { // If we moved the table, then we hide the checkboxes.
			$("#frmevents .checkus5sw").hide();
		} else {
			$("#frmevents .checkus5sw").show();
			$('.td-visible').css("left", 0); // Set the cell to its original location
		}
	}

});
  