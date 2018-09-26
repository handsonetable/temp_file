<?php
require_once "postconnection.php";
require_once "../../elements/dbconnect.php";
//require_once "../loc_connect.php";
session_start();
if(isset($_POST['roomid'])){
	$roomid=$_POST['roomid'];
	$milesid=$_POST['milesid'];
	$info_milesid=12889;
	$check_query= $conn->query("SELECT max(jsonb_array_length(formdata::jsonb)) FROM milestones where roomid=$roomid and milesid=$info_milesid");//get formdata index count and status=completed
	$select_check=$check_query->fetch();
	$lock=json_decode($select_check['max']);
}

if(isset($_POST['details'])) {
	if($_POST['details']=='select'){
		    //check formdata=null
			//information collection data- promoter tab
		    $select=$conn->query("select formdata from milestones WHERE milesid='".$_POST['info_miles_id']."' and roomid='".$_POST['room_id']."'");
			$row=$select->fetch();
			//formdata - postgress
			$select1=$conn->query("select formdata from milestones WHERE milesid='".$_POST['miles_id']."' and roomid='".$_POST['room_id']."'");
			$row1=$select1->fetch();
			$arraydata=array(
			"info_formdata"=>json_decode($row['formdata']),
			"count"=> count(json_decode($row['formdata'])),
			"formdata" => json_decode($row1['formdata'])
		   );
	   		echo json_encode($arraydata);
			die();
		}

	if($_POST['details']=='replace'){
		$eid=$_POST['eid'];//18055
		$room_id=$_POST['miles_id'];//12890 for postgress insert
		$did=$_POST['did'];//director id
		$action=$_POST['action'];//type
		$doc_status=$_POST['doc_status'];//val
		$updated_on=$_POST['update'];
		$updated_by=$_SESSION['id'];//1232
		$status=$_POST['d_status'];

		if(!empty($did)){

			//check formdata to insert
				$select=$conn->query("SELECT formdata from milestones WHERE milesid=$room_id and roomid=$eid");//did check and fetch
				$row1 = $select->fetch();
				$promoter=mysqli_query($dbcon,"SELECT count(id) as id FROM `dsc_processing` where director_id=$did and engagement_id=$eid and action='$action'");
				$fetch=mysqli_fetch_assoc($promoter);
			}else{
				$fetch['id']=0;
			}

		//mysqli insert/update				
			if((!$fetch['id'])>0){
				$query=mysqli_query($dbconn,"INSERT into dsc_processing (uid,engagement_id,director_id,document_status,action,verification_code,updated_by,updated_on,status) values('','$eid','$did','$doc_status','$action','','$updated_by','$updated_on','$status')"); 
					$gen_uid=mysqli_insert_id($dbcon);	//inserted id in mysql
			}else{
				$query=mysqli_query($dbcon,"UPDATE dsc_processing SET uid='',engagement_id='$eid',director_id='$did',document_status='$doc_status',action='$action',updated_by='$updated_by',updated_on='$updated_on',status='$status' WHERE director_id=$did and action='$action'");
			}

		//begin postgress_insert
			if($action=='Courier Received'){
				$type='doc_received';
				$typee='dsc_received';
			}
			else if($action=='Document Verification'){
				$type='dsc_received';
				$typee='doc_received';//update opp type
			}
			//data-array to json

			
			$data = array('doc_status' => $doc_status,'status' => $status, 'updated_by' => $updated_by,'updated_on' => $updated_on);
			$update_data=array($type => $data);
			$index=array($update_data);
			$formdata = array('id_'.$did => $index);
			$formdata1=json_encode($formdata);
			$count_form=count(json_decode($row1['formdata']));//check formdata null
			//if formdata empty
			if(($count_form)==0){
				//insert formdata
				$select_a1 =$conn->query("UPDATE milestones SET formdata='$formdata1' WHERE milesid=$room_id and roomid=$eid");
			}else{
				//check did
				$select_did=$conn->query("SELECT (formdata->'id_$did') is not null FROM milestones  WHERE milesid=$room_id and roomid=$eid");
				$row = $select_did->fetch();
				if(($row[0])>0){
					//go inside did and update 
					$update_data1=json_encode($update_data);
					$index1=json_encode($index);
					//get last index
					$last_index=$conn->query("SELECT jsonb_array_length(formdata->'id_$did')-1 from milestones WHERE milesid=$room_id and roomid=$eid");
					$row_last = $last_index->fetch();
					$select_key_exists=$conn->query("SELECT  (formdata->'id_$did'->$row_last[0]->>'$type') is not null FROM milestones WHERE milesid=$room_id and roomid=$eid");
					$row1 = $select_key_exists->fetch();//returns boolean
					//if did->index->type exists
					if(($row1[0])>0){
						echo "key exists";
						echo "UPDATE milestones SET formdata =jsonb_set(formdata,'".'{"id_'.$did.'"}'."',formdata->'id_$did' || '$index1' ::jsonb)  WHERE milesid=$room_id and roomid=$eid";
						$select_a1 =$conn->query("UPDATE milestones SET formdata =jsonb_set(formdata,'".'{"id_'.$did.'"}'."',formdata->'id_$did' || '$index1' ::jsonb)  WHERE milesid=$room_id and roomid=$eid");
					}else{
						echo "no exists";
						echo "UPDATE milestones SET formdata =jsonb_set(formdata,'".'{"id_'.$did.'",'.$row_last[0].'}'."',formdata->'id_$did'->$row_last[0] || '$update_data1'::jsonb) WHERE milesid=$room_id and roomid=$eid";
						$select_a1 =$conn->query("UPDATE milestones SET formdata =jsonb_set(formdata,'".'{"id_'.$did.'",'.$row_last[0].'}'."',formdata->'id_$did'->$row_last[0] || '$update_data1'::jsonb) WHERE milesid=$room_id and roomid=$eid");
						
					}
					
					
					
				}else{
					//create new index for did
					$select_a1 =$conn->query("UPDATE milestones SET formdata=formdata || '$formdata1' WHERE milesid=$room_id and roomid=$eid");

				}
				
			}
	die();
	}
}
?>
<style>
	/*modal alignment with scrollbar*/
	.modal-dialog{
		max-width: 80%;
	}
	.modal-body{
		min-height:400px;overflow: auto;max-height: calc(100vh - 125px);
    }
    .modal-header ul{
	    overflow-x: auto;display: -webkit-inline-box;overflow-y: hidden;
    }
    .m-tabs-line .m-tabs__link{
    	padding: 0 0 15px 0 !important;
    }
    .modal-body::-webkit-scrollbar-track,.modal-header ul::-webkit-scrollbar-track,.dropdown-menu::-webkit-scrollbar-track {
    	-webkit-box-shadow: inset 0 0 7px rgba(230,230,250,0.8);
    }
   .modal-body::-webkit-scrollbar-thumb,.modal-header ul::-webkit-scrollbar-thumb,.dropdown-menu::-webkit-scrollbar-thumb {
	    background-color: #716aca54;outline: 1px solid slategrey;border-radius: 50px;
    }
    .modal-title{
    	margin-right: 1%;white-space: nowrap;
    }
    .modal::-webkit-scrollbar{
    	width: 0;
    }
    .modal-body::-webkit-scrollbar,.modal-header ul::-webkit-scrollbar,.dropdown-menu::-webkit-scrollbar {
    	height: 6px;width: 0.3em;
    }
    .input-group-prepend select {
    	width: initial !important;
    }
    .swal2-styled{
    	background: #ffc65c !important;margin:10px auto 0px !important;
    }
    a.disabled:focus{ outline: none; }
</style>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
 	<div class="modal-header" style="padding-bottom:0px;display: none;">
		<h5 class="modal-title" id="exampleModalLabel">Courier</h5>
		<ul class="nav nav-tabs nav-fill m-tabs-line m-tabs-line--brand m-tabs-line--2x tabb" role="tablist" style="margin-bottom:0px">
			<?php if($lock>0){  $var2=3; for($tab=1;$tab<=$var2;$tab++){ ?>
		        <li class="nav-item m-tabs__item">
		            <a class="nav-link m-tabs__link" data-toggle="tab" href="#tab<?php echo $tab; ?>" style="font-size: 15px;">Promoter <?php echo $tab; ?></a>
		        </li>
	    	<?php } } ?>
	    </ul>
		<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin:0px">
	  		<span aria-hidden="true">×</span>
		</button>
	</div>
    <div class="modal-body" style="display: none;">
    	<?php if($lock>0){ ?>
    		 <!--tab end-->
	    <div class="tab-content">
	    	<?php for($tab=1;$tab<=$var2;$tab++){ ?>
		        <div class="tab-pane fade" id="tab<?php echo $tab; ?>" role="tabpanel">
		        	<!--form begin-->
		        	<form method="POST" name="form<?php echo $tab;?>" class="form<?php echo $tab; ?>" uid="">
		        		<div class="form-group m-form__group row">
					        <div class="col-lg-6">
					            <label>Courier Recieved </label>
					            <div class="input-group">
									<input type="text" class="form-control doc_status" placeholder="Select type. . .">
									<div class="input-group-append">
										<div class="btn-group">
										    <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										      Change Status
										    </button>
										    <div class="dropdown-menu"  action="Courier Received"  x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 40px, 0px);">
											  <a class="dropdown-item" data-status="0">Request DSC</a>
											  <a class="dropdown-item" data-status="1">DSC Recieved</a>
											</div>
										 </div>
									</div>
								</div>
					        </div>
				          	<div class="col-lg-6">
					            <label>Document Received On</label>
					            <input type="text" class="form-control doc_recieved_on" readonly disabled>
				          	</div>
				        </div>
				        <div class="form-group m-form__group row">
					        <div class="col-lg-6">
					            <label>Digital Signature Status</label>
					           <div class="input-group">
									<input type="text" class="form-control dsc_status" placeholder="Select type. . .">
									<div class="input-group-append">
										<div class="btn-group">
										    <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										      Change Status
										    </button>
										    <div class="dropdown-menu" action="Document Verification" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 40px, 0px);overflow: auto;height: 200px;">
											  <a class="dropdown-item" data-status="1">Document Acceptable</a>
											  <a class="dropdown-item" data-status="0">Document Missing - ID Proof</a>
											  <a class="dropdown-item" data-status="0">Document Missing - Address Proof</a>
											  <a class="dropdown-item" data-status="0">Document Missing - Application Form</a>
											  <a class="dropdown-item" data-status="0">Document Missing - ID & Address Proof</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - No Applicant Signature</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - No Gazzetted Officer Signature</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - Signature Mismatch</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - Signature with Black Pen</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - Information Filled Incorrectly</a>
											  <a class="dropdown-item" data-status="0">Document Missing - Apostille</a>
											  <a class="dropdown-item" data-status="0">Document Missing - Notary</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - No Sign on Photo</a>
											  <a class="dropdown-item" data-status="0">Document Missing - Apostille & Notary</a>
											  <a class="dropdown-item" data-status="0">Document Missing - No Photo</a>
											  <a class="dropdown-item" data-status="0">Document Missing - No Sign on Application</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - No Sign on ID Proof</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - No Sign on Address Proof</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - Unacceptable Notary Signature Submitted</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - Bank Manager Sign in Black Pen</a>
											  <a class="dropdown-item" data-status="0">Document Incorrect - Bank Manager Seal Missing</a>
											</div>
										  </div>
										</div>
								</div>
					        </div>
					        <div class="col-lg-6">
					            <label>Digital Signature Application Checked By & On</label>
					            <input type="text" class="form-control dsc_received_by"  readonly disabled>
				          	</div>
					    </div>
		        	</form>
		        </div>
    	
        	<?php } } else{ ?>
        	<div class="row">
				<div class="col-sm-2">
					<i class="fa fa-lock" style=" font-size: 5.1rem;color: #d9d7f3; margin-left: 33px;"></i>
				</div>
				<div class="col-sm-9">
					<h5 style="color: #e80a0a;position: relative;top: 30%;transform: translateY(30%);">Fill information collection to unlock Document collection!</h5>
				</div>
			</div><?php } ?>
    </div>


<script>
	$(document).ready(function() {
		//get lasttab
		  $('a[data-toggle="tab"]').click(function (e) {
			    e.preventDefault();
			    $(this).tab('show');
			});

			$('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
			    var id = $(e.target).attr("href");
			    localStorage.setItem('selecteddocTab2', id)
			});

			var selectedTab = localStorage.getItem('selecteddocTab2');
			if (selectedTab != null) {
			    $('a[data-toggle="tab"][href="' + selectedTab + '"]').tab('show');
			}else{
				$('a[data-toggle="tab"][href="#tab1"]').tab('show');
			}
		//click disabled
		$( "a[data-toggle='tab']" ).click(function() {            
		    if ($(this).hasClass('disabled') ) {
		       swal('Complete Information collection to Unlock!');
		    }     
		});
		$('.dsc_status').parent().find('button').addClass('disabled').html('Locked');
		var form={"room_id":"<?php echo $_POST['roomid']; ?>","details":"select","info_miles_id":12889,"miles_id":"<?php echo $_POST['milesid']; ?>"};
		$.ajax({
                url: 'forms/i-29-2.php',
                type: "POST",
                data: form,
				async:false,
                success: function(msg) {
                	var ss=JSON.parse(msg);
                	//promoter_name
                	if(ss.count!==0){
	                	var tab_count=$('.modal-header >ul >li').length;
	                	var i=Number(ss['count'])+Number(1);
	                	for(i;i<=tab_count;i++){
		                	$('a[data-toggle="tab"][href="#tab'+i+'"]').addClass('disabled').css("cursor","not-allowed");
		                	$('#tab'+i).remove();
	               		}
	               		for(var i=0;i<ss['info_formdata'].length;i++){

							var dname=ss['info_formdata'][i].director_name;
	               			$('a[data-toggle="tab"][href="#tab' + (i+1) + '"]').html(dname);
							$('.form'+(i+1)).attr('uid',ss['info_formdata'][i].uid);
							var did=ss['info_formdata'][i].uid;
							 //formdata=Array();
							 if(ss.formdata==null){
							 	console.log('formdata null');
							 }else{
								var formdata=ss.formdata['id_'+did];
								if(typeof formdata!='undefined'){
									
								var len=formdata.length;
									if(Number(Object.keys(formdata).length)>0){
										//console.log(Object.keys(formdata).length);
										var index=(formdata.length)-1;
										var doc_received=ss.formdata['id_'+did][index].doc_received;
										var dsc_received=ss.formdata['id_'+did][index].dsc_received;
										if((doc_received!==undefined) || (dsc_received!==undefined) ){
												if(doc_received!==undefined){
													$('.form'+(i+1)+' .doc_status').val(doc_received.doc_status);
													if((doc_received.status)==0){
														$('.form'+(i+1)+' .doc_status').parent().find('button').removeClass('btn-success btn-brand').addClass('btn-danger').html('<i class="fa	fa-exclamation-circle"></i>');
													}else if((doc_received.status)==1){

														$('.form'+(i+1)+' .dsc_status').parent().find('button').removeClass('disabled').html('Change Status');
														$('.form'+(i+1)+' .doc_status').parent().find('button').removeClass('dropdown-toggle btn-danger').addClass('btn-success disabled').html('<i class="fa fa-check"></i>');
													}
													$('.form'+(i+1)+' .doc_recieved_on').val(doc_received.updated_on);
												}else{
													//console.log(len); //no of index inside id_2904
													
													    for (var j =(len-1); j>=0;j--) {
													        if(typeof formdata[j].doc_received!='undefined'){
														        var field_data=formdata[j].doc_received.doc_status;
														        var field_status=formdata[j].doc_received.status;
														        var field_updated_on=formdata[j].doc_received.updated_on;
														        if(field_data!==null){
														        	
															        $('.form'+(i+1)+' .doc_status').val(field_data);											
															        $('.form'+(i+1)+' .doc_recieved_on').val(field_updated_on);
															        if((field_status)==0){
																		$('.form'+(i+1)+' .doc_status').parent().find('button').removeClass('btn-success btn-brand').addClass('btn-danger').html('<i class="fa	fa-exclamation-circle"></i>');
																		}else if((field_status)==1){
																			$('.form'+(i+1)+' .dsc_status').parent().find('button').removeClass('disabled').html('Change Status');
																			$('.form'+(i+1)+' .doc_status').parent().find('button').removeClass('dropdown-toggle btn-danger').addClass('btn-success disabled').html('<i class="fa fa-check"></i>');
																		
																		}
																		


														        break; //exit when it receives last recent data

														        }
														        


													        }
													    }
												}

												if(dsc_received!==undefined) {
													console.log(dsc_received);
													$('.form'+(i+1)+' .dsc_status').val(dsc_received.doc_status);
													if((dsc_received.status)==0){
														$('.form'+(i+1)+' .dsc_status').parent().find('button').removeClass('btn-success btn-brand').addClass('btn-danger').html('<i class="fa	fa-exclamation-circle"></i>');
													}else if((dsc_received.status)==1){
													$('.form'+(i+1)+' .dsc_status').parent().find('button').removeClass('dropdown-toggle btn-danger').addClass('btn-success disabled').html('<i class="fa fa-check"></i>');
													}												
													$('.form'+(i+1)+' .dsc_received_by').val(dsc_received.updated_on);
												}else{
													console.log('dsc_rec empty');
													//begin for loop
													for (var j =(len-1); j>=0;j--) {
													        if(typeof formdata[j].dsc_received!='undefined'){
														        var field_data=formdata[j].dsc_received.doc_status;
														        var field_status=formdata[j].dsc_received.status;
														        
														        //begin get old data
														        if(field_data!==null){

														        $('.form'+(i+1)+' .dsc_status').val(field_data);
														        if((field_status)==0){
																	$('.form'+(i+1)+' .dsc_status').parent().find('button').removeClass('btn-success btn-brand').addClass('btn-danger').html('<i class="fa	fa-exclamation-circle"></i>');
																	}else if((field_status)==1){
																		$('.form'+(i+1)+' .dsc_status').parent().find('button').removeClass('dropdown-toggle btn-danger').addClass('btn-success disabled').html('<i class="fa fa-check"></i>');
																	
																	}
																	//$('.form'+(i+1)+' .dsc_received_by').val(dsc_received.updated_on);
																}
																//end old data
													        }
													    }
													    //end for loop
												}

											}else{
												break;
											}
										}else{
											break;
										}
									} 	
							}
							
	               		}
	               		
	                }
	            }
            });

	
		$( "a[data-status]" ).click(function() {
			var form=$(this).closest("form");
			var uid=$(form).attr('uid');//promoter id
			var field=$(this).parent().attr('action');//type-Courier Received || Document Verification
			var data=$(this).html();//value from dropdown
			var status=$(this).data('status');//1 or 0
			var updated_on='<?php echo date("Y-m-d H:i:s"); ?>';

			//ui
			$(this).parents('.input-group').children('input').val(data);
			$(this).parents('.col-lg-6').siblings('.col-lg-6').children('input').val(updated_on);
			if(status==1){
				$(form).find('.dsc_status').parent().find('button').removeClass('disabled').html('Change Status');
				$(this).parent().siblings('button').removeClass('dropdown-toggle btn-danger').addClass('btn-success disabled').html('<i class="fa fa-check"></i>');
			}else if(status==0){
				$(this).parent().siblings('button').removeClass('btn-success btn-brand').addClass('btn-danger').html('<i class="fa	fa-exclamation-circle"></i>');
				
			}
							
			
			if((uid.length)>0){
				//ajax
			var formdata={eid:"<?php echo $_POST['roomid']; ?>",miles_id:"<?php echo $_POST['milesid']; ?>",details:"replace",did:uid,action:field,doc_status:data,update:updated_on,d_status:status};
			$.ajax({
                url: 'forms/i-29-2.php',
                type: "POST",
                data: formdata,
				async:false,
                success: function(msg) {
                	toastr.success('Updated Successfully!');
                }
            });
			}else{
				return false;	//no did
			}
		});



		$('.modal-header, .modal-body').fadeIn();
	});
	
</script>
