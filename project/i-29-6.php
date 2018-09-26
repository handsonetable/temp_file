<?php
require_once "postconnection.php";
require_once "../../elements/dbconnect.php";
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
		    //check info-coll formdata=null 
		    $select=$conn->query("select formdata from milestones WHERE milesid='".$_POST['milesid']."' and roomid='".$_POST['roomid']."'");
			$row1=$select->fetch();
			//formdata - postgress
			$select1=$conn->query("select formdata from milestones WHERE milesid='".$_POST['miles_id']."' and roomid='".$_POST['roomid']."'");
			$row2=$select1->fetch();
			$arraydata=array(
			"info_formdata"=>json_decode($row1['formdata']),
			"count"=> count(json_decode($row1['formdata'])),
			"formdata"=>json_decode($row2['formdata'])
		   );
	   		echo json_encode($arraydata);
			die();
		}
	if($_POST['details']=='replace'){
		$eid=$_POST['eid'];//18055
		$room_id=$_POST['miles_id'];//12890 for postgress insert
		$did=$_POST['did'];//director id
		//$verified_status=$_POST['formdata']['verified_status'];
		$completed_status=$_POST['formdata']['completed_status'];
		$status=$_POST['formdata']['status'];
		$index=array($_POST['formdata']);
		$formdataa=array('id_'.$did => $index);
		$formdata1=json_encode($formdataa);
		if(!empty($completed_status)){
			$doc_status=$completed_status;
			$action='DSC Obtained';
			$updated_by=$_POST['formdata']['dsc_completed_by'];
			$updated_on=$_POST['formdata']['dsc_completed_on'];
			$status=$_POST['formdata']['status'];
		}else{
			/*$doc_status='DSC Tagged';
			$action='DSC Tagged';
			$updated_by=$_POST['formdata']['dsc_verified_by'];
			$updated_on=$_POST['formdata']['dsc_verified_on'];
			$status=$_POST['formdata']['status'];*/
		}
		if($status=='Completed'){
			$status=1;
		}else{
			$status=0;
		}
		//mysqli insert
		echo "SELECT count(id) as id FROM `dsc_processing` where director_id=$did and engagement_id=$eid and action='$action'";
		$promoter=mysqli_query($dbcon,"SELECT count(id) as id FROM `dsc_processing` where director_id=$did and engagement_id=$eid and action='$action'");
		$fetch=mysqli_fetch_assoc($promoter);
		//to get uid to insert into mysql tbl
			$query_eng= $dbcon->query("SELECT m.uid from master as m  inner join engagements as e on e.uid =m.uid where e.engagement_id='$eid'");
			$select_row = mysqli_fetch_assoc($query_eng);
			$eng_uid=$select_row['uid'];//37132
		if((!$fetch['id'])>0){			
			echo "INSERT into dsc_processing (uid,engagement_id,director_id,document_status,action,verification_code,updated_by,updated_on,status) values('$eng_uid','$eid','$did','$doc_status','$action','','$updated_by','$updated_on','$status')";
			$query=mysqli_query($dbcon,"INSERT into dsc_processing (uid,engagement_id,director_id,document_status,action,verification_code,updated_by,updated_on,status) values('$eng_uid','$eid','$did','$doc_status','$action','','$updated_by','$updated_on','$status')"); 
			echo $gen_uid=mysqli_insert_id($dbcon);	//inserted id in mysql
		}else{
			echo "UPDATE dsc_processing SET uid='$eng_uid',engagement_id='$eid',director_id='$did',document_status='$doc_status',action='$action',updated_by='$updated_by',updated_on='$updated_on',status='$status' WHERE director_id=$did and action='$action'";
			$query=mysqli_query($dbcon,"UPDATE dsc_processing SET uid='$eng_uid',engagement_id='$eid',director_id='$did',document_status='$doc_status',action='$action',updated_by='$updated_by',updated_on='$updated_on',status='$status' WHERE director_id=$did and action='$action'");
		}

		//postgress
		$select=$conn->query("SELECT count(formdata) from milestones WHERE milesid=$room_id and roomid=$eid");//did check formdata || count empty 
		$row1 = $select->fetch();
		if(($row1[0])>0){ //update
			//check did exists or not
				$select_did=$conn->query("SELECT count(formdata->'id_$did') from milestones  WHERE milesid=$room_id and roomid=$eid");
				$row_did = $select_did->fetch();
				if(($row_did[0])>0){
					$update_data=json_encode($_POST['formdata']);
					//get last index
					$last_index=$conn->query("SELECT jsonb_array_length(formdata->'id_$did')-1 from milestones WHERE milesid=$room_id and roomid=$eid");
					$row_last = $last_index->fetch();
					if(($row_last[0])>0){
						$select_a1 =$conn->query("UPDATE milestones SET formdata =jsonb_set(formdata,'".'{"id_'.$did.'"}'."',formdata->'id_$did' || '$update_data' ::jsonb)  WHERE milesid=$room_id and roomid=$eid");
					}else{
						echo "no key exist";
						$select_a1 =$conn->query("UPDATE milestones SET formdata =jsonb_set(formdata,'".'{"id_'.$did.'"}'."',formdata->'id_$did' || '$update_data'::jsonb) WHERE milesid=$room_id and roomid=$eid");
					}
				}else{
					//create new index for did
					$select_a1 =$conn->query("UPDATE milestones SET formdata=formdata || '$formdata1' WHERE milesid=$room_id and roomid=$eid");
				}
			
		}else{
			$select_a1 =$conn->query("UPDATE milestones SET formdata='$formdata1' WHERE milesid=$room_id and roomid=$eid");
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
		overflow: auto;max-height: calc(100vh - 125px);
    }
    .modal-header ul{
	    overflow-x: auto;display: -webkit-inline-box;overflow-y: hidden;
    }
    .m-tabs-line .m-tabs__link{
    	padding: 0 0 15px 0 !important;
    }
    .modal-body::-webkit-scrollbar-track,.modal-header ul::-webkit-scrollbar-track {
    	-webkit-box-shadow: inset 0 0 7px rgba(230,230,250,0.8);
    }
   .modal-body::-webkit-scrollbar-thumb,.modal-header ul::-webkit-scrollbar-thumb {
	    background-color: #716aca54;outline: 1px solid slategrey;border-radius: 50px;
    }
    .modal-title{
    	margin-right: 1%;white-space: nowrap;
    }
    .modal::-webkit-scrollbar{
    	width: 0;
    }
    .modal-body::-webkit-scrollbar,.modal-header ul::-webkit-scrollbar {
    	height: 6px;width: 0.3em;
    }
    .input-group-prepend select {
    	width: initial !important;
    }
    .swal2-styled{
    	background: #ffc65c !important;margin:10px auto 0px !important;
    }
    a.disabled:focus{ outline: none; }
    .swal-button{
    	 background-color: #f9d87cfa !important; 
    }
    .swal-footer {
    text-align: center !important;
	}
	a .dropdown-item{
		color:black !important;
	}
</style>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
 	<div class="modal-header" style="padding-bottom:0px;display: none;">
		<h5 class="modal-title" id="exampleModalLabel">Issue</h5>
		<ul class="nav nav-tabs nav-fill m-tabs-line m-tabs-line--brand m-tabs-line--2x tabb" role="tablist" style="margin-bottom:0px">
			<?php if($lock>0){  $var2=3; for($tab=1;$tab<=$var2;$tab++){ ?>
		        <li class="nav-item m-tabs__item">
		            <a class="nav-link m-tabs__link" data-toggle="tab" href="#tab<?php echo $tab; ?>" style="font-size:15px;">Promoter <?php echo $tab; ?></a>
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
		        	<form method="POST" name="form<?php echo $tab;?>" class="form<?php echo $tab; ?>">
					    <!-- 
					    <div class="form-group m-form__group row">
					        <div class="col-lg-6">
					           	<label>DSC Verficiation Updated By</label>
						          <div class="input-group">
										<input type="text" class="form-control dsc_verified_by" placeholder="Verified by. . .">
										<div class="input-group-append">
											<div class="btn-group">
											    <button type="button" data-courier="1"  data-id="verification" class="btn btn-primary dsc_verify_btn" >
											     Sign!
											    </button>
											</div>
										</div>
									</div>
					        </div>
					        <div class="col-lg-6">
					           	<label>DSC Verficiation Updated On</label>
					           	<input type="text" class="form-control dsc_verified_on" readonly disabled>
				          	</div>
					    </div> -->
					    <div class="form-group m-form__group row">
					        <div class="col-lg-6">
					            <label>Digital Signature Complete </label>
					            <div class="input-group">
									<input type="text" class="form-control dsc_completed_by" placeholder="Select Digital Signature Status. . .">
									<div class="input-group-append">
										<div class="btn-group">
											    <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											      Change Status
											    </button>
											    <div class="dropdown-menu" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 40px, 0px);">
												  <a class="dropdown-item" data-courier="1" data-id="status">Completed</a>
												  <a class="dropdown-item" data-courier="-1" data-id="status">Resubmission</a>
												  <a class="dropdown-item" data-courier="0" data-id="status">Rejected</a>
												</div>
											  </div>
									</div>
								</div>
					        </div>
				          	<div class="col-lg-6">
					            <label>DSC Status Updated By & On</label>
					            <input type="text" class="form-control dsc_completed_on" name="dsc_completed_on" disabled readonly>
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
			    localStorage.setItem('issuetabs', id)
			});

			var selectedTab = localStorage.getItem('issuetabs');
			if (selectedTab != null) {
			    $('a[data-toggle="tab"][href="' + selectedTab + '"]').tab('show');
			}else{
				$('a[data-toggle="tab"][href="#tab1"]').tab('show');
			}
		//click disabled
		$( "a[data-toggle='tab']" ).click(function() {            
		    if ($(this).hasClass('disabled') ) {
		       swal('Complete Information Collection to unlock',{timer: 3000,button:true});
		    }     
		});
		var form={"roomid":"<?php echo $roomid; ?>","details":"select","milesid":12889,"miles_id":"<?php echo $milesid;?>"};
		$.ajax({
                url: 'forms/i-29-6.php',
                type: "POST",
                data: form,
				async:false,
                success: function(msg) {
                	var ss=JSON.parse(msg);
                	var tab_count=$('.modal-header >ul >li').length;
                	var i=Number(ss['count'])+Number(1);
                	for(i;i<=tab_count;i++){
	                	$('a[data-toggle="tab"][href="#tab'+i+'"]').addClass('disabled').css("cursor","not-allowed");
	                	$('#tab'+i).remove();
               		}
               		for(var i=0;i<ss['info_formdata'].length;i++){
               			$('a[data-toggle="tab"][href="#tab' + (i+1) + '"]').html(ss['info_formdata'][i].director_name);
						var did=ss['info_formdata'][i].uid;
               			$('.form'+(i+1)).attr('uid',did);
               			//formdata=Array();
							 if(ss.formdata==null){
							 	console.log('formdata null');
							 }else{
							 	/*begin to get updated data*/
							 	var formdata=ss.formdata['id_'+did];
								if(typeof formdata!='undefined'){
									
								var len=formdata.length;
									if(Number(Object.keys(formdata).length)>0){
										//console.log(Object.keys(formdata).length);
										var index=(formdata.length)-1;
										var dsc_completed_by=ss.formdata['id_'+did][index].dsc_completed_by;
										var dsc_completed_on=ss.formdata['id_'+did][index].dsc_completed_on;
										var completed_status=ss.formdata['id_'+did][index].completed_status;
										$('.form'+(i+1)+' .dsc_completed_by').attr('disabled',true).val(dsc_completed_by);	
										$('.form'+(i+1)+' .dsc_completed_on').val(dsc_completed_on);
										if(completed_status=='DSC Application Rejected'){
											$('.form'+(i+1)+' .dropdown-menu').siblings('button').addClass('btn-danger btn-success').html('<i class="fa fa-ban"></i>');
										}else if(completed_status=='Resubmit DSC Application'){
											$('.form'+(i+1)+' .dropdown-menu').siblings('button').removeClass('btn-danger btn-success').addClass('btn-danger').html('<i class="fa fa-repeat"></i>');
										}else if(completed_status=='DSC Issued'){
											$('.form'+(i+1)+' .dropdown-menu').siblings('button').removeClass('btn-danger dropdown-toggle').addClass('btn-success').attr('disabled',true).html('<i class="fa fa-check"></i>');
											$('.form'+(i+1)+' .dropdown-menu').remove();
										}
									}
								}
							/*end of get updated data*/
							}
               		}
                }
            });

		//submit
		$('.dropdown-menu a').on('click',function(e){
			e.preventDefault();
			var field=$(this).data('courier');
			var type=$(this).data('id');
			var form=$(this).parents('form').attr('class');
			var uid=$('.'+form).attr('uid');
			var uuid="id_"+uid;/*
			var dsc_verified_by=$('.'+form+' .dsc_verified_by').val();
			var dsc_verified_on=$('.'+form+' .dsc_verified_on').val();*/
			var dsc_completed_by=$('.'+form+' .dsc_completed_by').val();
			var dsc_completed_on=$('.'+form+' .dsc_completed_on').val();
			var currentdate = new Date(); 
    		var date = currentdate.getDate() + "-"
                + (currentdate.getMonth()+1)  + "-" 
                + currentdate.getFullYear() + " "  
                + currentdate.getHours() + ":"  
                + currentdate.getMinutes() + ":" 
                + currentdate.getSeconds();
			/*if(type=='verification'){
				$(this).addClass('btn-success').attr("disabled", true).html('<i class="fa fa-check"></i>');
				$('.'+form+' .dsc_verified_by').val(<?php echo $_SESSION['id']; ?>);
				$('.'+form+' .dsc_verified_on').val(date);
				dsc_verified_by=<?php echo $_SESSION['id']; ?>;
				dsc_verified_on=date;
				verified_status='DSC Tagged';
				$(this).parents('.'+form).find('.dropdown-toggle').removeAttr('disabled');
			}else*/ if(type=='status'){
				if(field==0){
					$(this).parents('.dropdown-menu').siblings('button').addClass('btn-danger btn-success').html('<i class="fa fa-ban"></i>');
					completed_status='DSC Application Rejected';
				}else if(field==-1){
					$(this).parents('.dropdown-menu').siblings('button').removeClass('btn-danger btn-success').addClass('btn-danger').html('<i class="fa fa-repeat"></i>');
					completed_status='Resubmit DSC Application';
				}else{
					$(this).parents('.dropdown-menu').siblings('button').removeClass('btn-danger dropdown-toggle').addClass('btn-success').attr('disabled',true).html('<i class="fa fa-check"></i>');
					$(this).parents('.dropdown-menu').remove();
					completed_status='DSC Issued';
				}
				$('.'+form+' .dsc_completed_by').val(<?php echo $_SESSION['id']; ?>);
				$('.'+form+' .dsc_completed_on').val(date);
				dsc_completed_by="<?php echo $_SESSION['id']; ?>";
				dsc_completed_on=date;
				//verified_status='DSC Tagged';
			}

			if((completed_status)==1){
				var status='1';
			}else{
				var status='0';
			}
			//ajax
			var formdata={"eid":"<?php echo $_POST['roomid']; ?>","miles_id":"<?php echo $_POST['milesid']; ?>","details":"replace","did":uid,"formdata":{"dsc_completed_by":dsc_completed_by,"dsc_completed_on":dsc_completed_on,"completed_status":completed_status,"status":status }};
			$.ajax({
				url: 'forms/i-29-6.php',
				type: "POST",
                data: formdata,
                async:false,
                success: function(msg) {
                	toastr.success('Updated Successfully!');
                }
			});

			console.log(formdata);
		});
		
		$('.modal-header, .modal-body').fadeIn();
	});
	
</script>
