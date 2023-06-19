<?php
defined('BASEPATH') OR exit('No direct script access allowed');
define('Success', "success");
define('Failure', "failure");

class Api extends CI_Controller {

	private $data;
	
	public function __construct(){
		parent::__construct();
		$this->data = file_get_contents('php://input');
		date_default_timezone_set('Asia/Calcutta'); 
	}
	
	public function signIn(){
		$data =  json_decode($this->data);
		$email = isset($data->email) ? $data->email : '';
		$password = isset($data->password) ? md5($data->password) : '';	
		$check_exist = $this->db->where('email',$email)->where('password',$password)->get('users')->num_rows();
		if($check_exist > 0){
		   $check_exist1 = $this->db->where('email',$email)->where('password',$password)->get('users')->row_array();		   
		   $output = array(
				'status' => Success,
				'message' => 'SignIn in  Successfully',
			    'data' => $check_exist1,
            );
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Unauthorized User!.",
				'data' => []
            );
		}
		echo json_encode($output); die;
	}
    
	public function getPlans(){		
		$plandata = $this->db->order_by('plan_id','desc')->get('tbl_plans')->result_array();
		if(!empty($plandata)){
			$output = array(
				'status' => Success,
				'message' => 'Plans fetched successfully',
			    'data' => $plandata,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output); die;		
	}
	
	public function getActivePlans(){		
		$plandata = $this->db->where('status','active')->order_by('plan_id','desc')->get('tbl_plans')->result_array();
		if(!empty($plandata)){
			$output = array(
				'status' => Success,
				'message' => 'Plans fetched successfully',
			    'data' => $plandata,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output); die;		
	}
	
	//
	public function getAuctions(){
		$data =  json_decode($this->data);
		$status = $data->status;
		$auction_id = $this->db->where('status',$status)->get('tbl_auction')->result_array();
		foreach($auction_id as $key => $value){
		   $bid_data =  $this->db->select_min('bid_amount')->select('member_id')->where('auction_id',$value['auction_id'])->get('tbl_bids')->row_array();
    	   $auction_id[$key]['bid_amount'] = $bid_data['bid_amount'];
    	   $auction_id[$key]['member_id'] = $bid_data['member_id'];
		}
	   
		if(!empty($auction_id)){
			$output = array(
				'status' => Success,
				'message' => 'Auction Fetched Successfully',
			    'data' => $auction_id,
            );
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}
    public function getGroupInAPlan(){
		$data =  json_decode($this->data);
		$plan_id = $data->plan_id;
		$groupdata = $this->db->where('plan_id',$plan_id)->get('tbl_groups')->result_array();
		if(!empty($groupdata)){
			$output = array(
				'status' => Success,
				'message' => 'Groups Fetched Successfully',
			    'data' => $groupdata,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}


	public function getGroupDetails(){ 
		$data =  json_decode($this->data);		
		$plan_id = isset($data->plan_id) ? $data->plan_id : '';
	    $group_id = isset($data->group_id) ? $data->group_id : '';
	    
	   if(!empty($plan_id)){
		$group_details = $this->db->select('total_members')->where('plan_id',$plan_id)->get('tbl_groups')->result_array();
		$total_subscription = 0;
		foreach($group_details as $keys=>$values){
		    $total_subscription += $values['total_members'];
		}
		$ts_array = array(
		    'total_members' => isset($total_subscription) ? $total_subscription :'',
		    );
		$total_member = $this->db->where('plan_id',$plan_id)->where('slot_status','vacant')->get('tbl_orders')->num_rows();
		$num_member = array('total_available_member'=>$total_member);
		$newdetail = array_merge($ts_array,$num_member);
	   }else{
	       
	    $group_details = $this->db->select('total_members')->where('group_id',$group_id)->get('tbl_groups')->result_array();
	    $getPlan_id = $this->db->where('group_id',$group_id)->get('tbl_groups')->row_array();
		$total_subscription = 0;
		foreach($group_details as $keys=>$values){
		    $total_subscription += $values['total_members'];
		}
		$ts_array = array(
		    'total_members' => isset($total_subscription) ? $total_subscription :'',
		);
		$total_member = $this->db->where('plan_id',$getPlan_id['plan_id'])->where('group_id',$group_id)->where('slot_status','vacant')->get('tbl_orders')->num_rows();
		$num_member = array('total_available_member'=>$total_member);
		$newdetail = array_merge($ts_array,$num_member);  
	   
	   }
	    
	   if(!empty($plan_id)){
		  $member_id = $this->db->where('plan_id',$plan_id)->where('slot_status !=','cancelled')->order_by('order_id','asc')->get('tbl_orders')->result_array();
	   }else{
	     $group_details = $this->db->where('group_id',$group_id)->get('tbl_groups')->row_array();
	     $member_id = $this->db->where('plan_id',$group_details['plan_id'])->where('group_id',$group_id)->where('slot_status !=','cancelled')->order_by('order_id','asc')->get('tbl_orders')->result_array();
	   }

		$group_plan_data = array();
	    $i=1;
		foreach($member_id as $keys=>$values){
			
		   $group_name = $this->db->select('group_name')->where('group_id',$values['group_id'])->get('tbl_groups')->row_array();
		   if($values['slot_status'] != 'vacant'){
				if($values['member_id'] != 0){
					$group_members = $this->db->select('member_id,name,mobile')->where('member_id',$values['member_id'])->get('tbl_members')->row_array();
				}else{
					$group_members = [];
				}

				if(!empty($plan_id)){
					$plan_data = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
					$chit_taken = $this->db->where('member_id',$values['member_id'])->where('plan_id',$plan_id)->where('group_id',$values['group_id'])->where('slot_number',$values['slot_number'])->get('tbl_chits')->row_array();
					$amount  = $this->convertAmountCurrency($plan_data['plan_amount']);
				}else{
					$plan_data = $this->db->where('plan_id',$values['plan_id'])->get('tbl_plans')->row_array();
					$chit_taken = $this->db->where('member_id',$values['member_id'])->where('plan_id',$values['plan_id'])->where('group_id',$values['group_id'])->where('slot_number',$values['slot_number'])->get('tbl_chits')->row_array();
					$amount  = $this->convertAmountCurrency($plan_data['plan_amount']);   
				}
			
				$sql = "SELECT tbl_subscriber_collateral.* FROM `tbl_subscriber_collateral`
						INNER JOIN `tbl_collateral_master` ON `tbl_subscriber_collateral`.collateral_id = `tbl_collateral_master`.collateral_id
						WHERE tbl_subscriber_collateral.member_id =".$values['member_id'];
				
				$coll = $this->db->query($sql)->row_array();
				$riskCal = $this->memberRiskCalculation($values['member_id']);

				$group_members['slot_number'] = isset($values['slot_number']) ? $values['slot_number'] : '-';
				$group_members['group_name'] = isset($group_name['group_name']) ? $group_name['group_name'] : '-';
				$group_members['slot_status'] = isset($values['slot_status']) ? $values['slot_status'] : '-';
				$group_members['order_id'] = isset($values['order_id']) ? $values['order_id'] : '-';
				$group_members['chit_taken'] = isset($chit_taken['added_date']) ? date('d-M',strtotime($chit_taken['added_date'])) : '-';
				$group_members['forgo_amount'] = isset($chit_taken['forgo_amount']) ? $chit_taken['forgo_amount'] : '';
				$group_members['collateral_description'] = isset($coll['collateral_name']) ? $coll['collateral_name'].'-'.$coll['collateral_sub_name'].'-'.$coll['subscription_locked'].$coll['estimated_amount'].'-'.$coll['exemption_reason'].$coll['exemption_amount'] : '-';
				$group_members['riskCal'] = isset($riskCal['data']) ? $riskCal['data'] : '-';
				$group_plan_data[] = array_merge($group_members,$plan_data);

		   }else{
		   		// $group_members = $this->db->where('member_id',69)->get('tbl_members')->row_array();
				$group_members['name'] = "-";
				$group_members['mobile'] = "-";
				$group_members['group_name'] = isset($group_name['group_name']) ? $group_name['group_name'] : '-';
				$group_members['slot_number'] = isset($values['slot_number']) ? $values['slot_number'] : '-';
				$group_members['slot_status'] = isset($values['slot_status']) ? $values['slot_status'] : '-';
				$group_members['order_id'] = isset($values['order_id']) ? $values['order_id'] : '-';
				$group_members['chit_taken'] = '-';
				$group_members['forgo_amount'] = '-';
				$group_members['collateral_description'] = '-';
				$group_members['risk_cal'] = '-';
		   if(!empty($plan_id)){
		   $plan_data = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
		   }else{
		     $plan_data = $this->db->where('plan_id',$values['plan_id'])->get('tbl_plans')->row_array();  
		   }
		   $group_plan_data[] = array_merge($group_members,$plan_data);
		   }
		   	$i++;
		}        
		
		if(!empty($group_plan_data)){
			$output = array(
				'status' => "Success",
				'message' => 'Group Members Fetched Successfully',
			    'data' => $group_plan_data,
			    'group_details' => $newdetail,
            );
		}else{
			$output = array(
				'status' => "Failure",
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}
	
	public function memberRiskCalculation($member_id){
		$getall_member_emi = $this->db->where('member_id',$member_id)->get('tbl_emi')->result_array();
		$sum_of_due = 0;
		foreach($getall_member_emi as $keys => $values){
			if($values['emi_status'] == 'due'){
				if($values['is_partial_payment'] == 'Yes'){
					$sum_of_due += $values['amount_due'];
				}
				else{
					$sum_of_due += $values['plan_emi'];
				}
			}
		}
		$sum_of_paid = 0;
		foreach($getall_member_emi as $keys=>$values){
			if($values['emi_status'] == 'paid'){
				$sum_of_paid += $values['plan_emi'];
			}
		}
		$calculate_of_risk = $sum_of_paid - $sum_of_due;
		$due_paid = array(
		    'total_emi_due'=>$sum_of_due,
		    'total_emi_paid'=>$sum_of_paid
		    );
		   

		if(!empty($calculate_of_risk)){
			$output = array(
			 'status' => Success,
			 'message' => 'GET Collateral Fetched Successfully',
			 'data' => isset($calculate_of_risk) ? $calculate_of_risk :'0',
			);
		}else{
		   $output = array(
			'status' => Failure,
			'message' => "GET Collateral Unsuccessfully",
			'data' => '0'
		   );     
		}
		return $output;
	}
	
	function convertAmountCurrency($number){
	   $length = strlen($number);
       $currency = '';

        if($length == 6 || $length == 7)
        {
            if($number <= 900000){
              $number1 = substr($number,0,-5);
              $value = "0".$number1;
              $ext = "L";
              $currency = $value.$ext;
            }else{
               $value = substr($number,0,-5);
               $ext = "L";
               $currency = $value.$ext;
            }
            
        }elseif($length == 8 || $length == 9){
           if($number >= 10000000){
              $number1 = substr($number,0,-7);
              $value = $number1;
              $ext = "Cr";
              $currency = $value.$ext;  
            } 
        }
        return $currency;
	}
	
public function getMembersInAuction(){ // 10jan2022		
		$data =  json_decode($this->data);
		$auction_id = isset($data->auction_id) ? $data->auction_id : '';
		$type = isset($data->type) ? $data->type : '';
		
		$getplangroup = $this->db->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
		$plan_id = isset($getplangroup['plan_id']) ? $getplangroup['plan_id'] : '';
		$group_id = isset($getplangroup['group_id']) ? $getplangroup['group_id'] : '';
    	if($type == "combined"){
    // 		$getorder_data = $this->db->select('member_id,order_id,slot_number')->where('group_id !=',$group_id)->where('slot_status','assigned')->where('plan_id',$plan_id)->get('tbl_orders')->result_array(); // update to not get groups
    // 		$getorder = array();
    // 		foreach($getorder_data as $key=>$val){
    // 		    $check_data = $this->db->where('slot_number',$val['slot_number'])->where('plan_id',$getplangroup['plan_id'])->where('auction_id !=',$auction_id)->get('tbl_bids')->num_rows();
    // 		    if($check_data == 0){
    // 		        $getorder[] = array(
    // 		           'member_id' =>  $val['member_id'],
    // 		           'order_id' =>  $val['order_id'],
    // 		            );
    // 		    }
    // 		}
    		
    // 		$getorder_data2 = $this->db->select('member_id,order_id,slot_number')->where('group_id',$group_id)->where('slot_status','assigned')->where('plan_id',$plan_id)->get('tbl_orders')->result_array(); 
    // 		foreach($getorder_data2 as $key=>$val){
    // 		  //  $check_data = $this->db->where('slot_number',$val['slot_number'])->where('plan_id',$getplangroup['plan_id'])->where('auction_id !=',$auction_id)->get('tbl_bids')->num_rows();
    // 		  //  if($check_data == 0){
    // 		        $getorder[] = array(
    // 		           'member_id' =>  $val['member_id'],
    // 		           'order_id' =>  $val['order_id'],
    // 		            );
    // 		  //  }
    // 		}
    
    
            $getorder = array();
    	    $getorder2 = $this->db->select('member_id,order_id,slot_number')->where('slot_status','assigned')->where('plan_id',$plan_id)->get('tbl_orders')->result_array(); 
    	    foreach($getorder2 as $key=>$val){
    	        $member_id = isset($val['member_id']) ? $val['member_id'] :'';
    	        $order_id = isset($val['order_id']) ? $val['order_id'] :'';
    	        $check_chitd = $this->db->where('member_id',$member_id)->where('slot_number',$val['slot_number'])->where('plan_id',$plan_id)->get('tbl_chits')->num_rows();
    	        if($check_chitd < 1){
    	            $getorder[] = array(
    	                'member_id' =>  $val['member_id'],
    		           'order_id' =>  $val['order_id'],
    	                );
    	        }
    	    }
    		
    	}else{
    	   // echo json_encode($type);die;
    	   // $getorder = $this->db->select('member_id,order_id')->where('slot_status','assigned')->where('plan_id',$plan_id)->where('group_id',$group_id)->get('tbl_orders')->result_array(); // update to not get groups
    	   $getorder = array();
    	    $getorder2 = $this->db->select('member_id,order_id,slot_number')->where('slot_status','assigned')->where('plan_id',$plan_id)->where('group_id',$group_id)->get('tbl_orders')->result_array(); 
    	    foreach($getorder2 as $key=>$val){
    	        $member_id = isset($val['member_id']) ? $val['member_id'] :'';
    	        $order_id = isset($val['order_id']) ? $val['order_id'] :'';
    	       // $check_chitd = $this->db->where('member_id',$member_id)->where('slot_number',$val['slot_number'])->where('plan_id',$plan_id)->where('group_id',$group_id)->get('tbl_chits')->num_rows();
    	       $check_chitd = $this->db->where('member_id',$member_id)->where('slot_number',$val['slot_number'])->where('plan_id',$plan_id)->get('tbl_chits')->num_rows();
    	        if($check_chitd < 1){
    	            $getorder[] = array(
    	                'member_id' =>  $val['member_id'],
    		           'order_id' =>  $val['order_id'],
    	                );
    	        }
    	    }
    	}
      if(!empty($getorder)){
		$getdata = array(); $getdata1 =array();
		foreach($getorder as $keys=>$values){
		$member_id = isset($values['member_id']) ? $values['member_id'] : '';
		$check_member_to_chit =  $this->db->where('member_id',$member_id)->get('tbl_chits')->num_rows();
			if(empty($check_member_to_chit)){				
				    $getmemberdetail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
                    $slot_number = $this->db->where('order_id',$values['order_id'])->get('tbl_orders')->row_array();
                    $new_array['slot_number'] = isset($slot_number['slot_number']) ? $slot_number['slot_number'] : '';
                    $blank_array =array();
                    if(isset($getmemberdetail) && is_array($getmemberdetail)){
                        $getdata[]= array_merge($getmemberdetail,$new_array);
                    }
			    	$getdata1 = array_filter($getdata);
			}else{
			   	$getmemberdetail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
    		    $slot_number = $this->db->where('order_id',$values['order_id'])->get('tbl_orders')->row_array();
    		    $new_array['slot_number'] = isset($slot_number['slot_number']) ? $slot_number['slot_number'] : '';
                    $blank_array =array();
                    if(isset($getmemberdetail) && is_array($getmemberdetail)){
                        $getdata[]= array_merge($getmemberdetail,$new_array);
                    }
				    $getdata1 = array_filter($getdata); 
			}
		}
		
		if(!empty($getdata1)){
			$output = array(
				'status' => Success,
				'message' => 'Memebers Details Fetched Successfully',
			    'data' => $getdata1,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
      }else{
        $output = array(
		'status' => Failure,
		'message' => "Invalid Data.",
		'data' => []
        );   
      }
		echo json_encode($output);die;
	}
	
	public function getMembersInAuction2(){ // 10jan2022  this for select member by name    		
		$data =  json_decode($this->data);
		$auction_id = isset($data->auction_id) ? $data->auction_id : '';
		$type = isset($data->type) ? $data->type : '';
		$member_name = isset($data->name) ? $data->name : '';
		
		$getplangroup = $this->db->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
		$plan_id = isset($getplangroup['plan_id']) ? $getplangroup['plan_id'] : '';
		$group_id = isset($getplangroup['group_id']) ? $getplangroup['group_id'] : '';
    	if($type == "combined"){
    		$getorder_data = $this->db->select('member_id,order_id,slot_number')->where('group_id !=',$group_id)->where('slot_status','assigned')->where('plan_id',$plan_id)->like('member_name',$member_name)->get('tbl_orders')->result_array(); // update to not get groups
    		$getorder = array();
    		foreach($getorder_data as $key=>$val){
    		    $check_data = $this->db->where('slot_number',$val['slot_number'])->where('plan_id',$getplangroup['plan_id'])->where('auction_id !=',$auction_id)->get('tbl_bids')->num_rows();
    		    if($check_data == 0){
    		        $getorder[] = array(
    		           'member_id' =>  $val['member_id'],
    		           'order_id' =>  $val['order_id'],
    		            );
    		    }
    		}
    		
    		$getorder_data2 = $this->db->select('member_id,order_id,slot_number')->where('group_id',$group_id)->where('slot_status','assigned')->where('plan_id',$plan_id)->get('tbl_orders')->result_array(); 
    		foreach($getorder_data2 as $key=>$val){
    		  //  $check_data = $this->db->where('slot_number',$val['slot_number'])->where('plan_id',$getplangroup['plan_id'])->where('auction_id !=',$auction_id)->get('tbl_bids')->num_rows();
    		  //  if($check_data == 0){
    		        $getorder[] = array(
    		           'member_id' =>  $val['member_id'],
    		           'order_id' =>  $val['order_id'],
    		            );
    		  //  }
    		}
    		
    	}else{
    	   // echo json_encode($type);die;
    	    $getorder = $this->db->select('member_id,order_id')->where('slot_status','assigned')->like('member_name',$member_name)->where('plan_id',$plan_id)->where('group_id',$group_id)->get('tbl_orders')->result_array(); // update to not get groups
    	}
      if(!empty($getorder)){
		$getdata = array(); $getdata1 =array();
		foreach($getorder as $keys=>$values){
		$member_id = isset($values['member_id']) ? $values['member_id'] : '';
		$check_member_to_chit =  $this->db->where('member_id',$member_id)->get('tbl_chits')->num_rows();
			if(empty($check_member_to_chit)){				
				    $getmemberdetail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
                    $slot_number = $this->db->where('order_id',$values['order_id'])->get('tbl_orders')->row_array();
                    $new_array['slot_number'] = isset($slot_number['slot_number']) ? $slot_number['slot_number'] : '';
                    $blank_array =array();
                    if(isset($getmemberdetail) && is_array($getmemberdetail)){
                        $getdata[]= array_merge($getmemberdetail,$new_array);
                    }
			    	$getdata1 = array_filter($getdata);
			}else{
			   	$getmemberdetail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
    		    $slot_number = $this->db->where('order_id',$values['order_id'])->get('tbl_orders')->row_array();
    		    $new_array['slot_number'] = isset($slot_number['slot_number']) ? $slot_number['slot_number'] : '';
                    $blank_array =array();
                    if(isset($getmemberdetail) && is_array($getmemberdetail)){
                        $getdata[]= array_merge($getmemberdetail,$new_array);
                    }
				    $getdata1 = array_filter($getdata); 
			}
		}
		
		if(!empty($getdata1)){
			$output = array(
				'status' => Success,
				'message' => 'Memebers Details Fetched Successfully',
			    'data' => $getdata1,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
      }else{
        $output = array(
		'status' => Failure,
		'message' => "Invalid Data.",
		'data' => []
        );   
      }
		echo json_encode($output);die;
	}
	
	

	public function getAuctionDetails(){
		$data =  json_decode($this->data);
		$auction_id = $data->auction_id;
		$getauction = $this->db->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
		if(!empty($getauction)){
			$output = array(
				'status' => Success,
				'message' => 'Auction Details Fetched Successfully',
			    'data' => $getauction,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}
	
public function saveBidByAgent(){ 
		$data =  json_decode($this->data);
		$member_id = isset($data->member_id) ? $data->member_id : '' ;
		$auction_id = isset($data->auction_id) ? $data->auction_id : '';
		$for_go_amount = isset($data->for_go_amount) ? $data->for_go_amount : ''; // bid amount 
		$agent_id = isset($data->agent_id) ? $data->agent_id : '';
		$forman_fees = isset($data->forman_fees) ? $data->forman_fees : '';
		$slot_number = isset($data->slot_number) ? $data->slot_number : '';

		$daata = $this->db->select('forgo_amount')->where('auction_id',$auction_id)->get('tbl_bids')->result_array();
		$plan_id =    $this->db->select('plan_id,group_id')->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
		$group_id = isset($plan_id['group_id']) ? $plan_id['group_id'] : '';
		$plan_name =  $this->db->select('plan_name')->where('plan_id',$plan_id['plan_id'])->get('tbl_plans')->row_array();
	    $member_name =    $this->db->select('name')->where('member_id',$member_id)->get('tbl_members')->row_array();
	    $plan_amount_detail = $this->db->where('plan_id',$plan_id['plan_id'])->get('tbl_plans')->row_array();
		$plan_amount = isset($plan_amount_detail['plan_amount']) ? $plan_amount_detail['plan_amount'] : '';
		$remaining_month = isset($plan_amount_detail['remaining_month']) ? $plan_amount_detail['remaining_month'] : '';
		$bid_amount = $plan_amount - $for_go_amount;
		$cost_of_chit_taken = ($bid_amount/$remaining_month)/($plan_amount - $bid_amount);	
		$max_bid_amount = ($plan_amount_detail['plan_amount']*$plan_amount_detail['max_bid'])/100; 
	  if(!empty($daata)){
		foreach($daata as $keys=>$values){
			$datasa = isset($values['forgo_amount']) ? $values['forgo_amount'] : '';
			if ($datasa < $for_go_amount  && $for_go_amount > $forman_fees && $for_go_amount <= $max_bid_amount ){
			  	$bid_is_bid = 'big';				
			}else{
				$bid_is_bid = 'small';
			}
		}
		if($bid_is_bid == 'small'){
			$output = array(
				'status' => Failure,
				'message' => "You must bid more than the previous bid",
				'data' => []
			);
		}else{
			if(!empty($agent_id)){
				$agent_ids = 1;
		   }else{
			   $agent_ids = 0;
		   }
		   
		   $data = array(
			 'auction_id' => isset($auction_id) ? $auction_id : '',
			 'plan_id' => isset($plan_id['plan_id']) ? $plan_id['plan_id'] : '',
			 'plan_name' => isset($plan_name['plan_name']) ? $plan_name['plan_name'] : '',
			 'group_id' => isset($group_id) ? $group_id : '',
			 'member_id' => isset($member_id) ? $member_id : '',
			 'is_bid_accepted' =>  'no',
			 'agent_id' => isset($agent_id) ? $agent_id : '',
			 'forgo_amount' =>  $for_go_amount,
			 'cost_of_chit_taken' => isset($cost_of_chit_taken) ? $cost_of_chit_taken : '',
			 'bid_amount' =>  isset($bid_amount) ? $bid_amount : '',
			 'member_name' => isset($member_name['name']) ? $member_name['name'] : '',
			 'is_added_by_agent' => isset($agent_ids) ? $agent_ids : '',
			 'slot_number' => isset($slot_number) ? $slot_number : '',
			 'added_date' => date('Y-m-d h:i:s')
		   );
		   $this->db->insert('tbl_bids',$data);
		   $insert_id = $this->db->insert_id();
   
		   if(!empty($insert_id)){
			   $output = array(
				   'status' => Success,
				   'message' => 'Bid Save Successfully',
				   'data' => [],
			   );	
		   }else{
			   $output = array(
				   'status' => Failure,
				   'message' => "Invalid Data.",
				   'data' => []
			   );
		   }
		}
	  }else{
	      
	      if($forman_fees < $for_go_amount && $for_go_amount < $max_bid_amount){
	
	        if(!empty($agent_id)){
				$agent_ids = 1;
		   }else{
			   $agent_ids = 0;
		   }
		   
		   $data = array(
			 'auction_id' => isset($auction_id) ? $auction_id : '',
			 'plan_id' => isset($plan_id['plan_id']) ? $plan_id['plan_id'] : '',
			 'plan_name' => isset($plan_name['plan_name']) ? $plan_name['plan_name'] : '',
			 'group_id' => isset($group_id) ? $group_id : '',
			 'member_id' => isset($member_id) ? $member_id : '',
			 'is_bid_accepted' =>  'no',
			 'agent_id' => isset($agent_id) ? $agent_id : '',
			 'forgo_amount' =>  $for_go_amount,
			 'cost_of_chit_taken' => isset($cost_of_chit_taken) ? $cost_of_chit_taken : '',
			 'bid_amount' =>  isset($bid_amount) ? $bid_amount : '',
			 'member_name' => isset($member_name['name']) ? $member_name['name'] : '',
			 'is_added_by_agent' => isset($agent_ids) ? $agent_ids : '',
			 'slot_number' => isset($slot_number) ? $slot_number : '',
			 'added_date' => date('Y-m-d h:i:s')
		   );
		   
		   $this->db->insert('tbl_bids',$data);
		   $insert_id = $this->db->insert_id();
   
		   if(!empty($insert_id)){
			   $output = array(
				   'status' => Success,
				   'message' => 'Bid Save Successfully',
				   'data' => [],
			   );	
		   }else{
			   $output = array(
				   'status' => Failure,
				   'message' => "Invalid Data.",
				   'data' => []
			   );
		   }
		
	      }else{
	        $output = array(
				'status' => Failure,
				'message' => 'Bid amount should be more than the minimum bid allowed ',
			    'data' => [],
            );   
	      }
	  }
		echo json_encode($output);die;		
	}
	
	public function bidsByAgent(){
	   	$data =  json_decode($this->data);
		$agent_id = $data->agent_id; 
		
		$bid_details = $this->db->where('agent_id',$agent_id)->get('tbl_bids')->result_array();
		if(!empty($bid_details)){
		    $output = array(
				'status' => Success,
				'message' => 'Bids Details Fetched Successfully',
			    'data' => $bid_details,
            );	
		}else{
		    $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            ); 
		}
	   echo json_encode($output);die;
	}

	public function memberAddedByAgent(){
	   	$data =  json_decode($this->data);
		$agent_id = $data->agent_id; 
		$members_details = $this->db->get('tbl_members')->result_array();
		if(!empty($members_details)){
		    $output = array(
				'status' => Success,
				'message' => 'Members Details Fetched Successfully',
			    'data' => $members_details,
            );	
		}else{
		    $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            ); 
		}
	   echo json_encode($output);die;
	}
	
	// clone set krna hai one day fequency m 
	//create function plan exipre 
	
	public function plansPurchasedByMember(){
	    $data =  json_decode($this->data);
		$member_id = $data->member_id; 
		$status = $data->status; 
		$members_details = $this->db->where('member_id',$member_id)->where('status',$status)->get('tbl_orders')->result_array();
		$all_member_detail = array();
		foreach($members_details as $keys=>$values){
		    $plan_id = $values['plan_id'];
		    $plan_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
		    $given_plan_detail = array(
		        'plan_name' =>isset($plan_detail['plan_name']) ? $plan_detail['plan_name'] : '' ,
		        'months_completed' =>isset($plan_detail['months_completed']) ? $plan_detail['months_completed'] : '',
		        'total_months' =>isset($plan_detail['total_months']) ? $plan_detail['total_months'] : '',
		        'total_subscription' =>isset($plan_detail['total_subscription']) ? $plan_detail['total_subscription'] : '',
		        );
		    $chit_detail = $this->db->where('plan_id',$plan_id)->where('member_id',$member_id)->where('slot_number',$values['slot_number'])->get('tbl_chits')->row_array();
		    $chit_array = array(
		        'return_chit_amount'=> isset($chit_detail['return_chit_amount']) ? $chit_detail['return_chit_amount'] : '',
		        'forgo_amount'=> isset($chit_detail['forgo_amount']) ? $chit_detail['forgo_amount'] : '',
		        'chit_amount'=> isset($chit_detail['chit_amount']) ? $chit_detail['chit_amount'] : '',
		        );
		    $all_detail = array_merge($chit_array,$given_plan_detail,$values);
		    $all_member_detail[] = $all_detail;
		   
		}
		if(!empty($all_member_detail)){
		    $output = array(
				'status' => Success,
				'message' => 'Plans Purchased By Member Fetched Successfully',
			    'data' => $all_member_detail,
            );	
		}else{
		    $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            ); 
		}
	   echo json_encode($output);die;
	}
	
	public function getMembersDetails(){
	    $data =  json_decode($this->data);
		$member_id = isset($data->member_id) ? $data->member_id : ''; 
		$members_details = $this->db->where('member_id',$member_id)->get('tbl_members')->result_array();
		if(!empty($members_details)){
		    $output = array(
				'status' => Success,
				'message' => 'Members Details Fetched Successfully',
			    'data' => $members_details,
            );	
		}else{
		    $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            ); 
		}
	   echo json_encode($output);die;
	}
	
	public function getPlansDetails(){
	    $data =  json_decode($this->data);
		$plan_id = $data->plan_id; 
		$plans_details = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->result_array();
		if(!empty($plans_details)){
		    $output = array(
				'status' => Success,
				'message' => 'Plans Details Fetched Successfully',
			    'data' => $plans_details,
            );	
		}else{
		    $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            ); 
		}
	   echo json_encode($output);die;
	}
	
	
	public function addMember(){
	    $data =  json_decode($this->data);
	    $member_id = isset($data->member_id) ? $data->member_id : '';
		$member_name = isset($data->name) ? $data->name : ''; 
		$member_profile = isset($data->member_profile) ? $data->member_profile : 'no-image.png'; 
		$last_name = isset($data->last_name) ? $data->last_name : ''; 
		$father_name = isset($data->father_name) ? $data->father_name : ''; 
		$dob = isset($data->dob) ? $data->dob : ''; 
		$mobile = isset($data->mobile) ? $data->mobile : '';
	    $secondary_mobile = isset($data->secondary_mobile) ? $data->secondary_mobile : '';
	    $office_phone = isset($data->office_phone) ? $data->office_phone : '' ;
	    $email = isset($data->email) ? $data->email : '';
	    $permanent_address = isset($data->address) ?  $data->address  : '';
	    $current_potal_address = isset($data->postal_address)  ? $data->postal_address : '' ;
	    $reference = isset($data->reference)  ? $data->reference : '' ;
	    $gender = isset($data->gender)  ? $data->gender : '' ;
	    $marital_status = isset($data->marital_status) ? $data->marital_status : '';
	    $subscriber_type = isset($data->subscriber_type) ? $data->subscriber_type : '';
	    $spouse_name = isset($data->spouse_name) ? $data->spouse_name : '' ;
	    $annivarsary_date = isset($data->annivarsary_date) ? $data->annivarsary_date : '';
	    $no_of_kids = isset($data->no_kids) ?  $data->no_kids : '';
	    $no_of_depends = isset($data->dependents) ? $data->dependents : '' ;
	    $village_city_name = isset($data->village_city_name) ? $data->village_city_name : '' ;
	    $district = isset($data->district) ? $data->district : '' ;
	    $state = isset($data->state) ? $data->state : '' ;
	    $address_pincode = isset($data->address_pincode) ? $data->address_pincode : '' ;
	    $nature_of_business = isset($data->nature_of_business) ? $data->nature_of_business : '' ;
	    $business_start_date = isset($data->business_start_date) ? $data->business_start_date : '' ;
	    $no_of_nominee = isset($data->no_of_nominee) ? $data->no_of_nominee : '' ;
	    $nominee_name = isset($data->nominee_name) ? $data->nominee_name : '' ;
	    $nominee_relationship = isset($data->nominee_relation) ? $data->nominee_relation : '' ;
	    $nominee_d_o_b = isset($data->nominee_dob) ? $data->nominee_dob : '' ;
	    $percentage_of_nomination = isset($data->nominee_precentage) ? $data->nominee_precentage : '';
	    $nominee_gaurdian_name = isset($data->nominee_gaurdian_name) ? $data->nominee_gaurdian_name : '' ;
	    $pan_number = isset($data->pan_no) ? $data->pan_no : '' ;
	    $income_type = isset($data->income_type) ?  $data->income_type : '';
	    $company_name = isset($data->company_name) ?  $data->company_name : '' ;
	    $company_type = isset($data->company_type) ? $data->company_type : '' ;
	    $designation = isset($data->designation) ?  $data->designation : '';
	    $work_address = isset($data->work_address) ? $data->work_address : '';
	    $salary = isset($data->salary) ? $data->salary : '' ;
	    $other_income = isset($data->other_income)  ? $data->other_income : '';
	    $experience = isset($data->experience) ? $data->experience : '';
	    $professional_service =isset($data->professional_service) ? $data->professional_service : '';
	    $office_address = isset($data->office_address) ? $data->office_address : '';
	    $employee_no =  isset($data->employee_no) ? $data->employee_no : '';
	    $gst_no = isset($data->gst_no) ?  $data->gst_no : '';
	    $annual_turnover =  isset($data->annual_turnover) ? $data->annual_turnover : '' ;
	    $income_source = isset($data->income_source) ? $data->income_source : '' ;
	    $monthly_income = isset($data->monthly_income) ? $data->monthly_income : '' ;
	    $car_category =  isset($data->car_category) ?  $data->car_category : '';
	    $two_wheeler_category = isset($data->two_wheeler_category) ? $data->two_wheeler_category : '';
	    $house_category = isset($data->house_category) ? $data->house_category : '' ;
	    $identity_category =  isset($data->identity_category) ?  $data->identity_category : '';
	    $address_category = isset($data->address_category) ?  $data->address_category : '';
	    $agent_id =  isset($data->agent_id) ? $data->agent_id : '';
	    $agent_comission  = isset($data->agent_comission) ? $data->agent_comission : '';
	    $adhaar_number = isset($data->adhaar_number) ? $data->adhaar_number : ''  ;
	    $docs = isset($data->docs) ? $data->docs : ''  ;
	    $incorporation_certificate = isset($data->incorporation_certificate) ? $data->incorporation_certificate : ''  ;
	    
	    if(!empty($agent_id)){
	        $is_added_by_agent = 1;
	    }else{
	        $is_added_by_agent = 0;
	    }
	    
	    $submit_data = array(
	     'name' =>   isset($member_name) ? $member_name : '',
	     'last_name' =>   isset($last_name) ? $last_name : '',
	     'father_name' => isset($father_name) ? $father_name : '',
	     'dob' => isset($dob) ? $dob : '',
	     'mobile' => isset($mobile) ? $mobile : '',
	     'secondary_mobile' => isset($secondary_mobile) ? $secondary_mobile : '',
	     'subscriber_type' => isset($subscriber_type) ? $subscriber_type : '',
	     'office_phone' => isset($office_phone) ? $office_phone : '',
	     'email' => isset($email) ? $email : '',
	     'permanent_address' => isset($permanent_address) ? $permanent_address : '',
	     'current_potal_address' => isset($current_potal_address) ? $current_potal_address : '',
	     'reference' => isset($reference) ? $reference : '',
	     'gender' => isset($gender) ? $gender : '',
	     'marital_status' => isset($marital_status) ? $marital_status : '',
	     'spouse_name' => isset($spouse_name) ? $spouse_name : '',
	     'village_city_name' => isset($village_city_name) ? $village_city_name : '',
	     'district' => isset($district) ? $district : '',
	     'state' => isset($state) ? $state : '',
	     'address_pincode' => isset($address_pincode) ? $address_pincode : '',
	     'nature_of_business' => isset($nature_of_business) ? $nature_of_business : '',
	     'business_start_date' => isset($business_start_date) ? $business_start_date : '',
	     'annivarsary_date' => isset($annivarsary_date) ? $annivarsary_date : '',
	     'no_of_kids' =>    isset($no_of_kids) ? $no_of_kids : '',
	     'no_of_depends' => isset($no_of_depends) ? $no_of_depends : '',
	     'no_of_nominee' => isset($no_of_nominee) ? $no_of_nominee : '',
	     'nominee_name' => isset($nominee_name) ? $nominee_name : '',
	     'nominee_relationship' => isset($nominee_relationship) ? $nominee_relationship : '',
	     'nominee_d_o_b' => isset($nominee_d_o_b) ? $nominee_d_o_b : '',
	     'percentage_of_nomination' => isset($percentage_of_nomination) ? $percentage_of_nomination : '',
	     'nominee_gaurdian_name' => isset($nominee_gaurdian_name) ? $nominee_gaurdian_name : '',
	     'pan_number' => isset($pan_number) ? $pan_number : '',
	     'income_type' => isset($income_type) ? $income_type : '',
	     'company_name' => isset($company_name) ? $company_name : '',
	     'company_type' => isset($company_type) ? $company_type : '',
	     'designation' => isset($designation) ? $designation : '',
	     'work_address' => isset($work_address) ? $work_address : '',
	     'salary' =>  isset($salary) ? $salary : '',
	     'other_income' => isset($other_income) ? $other_income : '',
	     'experience'  => isset($experience) ? $experience : '',
	     'office_address' => isset($office_address) ? $office_address : '',
	     'employee_no' => isset($employee_no) ? $employee_no : '',
	     'gst_no' => isset($gst_no) ? $gst_no : '',
	     'annual_turnover'  => isset($annual_turnover) ? $annual_turnover : '',
	     'income_source'  => isset($income_source) ? $income_source : '',
	     'monthly_income' => isset($monthly_income) ? $monthly_income : '',
	     'car_category' => isset($car_category) ? $car_category : '',
	     'two_wheeler_category' => isset($two_wheeler_category) ? $two_wheeler_category : '',
	     'house_category'  => isset($house_category) ? $house_category : '',
	     'identity_category' => isset($identity_category) ? $identity_category : '',
	     'address_category'  => isset($address_category) ? $address_category : '',
	     'is_added_by_agent' => isset($is_added_by_agent) ? $is_added_by_agent : '',
	     'agent_id' => isset($agent_id) ? $agent_id : '',
	     'agent_comission' => isset($agent_comission) ? $agent_comission : '',
	     'adhaar_number' => isset($adhaar_number) ? $adhaar_number : '',
	     'incorporation_certificate' => isset($incorporation_certificate) ? $incorporation_certificate : '',
	     'docs' => isset($docs) ? $docs : '',
	     'added_date' => date('Y-m-d h:i:s'),
	     'member_profile' => isset($member_profile) ? $member_profile : '',
		 
	    );
	    
	   if($member_id == ''){
		$submit_data['subscriber_id'] = $this->get_subdcriber_id();
	    $this->db->insert('tbl_members',$submit_data);
	    $insert_id = $this->db->insert_id();
	    $members_details = $this->db->where('member_id',$insert_id)->get('tbl_members')->row_array();
	    
	    $users_detail = array(
	        'member_id' => $insert_id,
	        'username' => isset($members_details['name']) ? $members_details['name'] :'',
	        'mobile' => isset($members_details['mobile']) ? $members_details['mobile'] : '',
	        'email' => isset($members_details['email']) ? $members_details['email'] :'',
	        'password' => md5('123'),
	        'type' => 'user' ,
	        'added_date' => date('y-m-d H:i:s'),
	        );
	   $this->db->insert('users',$users_detail);
	   }else{
	      $this->db->where('member_id',$member_id)->update('tbl_members',$submit_data);
	      $members_details = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
	   }
		if(!empty($members_details)){
		    $output = array(
				'status' => Success,
				'message' => 'Members Details Fetched Successfully',
			    'data' => $members_details,
            );	
		}else{
		    $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            ); 
		}
	   echo json_encode($output);die;
	}
	
	public function getAgentDetails(){
	    $data =  json_decode($this->data);
		$agent_id = $data->agent_id; 
	   if($agent_id != ''){
	    $getAgentDetails = $this->db->where('agent_id',$agent_id)->get('tbl_agent')->row_array();
	    if(!empty($getAgentDetails)){
	         $output = array(
				'status' => Success,
				'message' => 'Agent Details Fetched Successfully',
			    'data' => $getAgentDetails,
            );	
	    }else{
	        $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );  
	    }
	   }else{
	       $output = array(
				'status' => Failure,
				'message' => "Invalid Agent Id.",
				'data' => []
            );   
	   }
	    echo json_encode($output);die;
	}
	
	
	public function updateMembers(){
	    $data =  json_decode($this->data);
	    $member_id = isset($data->member_id) ? $data->member_id : ''  ;
		
	   if($member_id !=''){
    	  $submit_data = array(
    	     'name' =>   isset($data->name) ? $data->name : ''  ,
    	     'last_name' =>   isset($data->last_name) ? $data->last_name : ''  ,
    	     'father_name' => isset($data->father_name) ? $data->father_name : ''  ,
    	     'dob' => isset($data->dob) ? $data->dob : ''  ,
    	     'mobile' => isset($data->mobile) ? $data->mobile : ''  ,
    	     'secondary_mobile' => isset($data->secondary_mobile) ? $data->secondary_mobile : ''  ,
    	     'subscriber_type' => isset($data->subscriber_type) ? $data->subscriber_type : ''  ,
    	     'office_phone' => isset($data->office_phone) ? $data->office_phone : ''  ,
    	     'email' => isset($data->email) ? $data->email : ''  ,
    	     'permanent_address' => isset($data->permanent_address) ? $data->permanent_address : ''  ,
    	     'current_potal_address' => isset($data->current_potal_address) ? $data->current_potal_address : ''  ,
    	     'reference' => isset($data->reference) ? $data->reference : ''  ,
    	     'gender' => isset($data->gender) ? $data->gender : ''  ,
    	     'marital_status' => isset($data->marital_status) ? $data->marital_status : ''  ,
    	     'spouse_name' => isset($data->spouse_name) ? $data->spouse_name : ''  ,
    	     'village_city_name' => isset($data->village_city_name) ? $data->village_city_name : ''  ,
    	     'district' => isset($data->district) ? $data->district : ''  ,
    	     'state' => isset($data->state) ? $data->state : ''  ,
    	     'address_pincode' => isset($data->address_pincode) ? $data->address_pincode : ''  ,
    	     'nature_of_business' => isset($data->nature_of_business) ? $data->nature_of_business : ''  ,
    	     'business_start_date' => isset($data->business_start_date) ? $data->business_start_date : ''  ,
    	     'annivarsary_date' => isset($data->annivarsary_date) ? $data->annivarsary_date : ''  ,
    	     'no_of_kids' =>    isset($data->no_of_kids) ? $data->no_of_kids : ''  ,
    	     'no_of_depends' => isset($data->no_of_depends) ? $data->no_of_depends : ''  ,
    	     'no_of_nominee' => isset($data->no_of_nominee) ? $data->no_of_nominee : ''  ,
    	     'nominee_name' => isset($data->nominee_name) ? $data->nominee_name : ''  ,
    	     'nominee_relationship' => isset($data->nominee_relationship) ? $data->nominee_relationship : ''  ,
    	     'nominee_d_o_b' => isset($data->nominee_d_o_b) ? $data->nominee_d_o_b : ''  ,
    	     'percentage_of_nomination' => isset($data->percentage_of_nomination) ? $data->percentage_of_nomination : ''  ,
    	     'nominee_gaurdian_name' => isset($data->nominee_gaurdian_name) ? $data->nominee_gaurdian_name : ''  ,
    	     'pan_number' => isset($data->pan_number) ? $data->pan_number : ''  ,
    	     'income_type' => isset($data->income_type) ? $data->income_type : ''  ,
    	     'company_name' => isset($data->company_name) ? $data->company_name : ''  ,
    	     'company_type' => isset($data->company_type) ? $data->company_type : ''  ,
    	     'designation' => isset($data->designation) ? $data->designation : ''  ,
    	     'work_address' => isset($data->work_address) ? $data->work_address : ''  ,
    	     'salary' =>  isset($data->salary) ? $data->salary : ''  ,
    	     'other_income' => isset($data->other_income) ? $data->other_income : ''  ,
    	     'experience'  => isset($data->experience) ? $data->experience : ''  ,
    	     'office_address' => isset($data->office_address) ? $data->office_address : ''  ,
    	     'employee_no' => isset($data->employee_no) ? $data->employee_no : ''  ,
    	     'gst_no' => isset($data->gst_no) ? $data->gst_no : ''  ,
    	     'annual_turnover'  => isset($data->annual_turnover) ? $data->annual_turnover : ''  ,
    	     'income_source'  => isset($data->income_source) ? $data->income_source : ''  ,
    	     'monthly_income' => isset($data->monthly_income) ? $data->monthly_income : ''  ,
    	     'car_category' => isset($data->car_category) ? $data->car_category : ''  ,
    	     'two_wheeler_category' => isset($data->two_wheeler_category) ? $data->two_wheeler_category : ''  ,
    	     'house_category'  => isset($data->house_category) ? $data->house_category : ''  ,
    	     'identity_category' => isset($data->identity_category) ? $data->identity_category : ''  ,
    	     'address_category'  => isset($data->address_category) ? $data->address_category : ''  ,
    	     'is_added_by_agent' => isset($data->is_added_by_agent) ? $data->is_added_by_agent : ''  ,
    	     'agent_id' => isset($data->agent_id) ? $data->agent_id : ''  ,
    	     'agent_comission' => isset($data->agent_comission) ? $data->agent_comission : ''  ,
    	     'adhaar_number' => isset($data->adhaar_number) ? $data->adhaar_number : ''  ,
    	     'incorporation_certificate' => isset($data->incorporation_certificate) ? $data->incorporation_certificate : ''  ,
    	     'docs' => isset($data->docs) ? $data->docs : ''  ,
    	     'update_date' => date('Y-m-d h:i:s')
    	    );
		
		$this->db->where('member_id',$member_id)->update('tbl_members',$submit_data);
		$output = array(
			'status' => Success,
			'message' => 'Update Members Successfully',
            );
	   }else{
	      $output = array(
				'status' => Failure,
				'message' => "Invalid Member Id.",
				'data' => []
            );   
	   }
	   echo json_encode($output);die;
	}
	
	public function addAgent(){
	    $data =  json_decode($this->data);
	    $first_name = $data->first_name; 
		$last_name = $data->last_name; 
		$email = $data->email; 
		$phone = $data->phone;
		$gst_no = $data->gst_no;
		$company = $data->company; 
		$payment_terms = $data->payment_terms; 
		$address = $data->address; 
		$city = $data->city;
		$state = $data->state;
		$country = $data->country; 
		$data = array(
            'first_name' => isset($first_name) ? $first_name : '',
            'last_name' => isset($last_name) ? $last_name : '',
            'email' => isset($email) ? $email : '',
            'phone' => isset($phone) ? $phone : '',
            'gst_no' => isset($gst_no) ? $gst_no : '',
            'company' => isset($company) ? $company : '',
            'payment_terms' => isset($payment_terms) ? $payment_terms : '',
            'address' => isset($address) ? $address : '',
            'city' => isset($city) ? $city : '',
            'state' => isset($state) ? $state : '',
            'country' => isset($country) ? $country : '',
            'added_date' => date('Y-m-d h:i:s')
        );
        
		$this->db->insert('tbl_agent',$data);
		$insert_id = $this->db->insert_id(); 
	   if(!empty($insert_id)){
		$output = array(
			'status' => Success,
			'message' => 'Agent  Successfully',
			'data' => $insert_id
            );
	   }else{
	      $output = array(
				'status' => Failure,
				'message' => "Invalid Agent Id.",
				'data' => []
            );   
	   }
	   echo json_encode($output);die;
	}
	
	public function getAllAgentDetails(){
	    $getAgentDetails = $this->db->get('tbl_agent')->result_array();
	    if(!empty($getAgentDetails)){
	         $output = array(
				'status' => Success,
				'message' => 'Agent Details Fetched Successfully',
			    'data' => $getAgentDetails,
            );	
	    }else{
	        $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );  
	    }
	    echo json_encode($output);die;
	}
	
	public function getAllAgent(){
	    $getAgentDetails = $this->db->where('agent_id',$agent_id)->get('tbl_agent')->row_array();
	    if(!empty($getAgentDetails)){
	         $output = array(
				'status' => Success,
				'message' => 'Agent Details Fetched Successfully',
			    'data' => $getAgentDetails,
            );	
	    }else{
	        $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );  
	    }
	    echo json_encode($output);die;
	}
	
	public function getAllMemberDetails(){
	    $getMemberDetails = $this->db->order_by('member_id','desc')->get('tbl_members')->result_array();
	    if(!empty($getMemberDetails)){
	         $output = array(
				'status' => Success,
				'message' => 'Members Details Fetched Successfully',
			    'data' => $getMemberDetails,
            );	
	    }else{
	        $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );  
	    }
	    echo json_encode($output);die;
	}
	
	public function addPlan(){  
	    $data =  json_decode($this->data);
	    $plan_id = isset($data->plan_id) ? $data->plan_id : '' ;
	    if(!empty($plan_id)){
	        $this->db->where('plan_id',$plan_id)->delete('tbl_plans');
	        $this->db->where('plan_id',$plan_id)->delete('tbl_groups');
	        $this->db->where('plan_id',$plan_id)->delete('tbl_orders');
	        $this->db->where('plan_id',$plan_id)->delete('tbl_general_ledger_master');
	    }
	    $plan_name = isset($data->plan_name) ? $data->plan_name : '' ; 
		$admission_fee = isset($data->admission_fee) ? $data->admission_fee : '' ; 
		$plan_amount = isset($data->plan_amount) ? $data->plan_amount : ''; 
		$tenure = isset($data->tenure) ? $data->tenure : '' ;
		$start_month = isset($data->start_month) ? $data->start_month : '';
		$agent_commission = isset($data->agent_commission) ? $data->agent_commission : ''; 
		$emi = isset($data->emi) ? $data->emi : ''; 
		$foreman_fees = isset($data->foreman_fees) ? $data->foreman_fees : '';
		$min_prize_amount = isset($data->min_prize_amount) ? $data->min_prize_amount : '';
		$total_months = isset($data->total_months) ? $data->total_months : '';
		$groups_counts = isset($data->groups_counts) ? $data->groups_counts : ''; 
		$end_date_for_subscription = isset($data->end_date_for_subscription) ?  $data->end_date_for_subscription : '';
		$max_bid = isset($data->max_bid) ?  $data->max_bid : '';
		$auction_type = isset($data->auction_type) ?  $data->auction_type : '';
		$variable_auction_percentage = isset($data->variable_auction_percentage) ?  $data->variable_auction_percentage : '';
		$plan_gst = isset($data->plan_gst) ?  $data->plan_gst : '';
		$min_prize_amount = ($plan_amount) -($plan_amount * $max_bid/100);
		$min_bid_amount = $plan_amount * $foreman_fees/100;
	    $total_subscription = $tenure * $groups_counts;
        $emi = $plan_amount / $tenure;
        
               if($plan_amount < 100000){
                   $admission_fee = 0.04;
               }if($plan_amount > 99999 && $plan_amount < 1000000){
                   $admission_fee = 0.025;
               }
               if($plan_amount > 999999){
                   $admission_fee = 0.01;
               }
               $admission_amount = $admission_fee / 100 * $plan_amount;

		 $data = array(
             'plan_name' =>   isset($plan_name) ?  $plan_name : '',
             'admission_fee' =>   isset($admission_fee) ?  $admission_fee : '',
             'admission_amount' =>   isset($admission_amount) ?  $admission_amount : '',
             'plan_amount' =>   isset($plan_amount) ?  	$plan_amount : '',
             'tenure' =>   isset($tenure) ?  $tenure : '',
             'start_month' =>   isset($start_month) ?  $start_month : '',
             'agent_commission' =>   isset($agent_commission) ?  $agent_commission : '',
             'emi' =>   isset($emi) ?  $emi : '',
			 'foreman_fees' =>   isset($foreman_fees) ?  $foreman_fees : '',
			 'min_prize_amount' =>   isset($min_prize_amount) ?  $min_prize_amount : '',
             'total_subscription' =>   isset($total_subscription) ?  $total_subscription : '',  
             'max_bid' =>   isset($max_bid) ?  $max_bid : '',  
             'remaining_month' =>   isset($tenure) ?  $tenure : '',  
             'months_completed' => '0',  
             'total_months' =>   isset($tenure) ?  $tenure : '',  
             'groups_counts' =>   isset($groups_counts) ?  $groups_counts  : '',  
             'end_date_for_subscription' =>   isset($end_date_for_subscription) ?  $end_date_for_subscription : '', 
             'auction_type' =>   isset($auction_type) ?  $auction_type : '', 
             'variable_auction_percentage' =>   isset($variable_auction_percentage) ?  $variable_auction_percentage : '', 
             'plan_gst' =>   isset($plan_gst) ?  $plan_gst : '',
             'min_bid_amount' =>   isset($min_bid_amount) ?  $min_bid_amount : '', 
             'status' => 'active',
             'added_date' => date('y-m-d h:i:s')
            );
			$this->db->insert('tbl_plans',$data);
	    	$insert_id = $this->db->insert_id();

	    	
    
		    $ParticiDetails = isset($groups_counts) ?  $groups_counts  : '';		
			$char_arr = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
			for($kk=0; $kk<$ParticiDetails; $kk++){
				$data2 = array(
				'plan_id'=> isset($insert_id) ?  $insert_id : '',				
				'total_members' => isset($tenure) ?  $tenure : '',	
				'group_name' => isset($char_arr[$kk]) ?  $char_arr[$kk] : '',
				'added_date' => date('Y-m-d h:i:s')
			);			
			$this->db->insert('tbl_groups',$data2);			
		   }
		 $group_data = $this->db->select('group_id,total_members,plan_id')->where('plan_id',$insert_id)->get('tbl_groups')->result_array();
		 if(!empty($group_data)){
		     $sum_of_slot_no = 1;
	    foreach($group_data as $key => $value){
	      $total_member = isset($value['total_members']) ? $value['total_members'] : '';
	      $group_id = isset($value['group_id']) ? $value['group_id'] : '';
	      $group_name = isset($value['group_name']) ? $value['group_name'] : '';
          $group_data1 = $this->db->where('plan_id',$insert_id)->where('group_id',$group_id)->get('tbl_orders')->num_rows();
	       	if($group_data1 >= $total_member){
	              $output = array(
            		'status' => Success,
            		'message' => "All Order Slot Booked",
            		'data' => []
                  ); 
                continue;
	       	}else{
	         	$emi = round($emi, 0);
				for($i=1; $i<=$total_member; $i++){
					$amount  = $this->convertAmountCurrency($plan_amount);
					if($i < 10){
					$a = 0;
					$slot_number = date('m',strtotime($start_month)).date('y',strtotime($start_month)).$amount.$value['plan_id'].$a.$sum_of_slot_no;
					}else{
					$slot_number = date('m',strtotime($start_month)).date('y',strtotime($start_month)).$amount.$value['plan_id'].$sum_of_slot_no;  
					}
					$sum_of_slot_no ++;
					$data = array(
						'member_id' => isset($member_id) ? $member_id : '',
						'plan_id'   => isset($insert_id) ? $insert_id : '',
						'group_id'  => isset($group_id) ? $group_id : '',
						'member_name' => isset($member_name) ? $member_name : '',
						'plan_amount' => isset($plan_amount) ? $plan_amount : '',
						'start_month' => isset($start_month) ? $start_month : '',
						'emi' => isset($emi) ? $emi : '',
						'tenure' => isset($tenure) ? $tenure : '',
						'months_completed' => 0,
						'agent_commission' => isset($agent_commission)  ? $agent_commission : '',
						'end_month' => isset($end_date_for_subscription) ? $end_date_for_subscription : '',
						'total_months' => isset($tenure) ? $tenure : '',
						'groups_count'  => isset($groups_counts) ? $groups_counts : '',
						'admission_fees'    => isset($admission_fee) ? $admission_fee : '',
						'agent_id'  => isset($agent_id) ? $agent_id : '',
						'is_added_by_agent' => isset($is_added_by_agent) ? $is_added_by_agent : '0',
						'transaction_id' => isset($transaction_id) ? $transaction_id : '',
						'payment_mode' => "offline",
						'slot_number' => isset($slot_number) ? $slot_number : '',
						'added_date' => date('Y-m-d h:i:s')
					);
					
					$this->db->insert('tbl_orders',$data);
					$insert_id1 = $this->db->insert_id();
					
				}
	       	}
			
	  	}
		  $data = $this->AllotSlotToCompany($insert_id);
		  $create_plan_general_legder  = $this->create_plan_general_legder($insert_id);
		  if(!empty($insert_id1)){
			  $output = array(
				  'status' => Success,
				  'message' => 'Save Order Successfully',
				  'data' => [],
			  );	 
		  }else{
			  $output = array(
				  'status' => Failure,
				  'message' => "Invalid Data.",
				  'data' => []
			  ); 
		  }
	 }else{
	    $output = array(
    		'status' => Failure,
    		'message' => "Data not found.",
    		'data' => []
            );  
	 }
	  echo json_encode($output); die;
	}


	public function planUpdate(){
		$data =  json_decode($this->data);
		$plan_id = $data->plan_id;
		$data= $this->db->Where('plan_id',$plan_id)->get('tbl_plans')->row_array();
		if(!empty($data)){
			$output = array(
				'status' => Success,
				'message' => 'Plan Details Fetched Successfully',
			    'data' => $data,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}
	
	public function planUpdateDetails(){
	    $data =  json_decode($this->data);
	    $plan_id = $data->plan_id; 
	    $plan_name = $data->plan_name; 
		$admission_fee = $data->admission_fee; 
		$plan_amount = $data->plan_amount; 
		$tenure = $data->tenure;
		$start_month = $data->start_month;
		$agent_commission = $data->agent_commission; 
		$emi = $data->emi; 
	    $foreman_fees = $data->foreman_fees;
		$min_prize_amount = $data->min_prize_amount;
		$total_subscription = $data->total_subscription; 
		$months_completed = $data->months_completed;
		$total_months = $data->total_months;
		$groups_counts = $data->groups_counts; 
		$end_date_for_subscription = $data->end_date_for_subscription; 
     
		 $data = array(
             'plan_name' =>   isset($plan_name) ?  $plan_name : '',
             'admission_fee' =>   isset($admission_fee) ?  $admission_fee : '',
             'plan_amount' =>   isset($plan_amount) ?  	$plan_amount : '',
             'tenure' =>   isset($tenure) ?  $tenure : '',
             'start_month' =>   isset($start_month) ?  $start_month : '',
             'agent_commission' =>   isset($agent_commission) ?  $agent_commission : '',
             'emi' =>   isset($emi) ?  $emi : '',
             'foreman_fees' =>   isset($foreman_fees) ?  $foreman_fees : '',
			 'min_prize_amount' =>   isset($min_prize_amount) ?  $min_prize_amount : '',
             'total_subscription' =>   isset($total_subscription) ?  $total_subscription : '',  
             'months_completed' =>   isset($months_completed) ?  $months_completed : '',  
             'total_months' =>   isset($total_months) ?  $total_months : '',  
             'groups_counts' =>   isset($groups_counts) ?  $groups_counts  : '',  
             'end_date_for_subscription' =>   isset($end_date_for_subscription) ?  $end_date_for_subscription : '', 
             'added_date' => date('y-m-d h:i:s')
            );
         if(!empty($plan_id)){   
	    	$this->db->where('plan_id',$plan_id)->update('tbl_plans',$data);
    		$output = array(
    			'status' => Success,
    			'message' => 'Plans Update Successfully',
    			'data' => []
            );
	   }else{
	      $output = array(
				'status' => Failure,
				'message' => "Invalid Agent Id.",
				'data' => []
            );   
	   }
	   echo json_encode($output);die;
	}
	
	public function getEmiInPlan(){	  	
		$data =  json_decode($this->data);
		$member_id = isset($data->member_id) ? $data->member_id : ''; 
		$plan_id_detail = $this->db->where('member_id',$member_id)->get('tbl_emi')->row_array();
		$plan_id = isset($plan_id_detail['plan_id']) ? $plan_id_detail['plan_id'] : '';
		$plan_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
		$months_completed = isset($plan_detail['months_completed']) ? $plan_detail['months_completed'] : '';		
		$new_arr = array();
		for ($i=1; $i<=$months_completed; $i++){
			$getchitemi = $this->db->where('emi_status','due')->where('emi_no',$i)->where('member_id',$member_id)->get('tbl_emi')->result_array();			
			$new_arr[] = $getchitemi;
		}
		$newarray = array();
		foreach($new_arr as $key=>$value){
			foreach( $value as $keys=>$values ){
			  $newarray[] = $values['emi_id'];
			}			
		}
		$newchitarray = array();
		foreach( $newarray as $key=>$values){
			$getchit = $this->db->where('emi_id',$values)->get('tbl_emi')->row_array();
			if(!empty($getchit['divident'])){
			    $getchit['plan_emi'] = ($getchit['plan_emi'] - $getchit['divident']);
			   	$newchitarray[] = $getchit;
			}else{
			   $newchitarray[] = $getchit;
			}
		}
		if(!empty($newchitarray) && !empty($newchitarray)){			 
	       $output = array(
			'status' => Success,
			'message' => 'Emi Fetched Successfully',
			'data' => $newchitarray
            );
	   }else{
	       $output = array(
				'status' => Failure,
				'message' => "Invalid data.",
				'data' => []
            );    
	   }
	   echo json_encode($output); die;
	}

	public function GetSlotsEmies(){
		$data =  json_decode($this->data);
		$slot_number = isset($data->slot_number) ? $data->slot_number :'';
		$plan_id = isset($data->plan_id) ? $data->plan_id :'';
		$getchit = $this->db->where('slot_number',$slot_number)->where('plan_id',$plan_id)->get('tbl_emi')->result_array();
		$plan_data = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
		$months_completed = isset($plan_data['months_completed']) ? $plan_data['months_completed'] : '';		
		$current_dues = [];
		foreach($getchit as $key=>$val){
			if($val['emi_no'] <= $months_completed){
				$current_dues[] = $val;
			}
		}

		$all_emi = [];
		$total_dues = 0;
		foreach( $current_dues as $key=>$values){
			if(!empty($values['divident'])){
			    $emi = ($values['plan_emi'] - $values['divident']);
			}else{
				$emi = $values['plan_emi'];
			}
			$all_emi[] = array(
				'emi_id' => $values['emi_id'],
				'amount' => $emi,
				'plan_id' => $values['plan_id']
			);
			$total_dues += $emi;
		}

		if($total_dues != 0){			 
			$output = array(
				'status' => Success,
				'message' => 'Emi Fetched Successfully',
				'data' => $total_dues
				);
		}else{
			$output = array(
					'status' => Failure,
					'message' => "Invalid data.",
					'data' => $total_dues
				);    
		}
	   	echo json_encode($output); die;
	}

	public function paySlotsCurrentmies($slot_number,$plan_id){
		$slot_number = isset($slot_number) ? $slot_number :'';
		$plan_id = isset($plan_id) ? $plan_id :'';
		$getchit = $this->db->where('slot_number',$slot_number)->where('plan_id',$plan_id)->get('tbl_emi')->result_array();
		$plan_data = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
		$months_completed = isset($plan_data['months_completed']) ? $plan_data['months_completed'] : '';		
		$current_dues = [];
		foreach($getchit as $key=>$values){
			if($values['emi_no'] <= $months_completed){
				$current_dues[] = $values;
				$values['id'] = $values['emi_id'];
				$values['amount'] = $values['emi_id'];
				
			}
		}

		$all_emi = [];
		$total_dues = 0;
		foreach( $current_dues as $key=>$values){
			if(!empty($values['divident'])){
			    $emi = ($values['plan_emi'] - $values['divident']);
			}else{
				$emi = $values['plan_emi'];
			}
			$all_emi[] = array(
				'id' => $values['emi_id'],
				'amount' => $emi,
				'plan_id' => $values['plan_id']
			);
			$total_dues += $emi;
		}
		$bank_account_id = 1;
		$payment_mode = "Online";
		foreach($all_emi as $key=>$val){
			$this->PayDuesGeneralLedger($val,$bank_account_id,$payment_mode);
		}
	}
	
	public function payEmi(){
	    $data =  json_decode($this->data);
	    $emi_id = $data->emi_id; 
		$emi_amount = $data->emi_amount; 
		$status = $data->status; 

	   if(!empty($emi_id) &&  !empty($emi_amount) && !empty($status)){		   
	      if($status == 'chit_emi'){
			$data = $this->db->where('chit_emi_id',$emi_id)->get('chit_emi')->row_array();
			if(!empty($data)){
				if($data['emi_status'] == 'due'){
					if($data['chit_emi'] == $emi_amount){
						$update_status = array(
						'emi_status' =>'paid'
					);
					$update = $this->db->where('chit_emi_id',$emi_id)->update('chit_emi',$update_status);
					}else{
						$output = array(
							'status'=>'failed',
							'message'=>' Amount doesnot matched.'
						);
					}
				}
				if($data['emi_status'] == 'paid'){
					$output = array(
						'status' => 'failed',
						'message' => ' Emi paid already'
							);
				}
			  }  
			}			
		  
		  	if($status == 'plan_emi'){
			$data = $this->db->where('emi_id',$emi_id)->get('tbl_emi')->row_array();
			if(!empty($data)){
				if($data['emi_status'] == 'due'){
					if($data['plan_emi'] == $emi_amount){
						$update_status = array(
						'emi_status' =>'paid'
					);
					$update = $this->db->where('emi_id',$emi_id)->update('tbl_emi',$update_status);
					}else{
						$output = array(
							'status'=>'failed',
							'message'=>' Amount doesnot matched.'
						);
					}
				}
				if($data['emi_status'] == 'paid'){
					$output = array(
						'status' => 'failed',
						'message' => ' Emi paid already'
							);
				}
			}						
			}  	
			if(!empty($update)){
				$output = array(
					'status'=>'success',
					'message'=>'payed emi successfully',
					'data'=>[]
				);				
			}	
			if(empty($data)){
				$output = array(
					'status'=>'failed',
					'message'=>'No record found for this emi id.',
					'data'=>[]
				);				
			}	 
		}
		else{
			$output = array(
				'status'=>'failed',
				'message'=>'in valid data',
				'data'=>[]
			);
		}
	   echo json_encode($output); die;
	}
	
	
	public function getPlansAvailableForAuction(){		
		$plandata = $this->db->order_by('plan_id','desc')->get('tbl_plans')->result_array();
		if(!empty($plandata)){
			$output = array(
				'status' => Success,
				'message' => 'Plans fetched successfully',
			    'data' => $plandata,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output); die;		
	}
	
	//Pending 
	function getPercentageCalculation(){
	    
	}
	
     public function startAuction(){	// 10jan2021
	    $data =  json_decode($this->data);
	    $plan_id = isset($data->plan_id) ? $data->plan_id : '';  // planid, groupid, date, start time ,end time
	    $group_id = isset($data->group_id) ? $data->group_id : ''; 
	    $start_date = isset($data->start_date) ? $data->start_date : ''; 
		$start_time = isset($data->startTime) ? $data->startTime : ''; 
		$end_time = isset($data->end_time)  ? $data->end_time : ''; 
		
		$string = preg_replace('/\s+/', '', $start_time);		
		$time=date_create($string);
		$start_time = date_format($time,"H:i:s");
		
		$string = preg_replace('/\s+/', '', $end_time);		
		$time=date_create($string);
		$end_time = date_format($time,"H:i:s");

        if(!empty($plan_id)){
        //  $getPercentage = $this->getPercentageCalculation($min_prize_amount,$plan_amount);
			$getplan = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
			$foreman_fees = $getplan['foreman_fees'];
			$min_prize_amount = $getplan['min_prize_amount'];
			$plan_amount = $getplan['plan_amount'];
			$months_completed = $getplan['months_completed'];

           $data = array(
             'plan_id' => $plan_id,
			 'group_id' => $group_id,
             'start_date' => $start_date,
             'end_date' => $start_date,
			 'start_time' => $start_time,
			 'end_time' => $end_time,
             'status' => "1",
             'auction_no' => $months_completed+1,
			 'foreman_fees' => $foreman_fees,
			 'min_prize_amount' => $min_prize_amount,
			 'plan_amount' => $plan_amount,
             'added_date' => date('Y-m-d h:i:s')
           );
          $this->db->insert('tbl_auction',$data);
          $insert_id = $this->db->insert_id();

		    $getstatus = $this->db->select('status')->where('plan_id',$plan_id)->get('tbl_auction')->row_array();
			$status = $getstatus['status'];
			$data2 = array(
				'status' => $status,
			);
			$this->db->where('plan_id',$plan_id)->update('tbl_groups',$data2);

          if(!empty($insert_id)){
			$output = array(
				'status' => Success,
				'message' => 'Auction Started Successfully',
			    'data' => array('id'=>$insert_id),
            );	
    		}else{
    			$output = array(
    				'status' => Failure,
    				'message' => "Invalid Data.",
    				'data' => []
                );
    		}
        }else{

			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output); die;		
	}
	
	public function agentUpdate(){
		$data =  json_decode($this->data);
		$agent_id = isset($data->agent_id) ? $data->agent_id : '' ;
		$data= $this->db->Where('agent_id',$agent_id)->get('tbl_agent')->row_array();
		if(!empty($data)){
			$output = array(
				'status' => Success,
				'message' => 'Agent Fetched Successfully',
			    'data' => $data,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}
	
	public function agentUpdateDetails(){
		$data =  json_decode($this->data);
		$firstname = isset($data->fname) ? $data->fname : '';
		$lastname = isset($data->lname) ? $data->lname : '';
		$email = isset($data->email) ? $data->email : '';
		$phone = isset($data->phone) ? $data->phone : '';
		$gst_no = isset($data->gst_no) ? $data->gst_no : '';
		$company = isset($data->company) ? $data->company : '';
		$payment_terms = isset($data->payment_terms) ? $data->payment_terms : '';
		$address = isset($data->address) ? $data->address : '';
		$city = isset($data->city) ? $data->city : '';
		$state = isset($data->state) ? $data->state : '';
		$country = isset($data->country) ? $data->country : '';
		$agent_id = isset($data->agent_id) ? $data->agent_id : '' ;
		$data = array(
                'first_name' => isset($firstname) ? $firstname : '',
                'last_name' => isset($lastname) ? $lastname : '',
                'email' => isset($email) ? $email : '',
                'phone' => isset($phone) ? $phone : '',
                'gst_no' => isset($gst_no) ? $gst_no : '',
                'company' => isset($company) ? $company : '',
                'payment_terms' => isset($payment_terms) ? $payment_terms : '',
                'address' => isset($address) ? $address : '',
                'city' => isset($city) ? $city : '',
                'state' => isset($state) ? $state : '',
                'country' => isset($country) ? $country : '',
                'update_date' => date('Y-m-d h:i:s')
            );
        if(!empty($agent_id)){
		    $this->db->Where('agent_id',$agent_id)->update('tbl_agent',$data);
			$output = array(
				'status' => Success,
				'message' => 'Agent Update Successfully',
			    'data' => [],
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}
	
	public function agentDelete(){
		$data =  json_decode($this->data);	
		$agent_id = isset($data->agent_id) ? $data->agent_id : '' ; 
		if(!empty($agent_id)){
	    	$this->db->where('agent_id',$agent_id)->delete('tbl_agent');
			$output = array(
				'status' => Success,
				'message' => 'Delete agent successfully',
				'data' => []
			);
		}else{
		    $output = array(
				'status' => Failure,
				'message' => "Invalid Agent Id.",
				'data' => []
			);   
		}
		echo json_encode($output);die;
	}
	
	public function subscriberupdate(){
		$data =  json_decode($this->data);
		$member_id = isset($data->member_id) ? $data->member_id : '' ;
		$data= $this->db->Where('member_id',$member_id)->get('tbl_members')->row_array();
		if(!empty($data)){
			$output = array(
				'status' => Success,
				'message' => 'Member  Fetched Successfully',
			    'data' => $data,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}
	
    public function subscriberUpdateDetails(){
	    $data =  json_decode($this->data);
	    $member_id = isset($data->member_id) ? $data->member_id : ''; 
		$data =  json_decode($this->data);
		$member_name = isset($data->name) ? $data->name : ''; 
		$last_name = isset($data->last_name) ? $data->last_name : ''; 
		$father_name = isset($data->father_name) ? $data->father_name : ''; 
		$dob = isset($data->dob) ? $data->dob : ''; 
		$mobile = isset($data->mobile) ? $data->mobile : '';
	    $secondary_mobile = isset($data->secondary_mobile) ? $data->secondary_mobile : '';
	    $office_phone = isset($data->office_phone) ? $data->office_phone : '' ;
	    $email = isset($data->email) ? $data->email : '';
	    $permanent_address = isset($data->address) ?  $data->address  : '';
	    $current_potal_address = isset($data->potal_address)  ? $data->potal_address : '' ;
	    $reference = isset($data->reference)  ? $data->reference : '' ;
	    $gender = isset($data->gender)  ? $data->gender : '' ;
	    $marital_status = isset($data->marital_status) ? $data->marital_status : '';
	    $subscriber_type = isset($data->subscriber_type) ? $data->subscriber_type : '';
	    $spouse_name = isset($data->spouse_name) ? $data->spouse_name : '' ;
	    $annivarsary_date = isset($data->annivarsary_date) ? $data->annivarsary_date : '';
	    $no_of_kids = isset($data->kids) ?  $data->kids : '';
	    $no_of_depends = isset($data->dependents) ? $data->dependents : '' ;
	    $village_city_name = isset($data->village_city_name) ? $data->village_city_name : '' ;
	    $district = isset($data->district) ? $data->district : '' ;
	    $state = isset($data->state) ? $data->state : '' ;
	    $address_pincode = isset($data->address_pincode) ? $data->address_pincode : '' ;
	    $nature_of_business = isset($data->nature_of_business) ? $data->nature_of_business : '' ;
	    $business_start_date = isset($data->business_start_date) ? $data->business_start_date : '' ;
	    $no_of_nominee = isset($data->no_of_nominee) ? $data->no_of_nominee : '' ;
	    $nominee_name = isset($data->nominee_name) ? $data->nominee_name : '' ;
	    $nominee_relationship = isset($data->nominee_relation) ? $data->nominee_relation : '' ;
	    $nominee_d_o_b = isset($data->nominee_dob) ? $data->nominee_dob : '' ;
	    $percentage_of_nomination = isset($data->nominee_precentage) ? $data->nominee_precentage : '';
	    $nominee_gaurdian_name = isset($data->nominee_gaurdian_name) ? $data->nominee_gaurdian_name : '' ;
	    $pan_number = isset($data->Pan) ? $data->Pan : '' ;
	    $income_type = isset($data->income_type) ?  $data->income_type : '';
	    $company_name = isset($data->company_name) ?  $data->company_name : '' ;
	    $company_type = isset($data->company_type) ? $data->company_type : '' ;
	    $designation = isset($data->designation) ?  $data->designation : '';
	    $work_address = isset($data->work_address) ? $data->work_address : '';
	    $salary = isset($data->salary) ? $data->salary : '' ;
	    $other_income = isset($data->other_income)  ? $data->other_income : '';
	    $experience = isset($data->experience) ? $data->experience : '';
	    $professional_service =isset($data->professional_service) ? $data->professional_service : '';
	    $office_address = isset($data->office_address) ? $data->office_address : '';
	    $employee_no =  isset($data->employee_no) ? $data->employee_no : '';
	    $gst_no = isset($data->gst) ?  $data->gst : '';
	    $annual_turnover =  isset($data->annual_turnover) ? $data->annual_turnover : '' ;
	    $income_source = isset($data->income_source) ? $data->income_source : '' ;
	    $monthly_income = isset($data->monthly_income) ? $data->monthly_income : '' ;
	    $car_category =  isset($data->car_category) ?  $data->car_category : '';
	    $two_wheeler_category = isset($data->two_wheeler_category) ? $data->two_wheeler_category : '';
	    $house_category = isset($data->house_category) ? $data->house_category : '' ;
	    $identity_category =  isset($data->identity_category) ?  $data->identity_category : '';
	    $address_category = isset($data->address_category) ?  $data->address_category : '';
	    $agent_id =  isset($data->agent_id) ? $data->agent_id : '';
	    $agent_comission  = isset($data->agent_comission) ? $data->agent_comission : '';
	    $adhaar_number = isset($data->Aadhar) ? $data->Aadhar : ''  ;
	    $docs = isset($data->docs) ? $data->docs : ''  ;
	    $incorporation_certificate = isset($data->incorporation_certificate) ? $data->incorporation_certificate : ''  ;
	    
	    if(!empty($agent_id)){
	        $is_added_by_agent = 1;
	    }else{
	        $is_added_by_agent = 0;
	    }
	    
	    $submit_data = array(
	     'name' =>   isset($member_name) ? $member_name : '',
	     'last_name' =>   isset($last_name) ? $last_name : '',
	     'father_name' => isset($father_name) ? $father_name : '',
	     'dob' => isset($dob) ? $dob : '',
	     'mobile' => isset($mobile) ? $mobile : '',
	     'secondary_mobile' => isset($secondary_mobile) ? $secondary_mobile : '',
	     'subscriber_type' => isset($subscriber_type) ? $subscriber_type : '',
	     'office_phone' => isset($office_phone) ? $office_phone : '',
	     'email' => isset($email) ? $email : '',
	     'permanent_address' => isset($permanent_address) ? $permanent_address : '',
	     'current_potal_address' => isset($current_potal_address) ? $current_potal_address : '',
	     'reference' => isset($reference) ? $reference : '',
	     'gender' => isset($gender) ? $gender : '',
	     'marital_status' => isset($marital_status) ? $marital_status : '',
	     'spouse_name' => isset($spouse_name) ? $spouse_name : '',
	     'village_city_name' => isset($village_city_name) ? $village_city_name : '',
	     'district' => isset($district) ? $district : '',
	     'state' => isset($state) ? $state : '',
	     'address_pincode' => isset($address_pincode) ? $address_pincode : '',
	     'nature_of_business' => isset($nature_of_business) ? $nature_of_business : '',
	     'business_start_date' => isset($business_start_date) ? $business_start_date : '',
	     'annivarsary_date' => isset($annivarsary_date) ? $annivarsary_date : '',
	     'no_of_kids' =>    isset($no_of_kids) ? $no_of_kids : '',
	     'no_of_depends' => isset($no_of_depends) ? $no_of_depends : '',
	     'no_of_nominee' => isset($no_of_nominee) ? $no_of_nominee : '',
	     'nominee_name' => isset($nominee_name) ? implode(',',$nominee_name) : '',
	     'nominee_relationship' => isset($nominee_relationship) ? implode(',',$nominee_relationship) : '',
	     'nominee_d_o_b' => isset($nominee_d_o_b) ? implode(',',$nominee_d_o_b) : '',
	     'percentage_of_nomination' => isset($percentage_of_nomination) ? implode(',',$percentage_of_nomination) : '',
	     'nominee_gaurdian_name' => isset($nominee_gaurdian_name) ? implode(',',$nominee_gaurdian_name) : '',
	     'pan_number' => isset($pan_number) ? $pan_number : '',
	     'income_type' => isset($income_type) ? $income_type : '',
	     'company_name' => isset($company_name) ? implode(',',$company_name) : '',
	     'company_type' => isset($company_type) ? $company_type : '',
	     'designation' => isset($designation) ? $designation : '',
	     'work_address' => isset($work_address) ? $work_address : '',
	     'salary' =>  isset($salary) ? $salary : '',
	     'other_income' => isset($other_income) ? $other_income : '',
	     'experience'  => isset($experience) ? $experience : '',
	     'office_address' => isset($office_address) ? $office_address : '',
	     'employee_no' => isset($employee_no) ? $employee_no : '',
	     'gst_no' => isset($gst_no) ? $gst_no : '',
	     'annual_turnover'  => isset($annual_turnover) ? $annual_turnover : '',
	     'income_source'  => isset($income_source) ? $income_source : '',
	     'monthly_income' => isset($monthly_income) ? $monthly_income : '',
	     'car_category' => isset($car_category) ? $car_category : '',
	     'two_wheeler_category' => isset($two_wheeler_category) ? $two_wheeler_category : '',
	     'house_category'  => isset($house_category) ? $house_category : '',
	     'identity_category' => isset($identity_category) ? $identity_category : '',
	     'address_category'  => isset($address_category) ? $address_category : '',
	     'is_added_by_agent' => isset($is_added_by_agent) ? $is_added_by_agent : '',
	     'agent_id' => isset($agent_id) ? $agent_id : '',
	     'agent_comission' => isset($agent_comission) ? $agent_comission : '',
	     'adhaar_number' => isset($adhaar_number) ? $adhaar_number : '',
	     'incorporation_certificate' => isset($incorporation_certificate) ? $incorporation_certificate : '',
	     'docs' => isset($docs) ? $docs : '',
	     'added_date' => date('Y-m-d h:i:s')
	    );

	  if(!empty($member_id)){
	        $this->db->Where('member_id',$member_id)->update('tbl_members',$submit_data);
		    $output = array(
				'status' => Success,
				'message' => 'Members Update Successfully',
			    'data' => [],
            );	
		}else{
		    $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            ); 
		}
	   echo json_encode($output);die;
	}
	
	public function checkIfPlanAlreadyPurchased($member_id = '',$plan_id = ''){
	   $check_member = $this->db->where('plan_id',$plan_id)->where('member_id',$member_id)->get('tbl_orders')->num_rows();
	   if($check_member <= 0){
	       return true;
	   }else{
	       return false;
	   }
	}
	
    public function BuyPlanByAgent(){
	  $data =  json_decode($this->data);
	  $member_id = isset($data->member_id) ? $data->member_id : '' ;
	  $plan_id = isset($data->plan_id) ? $data->plan_id : '';
	  $member_name = isset($data->member_name) ? $data->member_name : '';
	  $agent_id = isset($data->agent_id) ? $data->agent_id : '' ;
	  $transaction_id = isset($data->transaction_id) ? $data->transaction_id : '';
	  $payment_mode = isset($data->payment_mode) ? $data->payment_mode : '';
	  $getplan = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
	  $admission_fees =  isset($getplan['admission_fee']) ? $getplan['admission_fee'] : '';
	  $start_month =  isset($getplan['start_month']) ? $getplan['start_month'] : '';
	  $emi =  isset($getplan['emi']) ? $getplan['emi'] : '';
	  $total_months =  isset($getplan['total_months']) ? $getplan['total_months'] : '';
	  $group_count = isset($getplan['groups_counts']) ? $getplan['groups_counts'] : '';
	  $plan_amount = isset($getplan['plan_amount']) ? $getplan['plan_amount'] : '';
	  $tenure = isset($getplan['tenure']) ? $getplan['tenure'] : '';
	  $agent_commission = isset($getplan['agent_commission']) ? $getplan['agent_commission'] : '';
	  $months_completed = isset($getplan['months_completed']) ? $getplan['months_completed'] : '';
	  $end_date_for_subscription = isset($getplan['end_date_for_subscription']) ? $getplan['end_date_for_subscription'] : '';
	   
	  if(!empty($agent_id)){
	     $is_added_by_agent = 1;
	  }else{
	     $is_added_by_agent = 0; 
	  }
	  
	 $group_data = $this->db->select('group_id,total_members')->where('plan_id',$plan_id)->get('tbl_groups')->result_array();
	 if(!empty($group_data)){
	 $test = false;
	 foreach($group_data as $key => $value){
	  $group_id = isset($value['group_id']) ? $value['group_id'] : '';
	  $group_data1 = $this->db->where('plan_id',$plan_id)->where('group_id',$group_id)->get('tbl_orders')->result_array();
	  if(!empty($group_data1)){
	  foreach($group_data1 as $key1 => $value1){
	      $slot_number = isset($value1['slot_number']) ? $value1['slot_number'] : '';
	      $slot_status = isset($value1['slot_status']) ? $value1['slot_status'] : '';
	      $order_id = isset($value1['order_id']) ? $value1['order_id'] : '';
	      $plan_id = isset($value1['plan_id']) ? $value1['plan_id'] : '';

	      
	       $plandata = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
	       
	       $plan_name = isset($plandata['plan_name']) ? $plandata['plan_name'] : '';
	      
    	    if($slot_status == 'vacant'){
	          $data = array(
	            'member_id' => isset($member_id) ? $member_id : '',
				'plan_id'   => isset($plan_id) ? $plan_id : '',
				'group_id'  => isset($group_id) ? $group_id : '',
				'member_name' => isset($member_id) ? $member_id : '',
				'plan_amount' => isset($plan_amount) ? $plan_amount : '',
				'start_month' => isset($start_month) ? $start_month : '',
				'emi' => isset($emi) ? $emi : '',
				'tenure' => isset($tenure) ? $tenure : '',
				'months_completed' => isset($months_completed) ? $months_completed : '',
				'agent_commission' => isset($agent_commission) ? $agent_commission : '',
				'end_month' => isset($end_date_for_subscription) ? $end_date_for_subscription : '',
				'total_months' => isset($total_months) ? $total_months : '',
				'groups_count'  => isset($group_count) ? $group_count : '',
				'admission_fees'    => isset($admission_fees) ? $admission_fees : '',
				'agent_id'  => isset($agent_id) ? $agent_id : '',
				'is_added_by_agent' => $is_added_by_agent,
				'transaction_id' => isset($transaction_id) ? $transaction_id : '',
				'payment_mode' => isset($payment_mode) ? $payment_mode : 'offline',
				'slot_status' => 'assigned',
				'added_date' => date('Y-m-d h:i:s')
	          );
	          $this->db->where('plan_id',$plan_id)->where('group_id',$group_id)->where('order_id',$order_id)->update('tbl_orders',$data);
	          
	         if(!empty($check_insert_id['insert_id'])){
                $insert_id =$check_insert_id['insert_id'] + 1;
            }else{
                $insert_id = 1;
            }
            
			$member_data = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
            $ledgerdata1 = array(
                    'insert_id'=> $insert_id,
                    'c_code' => '400',
                    'category_desc' => 'Subscription',
                    'plan_name' => isset($plan_name) ? $plan_name :'',
                    'transaction_mode' => 'J1 - Internal',
                    'transaction_type' => 'Subscription Due',
                    'transaction_description' => 'Subscribers A/c',
                    'amount' => isset($emi) ? $emi : '',
                    'dr_cr' =>'Dr',
                    'sub_id' => isset($member_data['subscriber_id']) ? $member_data['subscriber_id'] : '',
                    'account_name' => isset($member_data['name']) ? $member_data['name'] : '',
                    'added_date' => date('Y-m-d h:i:s'),
                    'account_description' => $this->getGlAccount('1002'),
                    'gl_account' => '1002',
                    'type' => 'Payment',
					'user' =>'Senthil',
					'slot_number' => $slot_number,
                );
                 $insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
                 $insert_id = $this->db->insert_id();
                 $selest_ensert_id = $this->db->where('general_ledger_master_id',$insert_id)->get('tbl_general_ledger_master')->row_array();
                  $ledgerdata2 = array(
                    'insert_id'=> $selest_ensert_id['insert_id'],
                    'c_code' => '400',
                    'category_desc' => 'Subscription',
                    'plan_name' => isset($plan_name) ? $plan_name :'',
                    'transaction_mode' => 'J1 - Internal',
                    'transaction_type' => 'Subscription Due',
                    'transaction_description' => 'Plan A/c',
                    'amount' => isset($emi) ? $emi : '',
                    'dr_cr' =>'Cr',
                    'sub_id' => isset($member_data['subscriber_id']) ? $member_data['subscriber_id'] : '',
                    'account_name' => isset($member_data['name']) ? $member_data['name'] : '',
                    'added_date' => date('Y-m-d h:i:s'),
                    'account_description' => $this->getGlAccount('1003'),
                    'gl_account' => '1003',
                    'type' => 'Payment',
					'user' =>'Senthil',
					'slot_number' => $slot_number,
                );
                $this->db->insert('tbl_general_ledger_master',$ledgerdata2);
	          
	          $saveLeger =  array(
	            'reference' => 'BuyPlan',
	            'is_system' => 'Yes',
	            'added_date' => date('Y-m-d h:i:s')
	          );
	          
	          $saveLeger1 =  array(
	            'reference' => 'Subscriber',
	            'debit' => $plan_amount
	          );
	          
	          $saveLeger2 =  array(
	            'reference' => 'Plan',
	            'credit' => $plan_amount
	          );
	          
	           $saveLeger3 =  array(
	            'reference' => 'Subscriber Purchased the Plan  '.$plan_name
	          );
	          
	          $this->db->insert('tbl_ledger_transactions',$saveLeger);
	          $this->db->insert('tbl_ledger_transactions',$saveLeger1);
	          $this->db->insert('tbl_ledger_transactions',$saveLeger2);
	          $this->db->insert('tbl_ledger_transactions',$saveLeger3);
	         
	          //Emi
			  $getorder =  $this->db->where('order_id',$order_id)->get('tbl_orders')->row_array();
			  $months = explode(" ",$getorder['tenure']);
			  $getmonths = isset($months[0]) ? $months[0] : ''; 
			  
			  $date2 = date('m', strtotime($start_month));
			  $date2 = 0;
			  $emi = round($emi, 0);
			  for($i=1; $i<=$getmonths; $i++){
				 // one column add in emi table plan name (pending)
				$date3 = date('M,Y', strtotime($start_month. ' + '.$date2.'month'));
				$date2  = $date2 + 1; 
				$data1 = array(
				'member_id' => isset($member_id) ? $member_id : '',
				'plan_id'   => isset($plan_id) ? $plan_id : '',
				'group_id'  => isset($group_id) ? $group_id : '',
				'emi_month' => $date3,
				'plan_emi' => $emi,
				'emi_no' => $i,
				'total_emi' => $tenure,
				'emi_status'  => "due",
				'is_partial_payment' => "No",
				'is_chit_taken' => "no",
				'chit_status' => 'close',
				'slot_number' => $slot_number,
				'added_date' => date('Y-m-d h:i:s')
			   );
			   $this->db->insert('tbl_emi',$data1);
			   $insert_id1 = $this->db->insert_id();
			   if(!empty($insert_id1)){
			       
			       
					$output = array(
						'status' => Success,
						'message' => 'Save Order Successfully',
						'data' => [],
					);	 
			   }else{
				  $output = array(
						'status' => Failure,
						'message' => "Invalid Data.",
						'data' => []
					); 
				}
			 }
	         $test = true;
	          break;
	      }
	      else{
	        $output = array(
				'status' => Success,
				'message' => "All Order Slot Booked",
				'data' => []
			  ); 
	        continue;
	      }
 	    }
 	    if($test == true){
 	        break;
 	    }else{
 	        continue;
 	    }
	   }else{
	     $output = array(
			'status' => Failure,
			'message' => "Data not found order table",
			'data' => []
		  );   
	   }
	  }
	 }else{
	     $output = array(
    		'status' => Failure,
    		'message' => "Data not found Group table",
    		'data' => []
    	  ); 
	 }
	  echo json_encode($output); die;
	}
	
	public function closeAuctionAutomatically(){ //17jan2022
			$date = date('m/d/Y');
			$time = date('H:i:s');	
			$auction_detail = $this->db->where('status','1')->where('end_date',$date)->get('tbl_auction')->result_array();
			foreach($auction_detail as $keys=>$values){
				$endtime=$values['end_time'];
				$auction_id = $values['auction_id'];
				if($time>$endtime){					
						$auction_status = array(
							'status' => '0'
						);
					$update = $this->db->where('auction_id',$auction_id)->update('tbl_auction',$auction_status);
					$auction_detail = $this->db->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
					$plan_id =  $auction_detail['plan_id'];
					$group_id = $auction_detail['group_id'];
					$return_chit_amount = $auction_detail['plan_amount'];
					$bid_id = $auction_detail['winning_bid_id'];
					
					$total_amount_paid = '0';
					$is_on_EMI = 'yes';				
					$bid_detail = $this->db->select('bid_amount')->where('auction_id',$auction_id)->get('tbl_bids')->result_array();
					$array_new = array();
					foreach($bid_detail as $keys=>$values){
						$array_new[] = $values['bid_amount'];
					}
					$min_bid = min($array_new);
					$bid_id = $this->db->select('bid_id')->where('auction_id',$auction_id)->where('bid_amount',$min_bid)->get('tbl_bids')->row_array();
					$min_bid_id = $bid_id['bid_id'];
					$slot_number = isset($bid_id['slot_number']) ? $bid_id['slot_number'] : '';
					$winning_bid_id = array(
						'winning_bid_id'=> isset($min_bid_id) ? $min_bid_id  : '',
					);
					$winning_id_update = $this->db->where('auction_id',$auction_id)->update('tbl_auction',$winning_bid_id);
					$bid_data = $this->db->where('bid_id',$min_bid_id)->get('tbl_bids')->row_array();
					$chit_amount = $bid_data['bid_amount'];
					$member_id = $bid_data['member_id'];
					$plan_id = $bid_data['plan_id'];
					$plan_details = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
					$total_amount_due = $plan_details['plan_amount'];
					$plan_tenure = $plan_details['tenure'];
					$plan_months_completed = $plan_details['months_completed'];
					$remaining_month = $plan_details['remaining_month'];
					$emiamountupdate = array(
						'months_completed' => $plan_months_completed+1,
						'remaining_month' => $remaining_month-1
					);
					$this->db->where('plan_id',$plan_id)->update('tbl_plans',$emiamountupdate);
					$forgo_amount = $total_amount_due - $chit_amount;
					$emi_amount = ($total_amount_due / ($plan_tenure-$plan_months_completed));
					$total_emi = $plan_tenure - $plan_months_completed;//
					$due_emi = $plan_tenure - $plan_months_completed;
					$emi_paid = '0';
					$is_active = '1';
					
					$data = array(
						'plan_id' => isset($plan_id) ? $plan_id : '',//
						'group_id' =>  isset($group_id) ? $group_id : '',//
						'auction_id' =>  isset($auction_id) ? $auction_id : '',//
						'member_id' =>  isset($member_id) ? $member_id : '',//
						'return_chit_amount' =>  isset($return_chit_amount) ? $return_chit_amount : '',//
						'total_amount_paid' => isset($total_amount_paid) ? $total_amount_paid : '',//
						'total_amount_due' => isset($total_amount_due) ? $total_amount_due : '',//
						'chit_amount' => isset($chit_amount) ? $chit_amount : '',//
						'forgo_amount' => isset($forgo_amount) ? $forgo_amount : '',//
						'is_on_EMI' => isset($is_on_EMI) ? $is_on_EMI : '',//
						'emi_amount' => isset($emi_amount) ? $emi_amount : '',//
						'total_emi' => isset($total_emi) ? $total_emi : '',//
						'due_emi' => isset($due_emi) ? $due_emi : '',//
						'emi_paid' => isset($emi_paid) ? $emi_paid : '',//
						'is_active' => isset($is_active) ? $is_active : '',	//
						'slot_numeber' => isset($slot_number) ? $slot_number : '',
						'chit_month' => date("M,Y"),
						'added_date' => date('Y-m-d h:i:s')
					); 			
					 $emi_amount = round($emi_amount, 0);
					 $chit_emi_months = $plan_months_completed+1;
					$insertdata = $this->db->insert('tbl_chits',$data);
					for($i=1; $i<=$total_emi; $i++){
						$data2 = array(
							'plan_id' => isset($plan_id) ? $plan_id : '',//
							'member_id' => isset($member_id) ? $member_id : '',//
							'group_id' => isset($group_id) ? $group_id : '',//
							'emi_no' => $i,//
							'chit_emi' => isset($emi_amount) ? $emi_amount : '',//
							'total_emi' => isset($total_emi) ? $total_emi : '',//
							'emi_status' => 'due',//
							'chit_amount' => isset($chit_amount) ? $chit_amount : '',
							'return_chit_amount' => isset($return_chit_amount) ? $return_chit_amount : '',//
							'return_factor' => '0',//
							'chit_emi_months' => isset($chit_emi_months) ? $chit_emi_months : '',//
							'is_partial_payment' => 'No'
						);
				// 			$insert_chit_emi = $this->db->insert('chit_emi',$data2);
							$chit_emi_months++;
					}
					$win_bid_acc = array(
						'is_bid_accepted'=>'yes'
					);
					$this->db->where('bid_id',$min_bid_id)->update('tbl_bids',$win_bid_acc);
					$member_name_detail = $this->db->select('name')->where('member_id',$member_id)->get('tbl_members')->row_array();
							$member_name = $member_name_detail['name'];
							$foreman_fees_detail = $this->db->select('foreman_fees,total_months')->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
							$foreman_fees = $foreman_fees_detail['foreman_fees'];
							$total_months = $foreman_fees_detail['total_months'];
							$group_details_member = $this->db->select('total_members')->where('group_id',$group_id)->get('tbl_groups')->row_array(); 
							$group_member = $group_details_member['total_members'];
							$forman_amount = $total_amount_due*$foreman_fees/100;
							$divident_amount = ($total_amount_due -($chit_amount + $forman_amount)) / $group_member;	
							$divident_data = array(
								'member_name'=>isset($member_name) ? $member_name : '',
								'member_id'=>isset($member_id) ? $member_id : '',
								'plan_id'=>isset($plan_id) ? $plan_id : '',
								'group_id'=>isset($group_id) ? $group_id : '',
								'auction_id'=>$auction_id,
								'divident_amount'=>$divident_amount,
								'month'=>isset($auction_no) ? $auction_no : '',
								'total_months'=>isset($total_months) ? $total_months : '',
								'added_date' => date('Y-m-d h:i:s')
							);
							$dividint_months_sub = $plan_months_completed+1;
								$this->db->insert('tbl_divident',$divident_data);
								$emidivident = array(
									'divident' => $divident_amount
								);
								$this->db->where('emi_no',$dividint_months_sub)->where('group_id',$group_id)->where('plan_id',$plan_id)->update('tbl_emi',$emidivident);
								
															$check_insert_id = $this->db->select('insert_id')->ORDER_BY('general_ledger_master_id','DESC')->get('tbl_general_ledger_master')->row_array();
							if(!empty($check_insert_id['insert_id'])){
                                $insert_id =$check_insert_id['insert_id'] + 1;
                            }else{
                                $insert_id = 1;
                            }
                            
                            $plandetails = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
                            $ledgerdata1 = array(
                                    'insert_id'=> $insert_id,
                                    'c_code' => '503',
                                    'category_desc' => 'Bid_Amount',
                                    'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
                                    'transaction_mode' => 'J1 - Internal',
                                    'transaction_type' => 'Bid_Amount',
                                    'transaction_description' => 'Final Bid Amt',
                                    'amount' => isset($bid_data['bid_amount']) ? $bid_data['bid_amount'] : '',
                                    'dr_cr' =>'Dr',
                                    'sub_id' => isset($member_id) ? $member_id : '',
                                    'account_name' => isset($member_id) ? $member_id : '',
                                    'added_date' => date('Y-m-d h:i:s'),
                                    'account_description' => $this->getGlAccount('1003'),
                                    'gl_account' => '1003',
                                    'type' => 'Payment',
                                    'user' => 'Senthil',
                                );
                                 $insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
                                 $insert_id = $this->db->insert_id();
                                 $selest_ensert_id = $this->db->where('general_ledger_master_id',$insert_id)->get('tbl_general_ledger_master')->row_array();
                                  $ledgerdata2 = array(
                                    'insert_id'=> $selest_ensert_id['insert_id'],
                                    'c_code' => '503',
                                    'category_desc' => 'Bid_Amount',
                                    'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
                                    'transaction_mode' => 'J1 - Internal',
                                    'transaction_type' => 'Bid_Amount',
                                    'transaction_description' => 'Final Bid Amt',
                                    'amount' => isset($bid_data['bid_amount']) ? $bid_data['bid_amount'] : '',
                                    'dr_cr' =>'Cr',
                                    'sub_id' => isset($member_id) ? $member_id : '',
                                    'account_name' => isset($member_id) ? $member_id : '',
                                    'added_date' => date('Y-m-d h:i:s'),
                                    'account_description' => $this->getGlAccount('1004'),
                                    'gl_account' => '1004',
                                    'type' => 'Payment',
                                    'user' => 'Senthil',
                                );
                                $this->db->insert('tbl_general_ledger_master',$ledgerdata2);
                                
                                $ledger_to_Divident = $this->db->where('code','501')->get('tbl_transaction_type_master')->row_array();
                                if(!empty($ledger_to_Divident['transaction_type_master_id'])){
                                    $get_selection_data = $this->db->where('transaction_type_id',$ledger_to_Divident['transaction_type_master_id'])->get('tbl_transcation_type_category_selection_master')->row_array();
                                    if(!empty($get_selection_data['general_ledger_id'])){
                                        $get_general_to = $this->db->where('id',$get_selection_data['general_ledger_id'])->get('tbl_ledger_account')->row_array();
                                    }if(!empty($get_selection_data['general_ledger_id_from'])){
                                        $get_general_from = $this->db->where('id',$get_selection_data['general_ledger_id_from'])->get('tbl_ledger_account')->row_array();
                                    }if(!empty($get_selection_data['category_id'])){
                                        $getcategree = $this->db->where('category_id',$get_selection_data['category_id'])->get('tbl_category')->row_array();
                                    }
                                }
                            $ledgerdata3 = array(
                                    'insert_id'=> $insert_id,
                                    'c_code' => isset($getcategree['code']) ? $getcategree['code'] : '501',
                                    'category_desc' => isset($getcategree['name']) ? $getcategree['name'] : 'Dividend Disbursal',
                                    'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
                                    'transaction_mode' => 'J1 - Internal',
                                    'transaction_type' => isset($ledger_to_Divident['name']) ? $ledger_to_Divident['name'] : 'Dividend',
                                    'transaction_description' => 'Dividend Allocation by plan to Individual subscribers',
                                    'amount' => isset($divident_amount) ? $divident_amount : '',
                                    'dr_cr' =>'Dr',
                                    'sub_id' => isset($member_id) ? $member_id : '',
                                    'account_name' => isset($member_id) ? $member_id : '',
                                    'added_date' => date('Y-m-d h:i:s'),
                                    'account_description' => isset($get_general_from['name']) ? $get_general_from['name'] : 'Dividend on Subscriptions',
                                    'gl_account' => '1002',
                                    'type' => 'Payment',
                                    'user' => 'Senthil',
                                );
                                 $insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata3);
                                 $insert_id = $this->db->insert_id();
                                 $selest_ensert_id = $this->db->where('general_ledger_master_id',$insert_id)->get('tbl_general_ledger_master')->row_array();
                                  $ledgerdata4 = array(
                                    'insert_id'=> $selest_ensert_id['insert_id'],
                                    'c_code' => isset($getcategree['code']) ? $getcategree['code'] : '501',
                                    'category_desc' => isset($getcategree['name']) ? $getcategree['name'] : 'Dividend Disbursal',
                                    'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
                                    'transaction_mode' => 'J1 - Internal',
                                    'transaction_type' => isset($ledger_to_Divident['name']) ? $ledger_to_Divident['name'] : 'Dividend',
                                    'transaction_description' => 'Dividend Allocation by plan to Individual subscribers',
                                    'amount' => isset($divident_amount) ? $divident_amount : '',
                                    'dr_cr' =>'Cr',
                                    'sub_id' => isset($member_id) ? $member_id : '',
                                    'account_name' => isset($member_id) ? $member_id : '',
                                    'added_date' => date('Y-m-d h:i:s'),
                                    'account_description' => isset($get_general_to['name']) ? $get_general_to['name'] : 'Subscribers A/c',
                                    'gl_account' => '1002',
                                    'type' => 'Payment',
                                    'user' => 'Senthil',
                                );
                                $this->db->insert('tbl_general_ledger_master',$ledgerdata4);
								
					$output = array(
						'success'=>'success',
						'message'=>'auction close',	
						'winning bid id'=>$min_bid_id,			
						'auction_id'=>$auction_id
					);
					echo json_encode($output);die;
				}			
				
			}			
		}
		
		
	

   	public function plangroupsforauction(){ //12jan2022 ..update
		$data =  json_decode($this->data);
	    $plan_id = $data->plan_id; 
		$getgroup = $this->db->where('plan_id',$plan_id)->get('tbl_groups')->result_array();
	    $all_detail = array();
	    $new_arr = array();
	    $getgrouprowData = [];
	    $auctionData = [];
		
		foreach($getgroup as $keys => $values){
			$group_id = $values['group_id'];			
			$auction_detail = $this->db->where('plan_id',$plan_id)->where('group_id',$group_id)->order_by('auction_id','desc')->get('tbl_auction')->row_array();
			$getgrouprow = $this->db->where('plan_id',$plan_id)->where('group_id',$group_id)->get('tbl_groups')->row_array();	
			$plan_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
			$auctionData[$keys] = $auction_detail;
			$getgrouprowData[$keys] = $getgrouprow;
			$plan_amount = $plan_detail['plan_amount'];
			$forman_fees = $plan_detail['foreman_fees'];
			$forman_amount = $plan_amount*$forman_fees/100;
			$all_detail[$keys]['auction_no']  =   isset($auction_detail['auction_no']) ?  $auction_detail['auction_no'] : '' ;
			$all_detail[$keys]['status']      =  isset($auction_detail['status']) ?  $auction_detail['status'] : '' ;
			$all_detail[$keys]['auction_id']  =  isset($auction_detail['auction_id']) ? $auction_detail['auction_id'] : '' ;
			$all_detail[$keys]['start_date']  =  isset($auction_detail['start_date']) ? $auction_detail['start_date'] : '' ;
			$all_detail[$keys]['end_date']  =  isset($auction_detail['end_date']) ? $auction_detail['end_date'] : '' ;
			$all_detail[$keys]['start_time']  =  isset($auction_detail['start_time']) ? $auction_detail['start_time'] : '' ;
			$all_detail[$keys]['end_time']  =  isset($auction_detail['end_time']) ? $auction_detail['end_time'] : '' ;
			$all_detail[$keys]['group_id']    =  isset($getgrouprow['group_id']) ?  $getgrouprow['group_id'] : '' ;
			$all_detail[$keys]['forman_fees'] =  isset($forman_amount)  ? $forman_amount : '';
			$all_detail[$keys]['plan_id']     =  isset($getgrouprow['plan_id']) ? $getgrouprow['plan_id'] : '';
			$all_detail[$keys]['total_members']= isset($getgrouprow['total_members']) ? $getgrouprow['total_members'] : '';
			$all_detail[$keys]['group_name'] =   isset($getgrouprow['group_name']) ? $getgrouprow['group_name'] : '' ;
			$all_detail[$keys]['plan_id']    =   isset($getgrouprow['plan_id']) ? $getgrouprow['plan_id'] : '';
			$all_detail[$keys]['group_name'] =   isset($getgrouprow['group_name'])  ? $getgrouprow['group_name'] : '' ;
			$all_detail[$keys]['added_date'] =   isset($getgrouprow['added_date']) ? $getgrouprow['added_date'] : '' ;
			$all_detail[$keys]['update_date']=   isset($getgrouprow['update_date'])  ? $getgrouprow['update_date'] : '';			
		}		       
		if(!empty($getgroup)){
			$output = array(
				'status' => Success,
				'message' => 'Group Details Fetched Successfully',
			    'data' => $all_detail,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}	
	public function getbidsforauction(){ // 10jan2022
		$data =  json_decode($this->data);
		$auction_id = isset($data->auction_id) ? $data->auction_id : ''; 
		$getauction = $this->db->where('auction_id',$auction_id)->order_by('bid_id','desc')->get('tbl_bids')->result_array();
		$new_arr =array();
		foreach($getauction as $key => $value){
		    $slot_number = $this->db->where('slot_number',$value['slot_number'])->get('tbl_orders')->row_array();
		    $new_array[$key] = $value;
		    $new_array[$key]['slot_number'] = isset($slot_number['slot_number']) ? $slot_number['slot_number'] : '';
		    $member_detail = $this->db->where('member_id',$slot_number['member_id'])->get('tbl_members')->row_array();
		    $new_array[$key]['member_name'] = isset($member_detail['name']) ? $member_detail['name'] : '';
		}
		if(!empty($new_array)){
			$output = array(
				'status' => Success,
				'message' => 'Bids Details Fetched Successfully',
			    'data' => $new_array,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "No Data Found Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}
	
	public function getchitemi(){ // 10jan2022
		$data =  json_decode($this->data);
		$member_id = $data->member_id; 
		$plan_id_detail = $this->db->where('member_id',$member_id)->get('chit_emi')->row_array();
		$plan_id = $plan_id_detail['plan_id'];
		$plan_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
		$months_completed = $plan_detail['months_completed'];		
		$new_arr = array();
		for ($i=1; $i<=$months_completed; $i++){
			$getchitemi = $this->db->where('emi_status','due')->where('chit_emi_months',$i)->where('member_id',$member_id)->get('chit_emi')->result_array();			
			$new_arr[] = $getchitemi;
		}
		$newarray = array();
		foreach($new_arr as $key=>$value){
			foreach( $value as $keys=>$values ){
			  $newarray[] = $values['chit_emi_id'];
			}			
		}
		$newchitarray = array();
		foreach( $newarray as $key=>$values){
			$getchit = $this->db->where('chit_emi_id',$values)->get('chit_emi')->row_array();
			$newchitarray[] = $getchit;
		}

		if(!empty($newchitarray)){
			$output = array(
				'status' => Success,
				'message' => 'Chit Emi Details Fetched Successfully',
			    'data' => $newchitarray,
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "No Data Found Data.",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}

     public function getsubscriberdues(){		
		$data =  json_decode($this->data);
		$member_id = $data->member_id; 
		$plan_id_emi_detail = $this->db->where('member_id',$member_id)->get('tbl_emi')->row_array();
		
		if(!empty($plan_id_emi_detail)){
		    	$plan_id = $plan_id_emi_detail['plan_id'];
        		$plan_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
        		$months_completed = $plan_detail['months_completed'];		
        		$new_arr = array();
        		for ($i=1; $i<=$months_completed; $i++){
        			$getchitemi = $this->db->where('emi_status','due')->where('emi_no',$i)->where('member_id',$member_id)->get('tbl_emi')->result_array();			
        			$new_arr[] = $getchitemi;
        		}
        		    
        		    $newarray = array();
        		foreach($new_arr as $key=>$value){
        			foreach( $value as $keys=>$values ){
        			  $newarray[] = $values['emi_id'];
        			}			
        		}
        		$rowofemidue =  count($newarray);
        		$sumofemidues = 0;
        		foreach( $newarray as $key=>$values){			
        			$getchitemidue = $this->db->select('plan_emi')->where('emi_id',$values)->where('is_partial_payment','No')->where('emi_status','due')->get('tbl_emi')->row_array();
        			$plan_emi = isset($getchitemidue['plan_emi']) ? $getchitemidue['plan_emi'] : '' ;	
        			if($plan_emi != ''){
        			  $sumofemidues+= $plan_emi;
        			}else{
        				$sumofemidues += 0;	
        			}	
        		}
        		$sumpartialemi = 0;
        		foreach( $newarray as $key=>$values){
        			$getchitempartialidue = $this->db->select('amount_due')->where('emi_id',$values)->where('is_partial_payment','Yes')->where('emi_status','due')->get('tbl_emi')->row_array();				
        			$partial_emi = isset($getchitempartialidue['amount_due']) ? $getchitempartialidue['amount_due'] : '' ;		
        			if($partial_emi != ''){
        				$sumpartialemi+= $partial_emi;
        			  }else{
        				  $sumpartialemi += 0;	
        			  }	
        		}
		$total_plan_amount_due = $sumpartialemi + $sumofemidues;
		}
		
		$plan_id_detail = $this->db->where('member_id',$member_id)->get('chit_emi')->row_array();
		if(!empty($plan_id_detail)){
		    $plan_id = $plan_id_detail['plan_id'];
		$plan_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
		$months_completed = $plan_detail['months_completed'];		
		$new_arr = array();
		for ($i=1; $i<=$months_completed; $i++){
			$getchitemi = $this->db->where('emi_status','due')->where('chit_emi_months',$i)->where('member_id',$member_id)->get('chit_emi')->result_array();			
			$new_arr[] = $getchitemi;
		}
		$newarray2 = array();
		foreach($new_arr as $key=>$value){
			foreach( $value as $keys=>$values ){
			  $newarray2[] = $values['chit_emi_id'];
			}			
		}
		$sumofchitemidues = 0;
		foreach( $newarray2 as $key=>$values){			
			$getchitemidue = $this->db->select('chit_emi')->where('chit_emi_id',$values)->where('is_partial_payment','No')->where('emi_status','due')->get('chit_emi')->row_array();
			$plan_emi = isset($getchitemidue['chit_emi']) ? $getchitemidue['chit_emi'] : '' ;	
			if($plan_emi != ''){
			  $sumofchitemidues+= $plan_emi;
			}else{
				$sumofchitemidues += 0;	
			}	
		}
		$sumchitpartialemi = 0;
		foreach( $newarray as $key=>$values){
			$getchitempartialidue = $this->db->select('amount_due')->where('chit_emi_id',$values)->where('is_partial_payment','Yes')->where('emi_status','due')->get('chit_emi')->row_array();				
			$partial_emi = isset($getchitempartialidue['amount_due']) ? $getchitempartialidue['amount_due'] : '' ;		
			if($partial_emi != ''){
				$sumchitpartialemi+= $partial_emi;
			  }else{
				  $sumchitpartialemi += 0;	
			  }	
		}
		$total_chit_amount_due =  $sumchitpartialemi + $sumofchitemidues;
		$rowschitemi =  count($newarray2);
		}
		
		

		$data = array(
					 'emi_due' =>  isset( $rowofemidue) ? $rowofemidue :''	, 
					 'emi_total_due_amount' => isset( $total_plan_amount_due) ? $total_plan_amount_due :'0'	, 
					 'chit_emi_due' =>  isset($rowschitemi) ? $rowschitemi :'0'	, 
					 'chit_emi_total_due_amount' =>  isset($total_chit_amount_due) ? $total_chit_amount_due :'0',
					 'additional_dues' => 0,
					 'registration_dues' => 0
					);
					
					if(! empty($total_plan_amount_due) || ! empty($total_chit_amount_due)){
						$output = array(
							'status' => Success,
							'message' => 'Chit Emi Details Fetched Successfully',
						    'data' => $data
			            );	
					}else{
						$output = array(
							'status' => Failure,
							'message' => "No Data Found Data.",
							'data' => []
			            );
					}
					echo json_encode($output);die;
	}



   public function assignChits(){ //11jan2022  update ..
	$data =  json_decode($this->data);
	$auction_id = $data->auction_id; 
	 
	 $auction_details = $this->db->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
	 $plan_id =  $auction_details['plan_id'];
	 $auction_no =  $auction_details['auction_no'];
	 $group_id = $auction_details['group_id'];
	 $return_chit_amount = $auction_details['plan_amount'];
	 $bid_id = $auction_details['winning_bid_id'];
	 $bid_detail = $this->db->where('bid_id',$bid_id)->get('tbl_bids')->row_array();
	 $chit_amount = $bid_detail['bid_amount'];
	 $member_id = $bid_detail['member_id'];
	 $total_amount_paid = '0';
	 $is_on_EMI = 'yes';
	
	 $plan_details = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
	 $total_amount_due = $plan_details['plan_amount'];
	 $plan_tenure = $plan_details['tenure'];
	 $plan_months_completed = $plan_details['months_completed'];
	 $forgo_amount = $total_amount_due - $chit_amount;
	 $emi_amount = ($total_amount_due / ($plan_tenure-$plan_months_completed));
	 $total_emi = $plan_tenure - $plan_months_completed;
	 $due_emi = $plan_tenure - $plan_months_completed;
	 $emi_paid = '0';
	 $is_active = '1';
	
	 $data = array(
		 'plan_id' => isset($plan_id) ? $plan_id : '',//
		 'group_id' =>  isset($group_id) ? $group_id : '',//
		 'return_chit_amount' =>  isset($return_chit_amount) ? $return_chit_amount : '',//
		 'total_amount_paid' => isset($total_amount_paid) ? $total_amount_paid : '',//
		 'total_amount_due' => isset($total_amount_due) ? $total_amount_due : '',//
		 'chit_amount' => isset($chit_amount) ? $chit_amount : '',//
		 'forgo_amount' => isset($forgo_amount) ? $forgo_amount : '',//
		 'is_on_EMI' => isset($is_on_EMI) ? $is_on_EMI : '',//
		 'emi_amount' => isset($emi_amount) ? $emi_amount : '',//
		 'total_emi' => isset($total_emi) ? $total_emi : '',//
		 'due_emi' => isset($due_emi) ? $due_emi : '',//
		 'emi_paid' => isset($emi_paid) ? $emi_paid : '',//
		 'is_active' => isset($is_active) ? $is_active : '',	//
		 'chit_month' => date("M,Y"),
	 ); 

	$emi_amount = round($emi_amount, 0);
	  for($i=1; $i<=$total_emi; $i++){
		$data2 = array(
			'plan_id' => isset($plan_id) ? $plan_id : '',//
			'member_id' => isset($member_id) ? $member_id : '',//
			'group_id' => isset($group_id) ? $group_id : '',//
			'emi_no' => $i,//
			'chit_emi' => isset($emi_amount) ? $emi_amount : '',//
			'total_emi' => isset($total_emi) ? $total_emi : '',//
			'emi_status' => 'due',//
			'chit_amount' => isset($chit_amount) ? $chit_amount : '',
			'return_chit_amount' => isset($return_chit_amount) ? $return_chit_amount : '',//
			'return_factor' => '0',//
			'chit_emi_months' => isset($due_emi) ? $due_emi : '',//
			'added_date' => date('Y-m-d h:i:s')
		 );
	        $insert_chit_emi = $this->db->insert('chit_emi',$data2);
	 }

	 if(!empty($auction_details)){
		$insertdata = $this->db->insert('tbl_chits',$data);
				$member_name_detail = $this->db->select('name')->where('member_id',$member_id)->get('tbl_members')->row_array();
				$member_name = $member_name_detail['name'];
				$foreman_fees_detail = $this->db->select('foreman_fees,total_months')->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
				$foreman_fees = $foreman_fees_detail['foreman_fees'];
				$total_months = $foreman_fees_detail['total_months'];
				$group_details_member = $this->db->select('total_members')->where('group_id',$group_id)->get('tbl_groups')->row_array(); 
				$group_member = $group_details_member['total_members'];
				$divident_amount = ($total_amount_due -($chit_amount + $foreman_fees)) / $group_member;	
				$divident_data = array(
					'member_name'=>isset($member_name) ? $member_name : '',
					'member_id'=>isset($member_id) ? $member_id : '',
					'plan_id'=>isset($plan_id) ? $plan_id : '',
					'group_id'=>isset($group_id) ? $group_id : '',
					'auction_id'=>$auction_id,
					'divident_amount'=>$divident_amount,
					'month'=>isset($auction_no) ? $auction_no : '',
					'total_months'=>isset($total_months) ? $total_months : '',
					'added_date' => date('Y-m-d h:i:s')
					
				);
					$this->db->insert('tbl_divident',$divident_data);

		if(!empty($auction_details)){
			$output = array(
				'status' => Success,
				'message' => 'Insert Chit Successfully',
				'data' => $insertdata,
			);	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
		); 
	    }
	 }
	 else{
		$output = array(
			'status' => Failure,
			'message' => "NO Data Found.",
			'data' => []
	       ); 
	 }	 
	echo json_encode($output);die;
 	}
	
    public function savefinalbid(){		//17jan2022
		$data =  json_decode($this->data);
		$bid_id = isset($data->bid_id) ? $data->bid_id : '';
		$slot_number = isset($data->slot_number) ? $data->slot_number : '';
		$bid_detial = $this->db->where('bid_id',$bid_id)->get('tbl_bids')->row_array();		
		if(!empty($bid_detial)){
		$auction_id = isset($bid_detial['auction_id']) ? $bid_detial['auction_id'] : '';
		$plan_id = isset($bid_detial['plan_id']) ? $bid_detial['plan_id'] : '';
		$bid_amount = isset($bid_detial['bid_amount']) ? $bid_detial['bid_amount'] : '';
		$member_id = isset($bid_detial['member_id']) ? $bid_detial['member_id'] : '';
		$group_id = isset($bid_detial['group_id']) ? $bid_detial['group_id'] : '';
		$data = array(
			'winning_bid_id' => $bid_id,
			'status'=>'0'
		);
		$auction_update = $this->db->where('auction_id',$auction_id)->update('tbl_auction',$data);	
		$auction_detail = $this->db->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
		$return_chit_amount = isset($auction_detail['plan_amount']) ? $auction_detail['plan_amount'] : '';	
		$auction_no = isset($auction_detail['auction_no']) ? $auction_detail['auction_no'] : '';	
		$plan_details = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
				$total_amount_due = isset($plan_details['plan_amount']) ? $plan_details['plan_amount'] : '';
				$total_amount_paid = '0';
				$is_on_EMI = 'yes';	
				$plan_tenure = isset($plan_details['tenure']) ? $plan_details['tenure'] : '';
				$plan_months_completed = isset($plan_details['months_completed']) ? $plan_details['months_completed'] : '';
				$remaining_month = isset($plan_details['remaining_month']) ? $plan_details['remaining_month'] : '';

				$emiamountupdate = array(
					'months_completed' => $plan_months_completed+1,
					'remaining_month' => $remaining_month-1
				);
				$this->db->where('plan_id',$plan_id)->update('tbl_plans',$emiamountupdate);
				$chit_amount = $bid_detial['bid_amount'];
				$forgo_amount = $total_amount_due - $chit_amount;
				$emi_amount = ($total_amount_due / ($plan_tenure-$plan_months_completed));
				$total_emi = $plan_tenure - $plan_months_completed;//
				$due_emi = $plan_tenure - $plan_months_completed;
				$emi_paid = '0';
				$is_active = '1';
				
				$data = array(
					'plan_id' => isset($plan_id) ? $plan_id : '',
					'group_id' =>  isset($group_id) ? $group_id : '',
					'auction_id' =>  isset($auction_id) ? $auction_id : '',
					'member_id' =>  isset($member_id) ? $member_id : '',
					'return_chit_amount' =>  isset($return_chit_amount) ? $return_chit_amount : '',
					'total_amount_paid' => isset($total_amount_paid) ? $total_amount_paid : '',
					'total_amount_due' => isset($total_amount_due) ? $total_amount_due : '',
					'chit_amount' => isset($chit_amount) ? $chit_amount : '',
					'forgo_amount' => isset($forgo_amount) ? $forgo_amount : '',
					'is_on_EMI' => isset($is_on_EMI) ? $is_on_EMI : '',
					'emi_amount' => isset($emi_amount) ? $emi_amount : '',
					'total_emi' => isset($total_emi) ? $total_emi : '',
					'due_emi' => isset($due_emi) ? $due_emi : '',
					'emi_paid' => isset($emi_paid) ? $emi_paid : '',
					'is_active' => isset($is_active) ? $is_active : '',	
					'slot_number' => isset($slot_number) ? $slot_number : '',
					'chit_month' => date("M,Y"),
					'added_date' => date('Y-m-d h:i:s')
				); 			
				 $emi_amount = round($emi_amount, 0);
				 $chit_emi_months = $plan_months_completed+1;
				$insertdata = $this->db->insert('tbl_chits',$data);
				for($i=1; $i<=$total_emi; $i++){
					$data2 = array(
						'plan_id' => isset($plan_id) ? $plan_id : '',//
						'member_id' => isset($member_id) ? $member_id : '',//
						'group_id' => isset($group_id) ? $group_id : '',//
						'emi_no' => $i,//
						'chit_emi' => isset($emi_amount) ? $emi_amount : '',//
						'total_emi' => isset($total_emi) ? $total_emi : '',//
						'emi_status' => 'due',//
						'chit_amount' => isset($chit_amount) ? $chit_amount : '',
						'return_chit_amount' => isset($return_chit_amount) ? $return_chit_amount : '',//
						'return_factor' => '0',//
						'chit_emi_months' => isset($chit_emi_months) ? $chit_emi_months : '',//
						'is_partial_payment' => 'No'
					);
						// $insert_chit_emi = $this->db->insert('chit_emi',$data2);
						$chit_emi_months++;
				}
				$win_bid_acc = array(
					'is_bid_accepted'=>'yes'
				);
				$this->db->where('bid_id',$bid_id)->update('tbl_bids',$win_bid_acc);				
				$member_name_detail = $this->db->select('name')->where('member_id',$member_id)->get('tbl_members')->row_array();
						$member_name = $member_name_detail['name'];
						$foreman_fees_detail = $this->db->select('foreman_fees,total_months')->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
						$foreman_fees = $foreman_fees_detail['foreman_fees'];
						$total_months = $foreman_fees_detail['total_months'];
						$group_details_member = $this->db->select('total_members')->where('group_id',$group_id)->get('tbl_groups')->row_array(); 
						$group_member = $group_details_member['total_members'];
						$forman_amount = $total_amount_due*$foreman_fees/100;
						$divident_amount = ($total_amount_due -($chit_amount + $forman_amount)) / $group_member;	
						$divident_data = array(
							'member_name'=>isset($member_name) ? $member_name : '',
							'member_id'=>isset($member_id) ? $member_id : '',
							'plan_id'=>isset($plan_id) ? $plan_id : '',
							'group_id'=>isset($group_id) ? $group_id : '',
							'auction_id'=>$auction_id,
							'divident_amount'=>$divident_amount,
							'month'=>isset($auction_no) ? $auction_no : '',
							'total_months'=>isset($total_months) ? $total_months : '',	
							'added_date' => date('Y-m-d h:i:s')
						);
						$dividint_months_sub = $plan_months_completed+1;
							$this->db->insert('tbl_divident',$divident_data);
							$emidivident = array(
								'divident' => $divident_amount
							);
							$this->db->where('emi_no',$dividint_months_sub)->where('group_id',$group_id)->where('plan_id',$plan_id)->update('tbl_emi',$emidivident);
							
							$check_insert_id = $this->db->select('insert_id')->ORDER_BY('general_ledger_master_id','DESC')->get('tbl_general_ledger_master')->row_array();
							if(!empty($check_insert_id['insert_id'])){
                                $insert_id =$check_insert_id['insert_id'] + 1;
                            }else{
                                $insert_id = 1;
                            }
                            
                            $plandetails = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
                            $ledgerdata1 = array(
                                    'insert_id'=> $insert_id,
                                    'c_code' => '503',
                                    'category_desc' => 'Bid_Amount',
                                    'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
                                    'transaction_mode' => 'J1 - Internal',
                                    'transaction_type' => 'Bid_Amount',
                                    'transaction_description' => 'Final Bid Amt',
                                    'amount' => isset($bid_detial['bid_amount']) ? $bid_detial['bid_amount'] : '',
                                    'dr_cr' =>'Dr',
                                    'sub_id' => isset($member_id) ? $member_id : '',
                                    'account_name' => isset($member_id) ? $member_id : '',
                                    'added_date' => date('Y-m-d h:i:s'),
                                    'account_description' => $this->getGlAccount('1003'),
                                    'gl_account' => '1003',
                                    'type' => 'Payment',
                                    'user' => 'Senthil',
                                );
                                 $insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
                                 $insert_id = $this->db->insert_id();
                                 $selest_ensert_id = $this->db->where('general_ledger_master_id',$insert_id)->get('tbl_general_ledger_master')->row_array();
                                  $ledgerdata2 = array(
                                    'insert_id'=> $selest_ensert_id['insert_id'],
                                    'c_code' => '503',
                                    'category_desc' => 'Bid_Amount',
                                    'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
                                    'transaction_mode' => 'J1 - Internal',
                                    'transaction_type' => 'Bid_Amount',
                                    'transaction_description' => 'Final Bid Amt',
                                    'amount' => isset($bid_detial['bid_amount']) ? $bid_detial['bid_amount'] : '',
                                    'dr_cr' =>'Cr',
                                    'sub_id' => isset($member_id) ? $member_id : '',
                                    'account_name' => isset($member_id) ? $member_id : '',
                                    'added_date' => date('Y-m-d h:i:s'),
                                    'account_description' => $this->getGlAccount('1004'),
                                    'gl_account' => '1004',
                                    'type' => 'Payment',
                                    'user' => 'Senthil',
                                );
                                $this->db->insert('tbl_general_ledger_master',$ledgerdata2);
                                
                                $ledger_to_Divident = $this->db->where('code','501')->get('tbl_transaction_type_master')->row_array();
                                if(!empty($ledger_to_Divident['transaction_type_master_id'])){
                                    $get_selection_data = $this->db->where('transaction_type_id',$ledger_to_Divident['transaction_type_master_id'])->get('tbl_transcation_type_category_selection_master')->row_array();
                                    if(!empty($get_selection_data['general_ledger_id'])){
                                        $get_general_to = $this->db->where('id',$get_selection_data['general_ledger_id'])->get('tbl_ledger_account')->row_array();
                                    }if(!empty($get_selection_data['general_ledger_id_from'])){
                                        $get_general_from = $this->db->where('id',$get_selection_data['general_ledger_id_from'])->get('tbl_ledger_account')->row_array();
                                    }if(!empty($get_selection_data['category_id'])){
                                        $getcategree = $this->db->where('category_id',$get_selection_data['category_id'])->get('tbl_category')->row_array();
                                    }
                                }
                            $ledgerdata3 = array(
                                    'insert_id'=> $insert_id,
                                    'c_code' => isset($getcategree['code']) ? $getcategree['code'] : '501',
                                    'category_desc' => isset($getcategree['name']) ? $getcategree['name'] : 'Dividend Disbursal',
                                    'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
                                    'transaction_mode' => 'J1 - Internal',
                                    'transaction_type' => isset($ledger_to_Divident['name']) ? $ledger_to_Divident['name'] : 'Dividend',
                                    'transaction_description' => 'Dividend Allocation by plan to Individual subscribers',
                                    'amount' => isset($divident_amount) ? $divident_amount : '',
                                    'dr_cr' =>'Dr',
                                    'sub_id' => isset($member_id) ? $member_id : '',
                                    'account_name' => isset($member_id) ? $member_id : '',
                                    'added_date' => date('Y-m-d h:i:s'),
                                    'account_description' => isset($get_general_from['name']) ? $get_general_from['name'] : 'Dividend on Subscriptions',
                                    'gl_account' => '1002',
                                    'type' => 'Payment',
                                    'user' => 'Senthil',
                                );
                                 $insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata3);
                                 $insert_id = $this->db->insert_id();
                                 $selest_ensert_id = $this->db->where('general_ledger_master_id',$insert_id)->get('tbl_general_ledger_master')->row_array();
                                  $ledgerdata4 = array(
                                    'insert_id'=> $selest_ensert_id['insert_id'],
                                    'c_code' => isset($getcategree['code']) ? $getcategree['code'] : '501',
                                    'category_desc' => isset($getcategree['name']) ? $getcategree['name'] : 'Dividend Disbursal',
                                    'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
                                    'transaction_mode' => 'J1 - Internal',
                                    'transaction_type' => isset($ledger_to_Divident['name']) ? $ledger_to_Divident['name'] : 'Dividend',
                                    'transaction_description' => 'Dividend Allocation by plan to Individual subscribers',
                                    'amount' => isset($divident_amount) ? $divident_amount : '',
                                    'dr_cr' =>'Cr',
                                    'sub_id' => isset($member_id) ? $member_id : '',
                                    'account_name' => isset($member_id) ? $member_id : '',
                                    'added_date' => date('Y-m-d h:i:s'),
                                    'account_description' => isset($get_general_to['name']) ? $get_general_to['name'] : 'Subscribers A/c',
                                    'gl_account' => '1002',
                                    'type' => 'Payment',
                                    'user' => 'Senthil',
                                );
                                $this->db->insert('tbl_general_ledger_master',$ledgerdata4);
                				
			$output = array(
				'status' => Success,
				'message' => 'Final bid set successfully.Now auction has been closed and No more bid will be accepted ',
			    'data' => []
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "failed",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}	
	
	public function cancelAuction(){		//11jan2022 new
		$data =  json_decode($this->data);
		$auction_id = isset($data->auction_id) ? $data->auction_id : '';
		$data = array(
			'status' => '3'
		);
		$sql = $this->db->where('auction_id',$auction_id)->update('tbl_auction',$data);
		$chit_detail = $this->db->where('auction_id',$auction_id)->get('tbl_chits')->row_array();
		if(!empty($chit_detail)){
			$group_id = isset($chit_detail['group_id']) ? $chit_detail['group_id'] : '';
			$plan_id = isset($chit_detail['plan_id']) ? $chit_detail['plan_id'] : '';
			$member_id = isset($chit_detail['member_id']) ? $chit_detail['member_id'] : '';
			$this->db->where('group_id',$group_id)->where('member_id',$member_id)->where('plan_id',$plan_id)->delete('chit_emi');
			$sqlsql = $this->db->where('auction_id',$auction_id)->delete('tbl_chits');
		}
		if(!empty($sqlsql)){
			$output = array(
				'status' => Success,
				'message' => 'Auction cancelled Successfully',
			    'data' => []
            );	
		}else{
			$output = array(
				'status' => Failure,
				'message' => "failed",
				'data' => []
            );
		}
		echo json_encode($output);die;
	}
	
	
	
	public function payDues(){
		$data =  json_decode($this->data,true);	
		$ten = isset($data['ten']) ? $data['ten'] : '';
		$twenty = isset($data['twenty']) ? $data['twenty'] : '';
		$fifty = isset($data['fifty']) ? $data['fifty'] : '';
		$hundred = isset($data['hundred']) ? $data['hundred'] : '';
		$two_hundred = isset($data['two_hundred']) ? $data['two_hundred'] : '';
		$five_hundred = isset($data['five_hundred']) ? $data['five_hundred'] : '';
		$two_thousand = isset($data['two_thousand']) ? $data['two_thousand'] : '';
		$agent_id = isset( $data['agent_id']) ? $data['agent_id'] :'';
		$payment_mode = isset( $data['payment_mode']) ? $data['payment_mode'] :'';
		$member_id = isset( $data['member_id']) ? $data['member_id'] :'';
		$total_sum = isset( $data['total_sum']) ? $data['total_sum'] :'';
		$gst = isset($data['gst']) ? $data['gst'] :'';
		$gstpercentage = isset($data['gst_percentage']) ? $data['gst_percentage'] :'';
		
		if($payment_mode == 'Cash'){
		   $is_payment_by_cash = 1; 
		}else{
		  $is_payment_by_cash = 0;  
		}
		
		$bank_account_id = isset( $data['bank_account_id']) ? $data['bank_account_id'] :'';
		
		 if(!empty($gstpercentage)){
		        $transcation_amount1 =  $total_sum * $gstpercentage/100;
		        $transcation_amount = $transcation_amount1 + $total_sum;
		 }else{
		        $transcation_amount = $total_sum;
		 }
		
		if(!empty($bank_account_id)){
		    $type = 'receipt';
		    $bnk_trans = $this->banktranscationcalculation($bank_account_id,$transcation_amount,$type);
		}
		
		$cheque_number = isset( $data['cheque_number']) ? $data['cheque_number'] :'';
		$payment_proof = isset( $data['payment_proof']) ? $data['payment_proof'] :'';
		if(!empty($member_id)){
    		$type = 'receipt';
    		$abc = $this->current_opening($member_id,$transcation_amount,$type);
		}
		$emi_ids = array();
		$total_amounts = array();
		$new_status = array();
		foreach ($data as $keys=>$values){

			$this->PayDuesGeneralLedger($values,$bank_account_id,$payment_mode);

			// die;

			$emi_id = isset( $values['id']) ? $values['id'] :''	;		
			$emi_amount=  isset( $values['amount']) ? $values['amount'] :'' ;
			$status = isset( $values['status']) ? $values['status'] :'';
			$new_status[] =  $status;
			if($status == 'chit_emi'){
				$data = $this->db->where('chit_emi_id',$emi_id)->get('chit_emi')->row_array();
				if(!empty($emi_id)){
					if($data['emi_status'] == 'due'){
						if($data['chit_emi'] == $emi_amount){
							$update_status = array(
							'emi_status' =>'paid'
						);
						$update1 = $this->db->where('chit_emi_id',$emi_id)->update('chit_emi',$update_status);
						$member_id = $this->db->select('member_id,plan_id')->where('chit_emi_id',$emi_id)->get('chit_emi')->row_array();
					    $plan_name = $this->db->select('plan_name')->where('plan_id',$member_id['plan_id'])->get('tbl_plans')->row_array();
						$emi_ids[] = $emi_id;
						$total_amounts[] = $emi_amount;
								$output = array(
									'status'=>'success',
									'message'=>'payed emi successfully',
									'data'=>$status
								);		
						}
						elseif($data['emi_status']>$emi_amount){
							if($data['is_partial_payment']=='Yes'){
								$partial_pay_amount = $data['partial_paid_amount']+$emi_amount;
								$partial_due_amount = $data['amount_due']-$emi_amount;
							    $partial_pay_amount = round($partial_pay_amount, 0);
							    $partial_due_amount = round($partial_due_amount, 0);
								$partial_update = array(
									'partial_paid_amount'=>$partial_pay_amount,
									'amount_due'=>$partial_due_amount
								);
								$update_data = $this->db->where('chit_emi_id',$emi_id)->update('chit_emi',$partial_update);
									$this->db->where('chit_emi_id',$emi_id)->update('chit_emi',$data);
    								$member_id = $this->db->select('member_id,plan_id')->where('chit_emi_id',$emi_id)->get('chit_emi')->row_array();
    								$plan_name = $this->db->select('plan_name')->where('plan_id',$member_id['plan_id'])->get('tbl_plans')->row_array();
    								$emi_ids[] = $emi_id;
    								$total_amounts[] = $emi_amount;

								$output = array(
									'status'=>'success',
									'message'=>'Partial Payment Successfull',
									'data'=>$emi_id.','.'chit_emi'
								);

							}else{
								$partial_paid_amount = $data['chit_emi'] - $emi_amount;
								$partial_paid_amount = round($partial_paid_amount, 0);
							    $emi_amount = round($emi_amount, 0);
								$data = array(
									'is_partial_payment'=>'Yes',
									'amount_due'=>$partial_paid_amount,
									'partial_paid_amount'=>$emi_amount
								);
								$this->db->where('chit_emi_id',$emi_id)->update('chit_emi',$data);
								$member_id = $this->db->select('member_id,plan_id')->where('chit_emi_id',$emi_id)->get('chit_emi')->row_array();
								$plan_name = $this->db->select('plan_name')->where('plan_id',$member_id['plan_id'])->get('tbl_plans')->row_array();
								$emi_ids[] = $emi_id;
								$total_amounts[] = $emi_amount;

								$output = array(
									'status'=>'success',
									'message'=>'Partial Payment Successfull',
									'data'=>$emi_id.','.'chit_emi'
								);
							}							
						}else{
							$output = array(
								'status'=>'Failure',
								'message'=>'Amount not Correct',
								'data'=>$emi_id.','.'chit_emi'
							);
						}
					}
					elseif($data['emi_status'] == 'paid'){
						$output = array(
							'status' => 'Failure',
							'message' => 'This emi already paid',
							'data'=>$emi_id.','.'chit_emi'
						);
					}
				}
			}			
		  
		  	if($status == 'plan_emi'){
				$data = $this->db->where('emi_id',$emi_id)->get('tbl_emi')->row_array();
				if(!empty($emi_id)){
					if($data['emi_status'] == 'due'){
						if($data['plan_emi'] == $emi_amount){
							$update_status = array(
							'emi_status' =>'paid'
						);
						$update2 = $this->db->where('emi_id',$emi_id)->update('tbl_emi',$update_status);
						$member_id = $this->db->select('member_id,plan_id')->where('emi_id',$emi_id)->get('tbl_emi')->row_array();
						$plan_name = $this->db->select('plan_name')->where('plan_id',$member_id['plan_id'])->get('tbl_plans')->row_array();
							$emi_ids[] = $emi_id;
							$total_amounts[] = $emi_amount;

						$output = array(
							'status'=>'success',
							'message'=>'Dues Paid Successfully',
							'data'=>$emi_id.','.'plan_emi'
						);
						}elseif($data['amount_due'] == $emi_amount){
							$update_status = array(
							'emi_status' =>'paid'
						);
						$update2 = $this->db->where('emi_id',$emi_id)->update('tbl_emi',$update_status);
						$member_id = $this->db->select('member_id,plan_id')->where('emi_id',$emi_id)->get('tbl_emi')->row_array();
						$plan_name = $this->db->select('plan_name')->where('plan_id',$member_id['plan_id'])->get('tbl_plans')->row_array();
							$emi_ids[] = $emi_id;
							$total_amounts[] = $emi_amount;

						$output = array(
							'status'=>'success',
							'message'=>'Dues Paid Successfully',
							'data'=>$emi_id.','.'plan_emi'
						);
						}else if($data['plan_emi'] > $emi_amount){
							if($data['is_partial_payment']=='Yes'){
								$partial_pay_amount = $data['partial_paid_amount']+$emi_amount;
								$partial_due_amount = $data['amount_due']-$emi_amount;
								$partial_pay_amount = round($partial_pay_amount, 0);
								$partial_due_amount = round($partial_due_amount, 0);
								$partial_update = array(
									'partial_paid_amount'=>$partial_pay_amount,
									'amount_due'=>$partial_due_amount
								);
								$update_data = $this->db->where('emi_id',$emi_id)->update('tbl_emi',$partial_update);
								$member_id = $this->db->select('member_id,plan_id')->where('emi_id',$emi_id)->get('tbl_emi')->row_array();
								$plan_name = $this->db->select('plan_name')->where('plan_id',$member_id['plan_id'])->get('tbl_plans')->row_array();
								$emi_ids[] = $emi_id;
								$total_amounts[] = $emi_amount;	
								
							}else{
								$partial_paid_amount = $data['plan_emi'] - $emi_amount;
								$partial_paid_amount = round($partial_paid_amount, 0);
								if(!empty($data['divident'])){
									$amount_due = $partial_paid_amount - $data['divident'];
								}else{
									$amount_due = $partial_paid_amount;
								}
								$emi_amount = round($emi_amount, 0);
								$amount_due1 = round($amount_due, 0);
								$data = array(
									'is_partial_payment'=>'Yes',
									'amount_due'=>$amount_due1,
									'partial_paid_amount'=>$emi_amount
								);
								$this->db->where('emi_id',$emi_id)->update('tbl_emi',$data);
								$member_id = $this->db->select('member_id,plan_id')->where('emi_id',$emi_id)->get('tbl_emi')->row_array();
								$plan_name = $this->db->select('plan_name')->where('plan_id',$member_id['plan_id'])->get('tbl_plans')->row_array();
								$emi_ids[] = $emi_id;
								$total_amounts[] = $emi_amount;	
								if($amount_due1 == 0){
									$update_status = array(
										'emi_status' =>'paid'
										);
									$update2 = $this->db->where('emi_id',$emi_id)->update('tbl_emi',$update_status);
								}
							}

						}else{
							$output = array(
								'status'=>'Failure',
								'message'=>'Amount not Correct',
								'data'=>$emi_id.','.'plan_emi'
							);
						}
					}else{
						$output = array(
							'status' => 'Failure',
							'message' => 'This emi already paid',
							'data'=>$emi_id.','.'plan_emi'
						);
					}
				}						
			}
			
			
		}
		$date = date('d/m/y H:i:s');
		$new_zrr = array();
		$emi_values = implode(',',$emi_ids);
		$sumofdueemi = 0; $i=0;
		foreach($total_amounts as $total_amounts){
			$sumofdueemi+= $total_amounts;	
			$i++;
		}
		if(!empty($agent_id)){
			$is_payment_by_agent = 'yes';
		}
// 	    if($gst == 'Yes'){
// 		    $data = $this->db->get('tbl_gst')->row_array();
// 		    $gst_percentage = $data['gst_percentage'];
// 		    $gst_amount = $total_sum * $gst_percentage / 100;
// 		    $after_gst_amount = $total_sum + $gst_amount;
// 		    $is_gst_included = 'Yes';
// 		}
        
        if(!empty($gst)){
		    $data = $this->db->where('gst_id',$gst)->get('tbl_gst')->row_array();
		    $gst_percentage = $data['gst_percentage'];
		    $gst_name = $data['name'];
		    $gst_amount = $total_sum * $gst_percentage / 100;
		    $after_gst_amount = $total_sum + $gst_amount;
		    $is_gst_included = 'Yes';
		 }

		$history_data = array(
			'emi_count'=> $i,
			'transaction_type'=>'subscriber money',
			'transaction_for'=>'pay_emi',
			'emi_ids'=>$emi_values,
			'emi_type'=> $new_status[0],
			'transaction_amount'=>$sumofdueemi ,
			'added_date'=>$date, // added_date
			'plan_id'=>isset($member_id['plan_id']) ? $member_id['plan_id'] : '',
			'plan_name'=>isset($plan_name['plan_name']) ? $plan_name['plan_name'] : '',
			'subscriber_id'=> isset($member_id['member_id']) ? $member_id['member_id'] : '', //Subscriber_id
			'is_payment_by_agent'=> isset($is_payment_by_agent) ? $is_payment_by_agent : 'no',
			'agent_id'=>isset($agent_id) ? $agent_id : '',
			'payment_mode'=>isset($payment_mode) ? $payment_mode : '',
			'bank_account_id'=>isset($bank_account_id) ? $bank_account_id : '',
			'cheque_number'=>isset($cheque_number) ? $cheque_number : '',
			'payment_proof'=>isset($payment_proof) ? $payment_proof : '',
			'ten' =>isset($ten) ? $ten : '',
			'tewenty' =>isset($twenty) ? $twenty : '',
			'fifty' =>isset($fifty) ? $fifty : '',
			'hundred' =>isset($hundred) ? $hundred : '',
			'two_hundred' =>isset($two_hundred) ? $two_hundred : '',
			'opening_balance' =>isset($abc['opening_balance']) ? $abc['opening_balance'] : '',
			'current_balance' =>isset($abc['current_amount']) ? $abc['current_amount'] : '',
			'five_hundred' =>isset($five_hundred) ? $five_hundred : '',
			'two_thousand' =>isset($two_thousand) ? $two_thousand : '',
			'is_payment_by_cash' =>isset($is_payment_by_cash) ? $is_payment_by_cash : '',
			'added_date' => date('Y-m-d h:i:s'),
			'transaction_month' => date("M,Y"),
			'transaction_amount_after_gst' =>isset($after_gst_amount) ? $after_gst_amount : '',
			'is_gst_included' =>isset($is_gst_included) ? $is_gst_included : '',
			'gst_percentage' =>isset($gst_percentage) ? $gst_percentage : '',
			'gst_amount' =>isset($gst_amount) ? $gst_amount : '',
			'tax_type' => isset($gst_name) ? $gst_name : '',
			'type' =>'receipt'
		  );
		  
		if(!empty($emi_ids)){
		   
    		$this->db->insert('tbl_transactions',$history_data);
    		$insert_id = $this->db->insert_id();
    		// $this->SubmitGeneralLedgerMaster($history_data);
    		$output = array(
    			'status'=>'success',
    			'message'=>'Dues Paid Successfully',
    			'data'=> $emi_id.','.'plan_emi',
     			'transaction_id' => $insert_id
    		);	

		}else{
		   $output = array(
    			'status'=>'failure',
    			'message'=>'Dues Paid Not Successfully',
    			'data'=> [],
     			'transaction_id' => 0
    		);	 
		}
		echo json_encode($output);die;
	}
	
	
	public function GetAuctionEndTime(){
		$current_time = date('H:i:s');
		$current_date = date('m/d/Y');
		$time_date = date('d/m/y H:i:s');
		$data =  json_decode($this->data);
	    $auction_id = $data->auction_id;
		$time = $data->time;
		
		if($time =='start_time'){
			$auction_detail = $this->db->select('start_date,start_time')->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
			if(!empty($auction_detail)){
			$start_time = $auction_detail['start_time'];
				$time1 = date_create($start_time);
				$time2 = date_create($current_time);
				$interval = date_diff($time2, $time1);
				$h = $interval->h;
				$i = $interval->i;
				$s = $interval->s;
				$deff_time = array(
					'hour'=>$h,
					'minute'=>$i,
					'second'=>$s
				);

				// $date1 = date_create($start_date);
				// $date2 = date_create($current_date);
				// $interval_d = date_diff($date1, $date2);
				// $d = $interval_d->d;
				// $m = $interval_d->m;
				// $y = $interval_d->y;
				
				$output = array(
					'status'=>'success',
					'message'=>'date fatch successfullt',
					'data'=>array($deff_time)
				);
			    
			}

		}
		elseif($time == 'end_time'){
			$auction_detail = $this->db->select('end_time,end_date')->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
			if(!empty($auction_detail)){
			$end_time = $auction_detail['end_time'];
// 			$end_date = $auction_detail['end_date'];			
				$time1 = date_create($end_time);
				$time2 = date_create($current_time);
				$interval = date_diff($time2,$time1);
				$h = $interval->h;
				$i = $interval->i;
				$s = $interval->s;
				$deff_time = array(
					'hour'=>$h,
					'minute'=>$i,
					'second'=>$s
				);				
				$output = array(
					'status'=>'success',
					'message'=>'fatch successfully',
					'data'=>array($deff_time)
				);
			}			
		}
	       $nodata = array(
					'hour'=>0,
					'minute'=>0,
					'second'=>0
				);
				
		if(empty($auction_detail)){
			$output = array(
				'status'=>'failure',
				'message'=>'found time is not fatched',
				'data'=>array($nodata)
			);
		}
		
		echo(json_encode($output));die;
	}
	
	public function getTransactionsHistory(){
		$data = $this->db->get('tbl_transactions')->result_array();
		if(!empty($data)){
			$output = array(
				'status'=>'success',
				'message'=>'Transaction history fetch successfully',
				'data'=>$data
			);
		}else{
			$output = array(
				'status'=>'failed',
				'message'=>'No Data Found',
				'data'=>[]
			);
		}
		echo json_encode($output);die;
	}
	
	public function getMemberTransactionsHistory(){
		$data =  json_decode($this->data);
	    $member_id = $data->member_id;
		$transaction_data = $this->db->where('subscriber_id',$member_id)->get('tbl_transactions')->result_array();
		if(!empty($transaction_data)){
			$output = array(
				'status'=>'success',
				'message'=>'Transaction history fetch successfully',
				'data'=>$transaction_data
			);
		}else{
			$output = array(
				'status'=>'failed',
				'message'=>'No Data Found',
				'data'=>[]
			);
		}
		echo json_encode($output);die;
	}
	
	public function getTransactionDetails(){
		$data =  json_decode($this->data);
	    $transaction_id = $data->transaction_id;
		$transaction_data = $this->db->select('emi_ids,emi_type,plan_name')->where('transaction_id',$transaction_id)->get('tbl_transactions')->row_array();
		$emi_values = explode(",",$transaction_data['emi_ids']);
		$status = $transaction_data['emi_type'];
		$plan_name = $transaction_data['plan_name'];
		$data_emi = array();
		if($status == 'plan_emi'){
			foreach($emi_values as $keys=>$values){
				$emi_data = $this->db->select('plan_emi,emi_id,added_date,emi_no,emi_month')->where('emi_id',$values)->get('tbl_emi')->row_array();				
				$data = array(
					'emi_amount' => $emi_data['plan_emi'],
					'emi_id'=> $emi_data['emi_id'],
					'plan_name'=> $plan_name,
					'paid_date'=> $emi_data['added_date'],
					'emi_number'=> $emi_data['emi_no'],
					'emi_month'=> $emi_data['emi_month'],
				);
				$data_emi[] = $data;
			}			
		}
		if($status == 'chit_emi'){
			foreach($emi_values as $keys=>$values){				
				$emi_data = $this->db->select('chit_emi,chit_emi_id,added_date,emi_no,chit_emi_months')->where('chit_emi_id',$values)->get('chit_emi')->row_array();				
				$data = array(
					'emi_amount' => $emi_data['chit_emi'],
					'emi_id'=> $emi_data['chit_emi_id'],
					'plan_name'=> $plan_name,
					'paid_date'=> $emi_data['added_date'],
					'emi_number'=> $emi_data['emi_no'],
					'emi_month'=> $emi_data['chit_emi_months'],
				);
				$data_emi[] = $data;
			}
		}				
		if(!empty($data_emi)){
			$output = array(
				'status'=>'success',
				'message'=>'Transaction history fatch successfull',
				'data'=>$data_emi
			);
		}else{
			$output = array(
				'status'=>'failed',
				'message'=>'No Data Found',
				'data'=>[]
			);
		}
		echo json_encode($output);die;
	}
	
	public function startAuctionAutomatically(){ //15jan2022
	   // $this->load->helper('file');
    //     $data = 'My Text here';
    //     $data .= PHP_EOL;
    //     write_file('/home1/molni2j8/public_html/www.premad.in/chitfund_api/images/product/cron_log.txt', json_encode($data));
        
		$date = date('m/d/Y');
		$time = date('H:i:s');	
		$auction_detail = $this->db->where('status','2')->where('start_date',$date)->get('tbl_auction')->result_array();
		foreach($auction_detail as $keys=>$values){
			$starttime=$values['start_time'];
			$auction_id = $values['auction_id'];
			if($time>$starttime){					
					$data = array(
						'status' => '1'
					);
				$update = $this->db->where('auction_id',$auction_id)->update('tbl_auction',$data);
				$output = array(
					'success'=>'success',
					'message'=>'auction start',
					'auction_id'=>$auction_id
				);
				echo json_encode($output);die;
			}			
			
		}			
	}
	
	public function getsubscriberchit(){
		$data = json_decode($this->data);
		$member_id = $data->member_id;
		$chit_detail = $this->db->where('member_id',$member_id)->get('tbl_chits')->row_array();
		$plan_id = isset($chit_detail['plan_id']) ? $chit_detail['plan_id'] : '';
		$group_id = isset($chit_detail['group_id']) ? $chit_detail['group_id'] : '';
		$member_id = isset($chit_detail['member_id']) ? $chit_detail['member_id'] : '';
		$all_data = array();
		$plan_name_detail = $this->db->select('plan_name')->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
		$group_name_detail = $this->db->select('group_name')->where('group_id',$group_id)->get('tbl_groups')->row_array();
		$member_name_detail = $this->db->select('name')->where('member_id',$member_id)->get('tbl_members')->row_array();
		$member_name = $member_name_detail;
		if(is_array($plan_name_detail) || is_array($group_name_detail)  || is_array($member_name) || is_array($chit_detail)){
	    	$all_data[] = array_merge($plan_name_detail,$group_name_detail,$member_name,$chit_detail);
		}
		if(!empty($chit_detail)){
			$output = array(
				'status'=>'success',
				'message'=>'chit detail fatch successfull',
				'data'=>$all_data
			);
		}else{
			$output = array(
				'status'=>'failed',
				'message'=>'No Data Found',
				'data'=>[]
			);
		}
		echo json_encode($output);die;
	}
	
	public function GetBankAccountsList(){
		$data = $this->db->get('bank_accounts')->result_array();
		if(!empty($data)){
			$output = array(
				'status'=>'success',
				'message'=>'bank detail fatch successfull',
				'data'=>$data
			);
		}else{
			$output = array(
				'status'=>'failed',
				'message'=>'No Data Found',
				'data'=> []
			);
		}
		echo json_encode($output);die;
	}
	
	public function chit_handover(){
		$data = json_decode($this->data);
		$chit_id = isset($data->chit_id) ? $data->chit_id : '';		
		$payment_mode = isset($data->payment_mode) ? $data->payment_mode : '';
		$amount = isset($data->amount) ? $data->amount : '';
		$ten = isset($data->ten) ? $data->ten : '';
		$twenty = isset($data->twenty) ? $data->twenty : '';
		$fifty = isset($data->fifty) ? $data->fifty : '';
		$hundred = isset($data->hundred) ? $data->hundred : '';
		$two_hundred = isset($data->two_hundred) ? $data->two_hundred : '';
		$five_hundred = isset($data->five_hundred) ? $data->five_hundred : '';
		$two_thousand = isset($data->two_thousand) ? $data->two_thousand : '';
		$gst = isset($data->gst) ? $data->gst : '';
		$chit_detail = $this->db->select('member_id,slot_number,plan_id')->where('chit_id',$chit_id)->get('tbl_chits')->row_array();

		
		if(!empty($chit_detail)){
		    $member_id = $chit_detail['member_id'];
        		$transcation_amount = $amount;
        		$type = 'payment';
        		$abc = $this->current_opening($member_id,$transcation_amount,$type);
		}
		
        if($payment_mode == 'cheque'){
                $cheque_no = $data->cheque_no;	
    			$bank_account_id = $data->bank_account_id;
    			$payment_proof =  isset($data->payment_proof) ? $data->payment_proof : '';
    
				
        }elseif($payment_mode == 'online'){
			    $bank_account_id = $data->bank_account_id;
		     	$payment_proof =  isset($data->payment_proof) ? $data->payment_proof : '';
        }
            
            $chit_detail_is_hnd = $this->db->select('is_hand_over')->where('chit_id',$chit_id)->get('tbl_chits')->row_array();
			if($chit_detail_is_hnd['is_hand_over']=='Yes'){
				$output = array(
					'status'=>'Failure',
					'message'=>'Chit is already handovered',
					'data'=>''
				);
			}else{
				$chit_data = array(
					'is_payment_by_cheque'=>'Yes',
					'cheque_no'=>isset($cheque_no) ? $cheque_no : '',
					'bank_account'=>isset($bank_account_number) ? $bank_account_number : '',
					'is_hand_over'=>'Yes',
					'payment_mode'=>isset($payment_mode) ? $payment_mode : '',
					'payment_proof'=>isset($payment_proof) ? $payment_proof : '',
					'handover_amount_after_chearing_dues'=>isset($amount) ? $amount : '',
					'update_date' => date('Y-m-d h:i:s')
				);
				$chit_update = $this->db->where('chit_id',$chit_id)->update('tbl_chits',$chit_data);
				
				
				 if(!empty($gst)){
        		    $data = $this->db->where('gst_id',$gst)->get('tbl_gst')->row_array();
        		    $gst_percentage = $data['gst_percentage'];
        		    $gst_amount = $amount * $gst_percentage / 100;
        		    $after_gst_amount = $amount + $gst_amount;
        		    $is_gst_included = 'Yes';
        		}

				$chit_detail = $this->db->where('chit_id',$chit_id)->get('tbl_chits')->row_array();
				$plandetails = $this->db->where('plan_id',$chit_detail['plan_id'])->get('tbl_plans')->row_array();
				$company_transaction = array(
					'transaction_type'=>'subscriber money',
					'transaction_amount'=> isset($amount) ? $amount : '0',
					'subscriber_id'=> isset($member_id) ? $member_id : '',
					'transaction_for'=>'Chit Handover ',
					'payment_mode'=>isset($payment_mode) ? $payment_mode : '',
					'bank_account_id'=> isset($bank_account_id) ? $bank_account_id : '',
					'cheque_number'=> isset($cheque_no) ? $cheque_no : '',
					'payment_proof'=> isset($payment_proof) ? $payment_proof : '',
					'opening_balance' =>isset($abc['opening_balance']) ? $abc['opening_balance'] : '',
			        'current_balance' =>isset($abc['current_amount']) ? $abc['current_amount'] : '',
					'added_date' => date('Y-m-d h:i:s'),
					'transaction_month' => date("M,Y"),
					'transaction_amount_after_gst' =>isset($after_gst_amount) ? $after_gst_amount : '',
        			'is_gst_included' =>isset($is_gst_included) ? $is_gst_included : '',
        			'gst_percentage' =>isset($gst_percentage) ? $gst_percentage : '',
        			'gst_amount' =>isset($gst_amount) ? $gst_amount : '',
        			'type' => 'receipt',
				);
			
				$this->db->insert('tbl_transactions',$company_transaction);

				$member_data_2 = $this->get_member_detail($member_id);
				$ledgerdata1 = array(
					'insert_id'=> '1',
					'c_code' => '502',
					'plan_id' => $chit_detail['plan_id'],
					'category_desc' => 'Prized Money payment',
					'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
					'transaction_mode' => 'B2 - Online transfer',
					'transaction_type' => 'Prized Payment',
					'transaction_description' => '',
					'amount' => isset($amount) ? $amount :'',
					'dr_cr' =>'Dr',
					'sub_id' => isset($member_data_2['subscriber_id']) ? $member_data_2['subscriber_id'] : '',
					'account_name' => isset($member_data_2['name']) ? $member_data_2['name'] : '',
					'added_date' => date('Y-m-d h:i:s'),
					'account_description' => $this->getGlAccount('1002'),
					'gl_account' => '1002',
					'type' => 'Payment',
					'user'=> 'Senthil',
					'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
				);
				$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
				$ledgerdata1 = array(
					'insert_id'=> '1',
					'c_code' => '502',
					'plan_id' => $chit_detail['plan_id'],
					'category_desc' => 'Prized Money payment',
					'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
					'transaction_mode' => 'B2 - Online transfer',
					'transaction_type' => 'Prized Payment',
					'transaction_description' => '',
					'amount' => isset($amount) ? $amount :'',
					'dr_cr' =>'Cr',
					'sub_id' => isset($member_data_2['subscriber_id']) ? $member_data_2['subscriber_id'] : '',
					'account_name' => isset($member_data_2['name']) ? $member_data_2['name'] : '',
					'added_date' => date('Y-m-d h:i:s'),
					'account_description' => $this->getGlAccount('1011'),
					'gl_account' => '1011',
					'type' => 'Payment',
					'user' =>'Senthil',
					'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
				);
				$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);

				
				$this->paySlotsCurrentmies($chit_detail['slot_number'],$chit_detail['plan_id']);

				$output = array(
					'status'=>'success',
					'message'=>'Chit handover successfully',
					'data'=>''
				);	
			}
		echo json_encode($output);die;
	}
	
	public function SubmitCompanyTransaction(){
		$data = json_decode($this->data);
		$transaction_type = isset($data->transaction_type) ? $data->transaction_type : '';
		$amount = isset($data->amount) ? $data->amount : '';
		$transaction_for = isset($data->transaction_for) ? $data->transaction_for : '';
		$payment_method = isset($data->payment_method) ? $data->payment_method : '';	
		$type = isset($data->type) ? $data->type : '';	
		$image_proof = isset($data->image_proof) ? $data->image_proof : '';
		$ten = isset($data->ten) ? $data->ten : '';
		$twenty = isset($data->twenty) ? $data->twenty : '';
		$fifty = isset($data->fifty) ? $data->fifty : '';
		$hundred = isset($data->hundred) ? $data->hundred : '';
		$two_hundred = isset($data->two_hundred) ? $data->two_hundred : '';
		$five_hundred = isset($data->five_hundred) ? $data->five_hundred : '';
		$two_thousand = isset($data->two_thousand) ? $data->two_thousand : '';
		$gst = isset($data->gst) ? $data->gst : '';
		$bank_account = isset($data->bank_account) ? $data->bank_account : '';
		$cheque_number = isset($data->cheque_number) ? $data->cheque_number : '';
		$service_provider = isset($data->service_provider) ? $data->service_provider : '';
		$received_by = isset($data->received_by) ? $data->received_by : '';
		$paid_by = isset($data->paid_by) ? $data->paid_by : '';
		$from = isset($data->from) ? $data->from : '';
		$to = isset($data->to) ? $data->to : '';
		
		if(!empty($cheque_number)){
		    $is_payment_by_cheque ='yes';
		}
		if($payment_method == 'cash'){
		   $is_payment_by_cash = 1;  
		}else{
		   $is_payment_by_cash = 0; 
		}
		if($transaction_type == 'subscriber money'){
			$subscriber = isset($data->member_id) ? $data->member_id : '';
			$member_id = $subscriber;
			$transcation_amount = $amount;
			$abc = $this->current_opening($member_id,$transcation_amount,$type);
    			if(isset($subscriber)){
    		    $member_del = $this->db->where('member_id',$subscriber)->get('tbl_members')->row_array();
    		    $member_unallocated_amount = $member_del['unallocated_amount'];
    		    if($type == 'payment'){
    		        $all_amt = isset($member_unallocated_amount) ? $member_unallocated_amount : '0';
    		        $total_cal = $all_amt - $amount;
    		        $data = array(
    		            'unallocated_amount' => $total_cal,
    		            );
    		        $this->db->where('member_id',$subscriber)->update('tbl_members',$data);
    		    }
    		    
    		     if($type == 'receipt'){
    		        $all_amt = isset($member_unallocated_amount) ? $member_unallocated_amount : '0';
    		        $total_cal = $all_amt + $amount;
    		        $data = array(
    		            'unallocated_amount' => $total_cal,
    		            );
    		        $this->db->where('member_id',$subscriber)->update('tbl_members',$data);
    		    }
    		}
		}
// 		if($gst=='Yes'){
// 		    $data = $this->db->get('tbl_gst')->row_array();
// 		    $gst_percentage = $data['gst_percentage'];
// 		    $gst_amount = $amount * $gst_percentage / 100;
// 		    $after_gst_amount = $amount + $gst_amount;
// 		    $is_gst_included = 'Yes';
// 		}
		
		if(!empty($gst)){
		    $data = $this->db->where('gst_id',$gst)->get('tbl_gst')->row_array();
		    $gst_percentage = isset($data['gst_percentage']) ? $data['gst_percentage'] : '';
		    $gst_name = isset($data['name']) ? $data['name'] : '';
		    $gst_amount = $amount * $gst_percentage / 100;
		    $after_gst_amount = $amount + $gst_amount;
		    $is_gst_included = 'Yes';
		}
		
        if(!empty($after_gst_amount)){
            $transcation_amount = $after_gst_amount;
        }else{
            $transcation_amount = $amount;
        }
		if(!empty($bank_account)){
		    $bank_account_id = $bank_account;
		    $bnk_trans = $this->banktranscationcalculation($bank_account_id,$transcation_amount,$type);
		}
	
		$submit_data = array(
			'transaction_type'=>isset($transaction_type) ? $transaction_type : 'null',
			'subscriber_id'=>isset($subscriber) ? $subscriber : 'null',
			'service_provider_id'=>isset($service_provider) ? $service_provider : 'null',
			'transaction_amount'=>isset($amount) ? $amount : 'null',			
			'transaction_for'=>isset($transaction_for) ? $transaction_for : 'null',
			'payment_mode'=>isset($payment_method) ? $payment_method : 'null',
			'bank_account_id'=>isset($bank_account) ? $bank_account : 'null',
			'is_payment_by_cheque'=>isset($is_payment_by_cheque) ? $is_payment_by_cheque : 'null',
			'cheque_number'=>isset($cheque_number) ? $cheque_number : 'null',
			'type'=>isset($type) ? $type : 'null',
			'received_by'=>isset($received_by) ? $received_by : 'null',
			'paid_by'=>isset($paid_by) ? $paid_by : 'null',
			'payment_proof'=>isset($image_proof) ? $image_proof : 'null',
			'is_payment_by_cash'=>isset($is_payment_by_cash) ? $is_payment_by_cash : '',
			'ten' =>isset($ten) ? $ten : '',
			'tewenty' =>isset($twenty) ? $twenty : '',
			'fifty' =>isset($fifty) ? $fifty : '',
			'hundred' =>isset($hundred) ? $hundred : '',
			'two_hundred' =>isset($two_hundred) ? $two_hundred : '',
			'five_hundred' =>isset($five_hundred) ? $five_hundred : '',
			'two_thousand' =>isset($two_thousand) ? $two_thousand : '',
			'is_gst_included' =>isset($is_gst_included) ? $is_gst_included : '',
			'gst_percentage' =>isset($gst_percentage) ? $gst_percentage : '',
			'gst_amount' =>isset($gst_amount) ? $gst_amount : '',
			'tax_type' => isset($gst_name) ? $gst_name : '',
			'opening_balance' =>isset($abc['opening_balance']) ? $abc['opening_balance'] : '',
			'current_balance' =>isset($abc['current_amount']) ? $abc['current_amount'] : '',
			'transaction_amount_after_gst' =>isset($after_gst_amount) ? $after_gst_amount : '',
			'added_date'=> date('Y-m-d h:i:s'),
			'transaction_month' => date("M,Y")
		);
		
		
	   $data1 = array(
	    'reference' => $type,
	    'is_system' => 'No',
	    'added_date' => date('Y-m-d h:i:s')
	   );
	   
	   $data2 = array(
	    'reference' => $from,
	    'debit' => $amount
	   );
	   
	   $data3 = array(
	    'reference' => $to,
	    'credit' => $amount
	   );
	   
	   $data4 = array(
	    'reference' => $transaction_for
	   );
	  
	   $this->db->insert('tbl_ledger_transactions',$data1);
	   $this->db->insert('tbl_ledger_transactions',$data2);
	   $this->db->insert('tbl_ledger_transactions',$data3);
	   $this->db->insert('tbl_ledger_transactions',$data4);

		
	
		$this->db->insert('tbl_transactions',$submit_data);
		$insert_id = $this->db->insert_id();
		
		if(!empty($insert_id)){
			$output = array(
				'status'=>'success',
				'message'=>'submit data successfully',
				'type'=> $type,
				'transaction_id' => $insert_id,
				'data'=> []
			);
		}else{
			$output = array(
				'status'=>'failed',
				'message'=>'No Data Found',
				'type'=> "",
				'transaction_id' => "",
				'data'=> []
			);
		}
		echo json_encode($output);die;

	}

    public function endauctionnow(){
		$data = json_decode($this->data);
		$auction_id = isset($data->auction_id) ? $data->auction_id : '';
	//	$slot_number = isset($data->slot_number) ? $data->slot_number : '';
		$submit_data = $this->db->where('auction_id',$auction_id)->where('status','1')->get('tbl_auction')->row_array();
		
			if(!empty($submit_data)){					
					$auction_status = array(
						'status' => '0'
					);
				$update = $this->db->where('auction_id',$auction_id)->update('tbl_auction',$auction_status);
				$auction_detail = $this->db->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
				$plan_id =  $auction_detail['plan_id'];
				$group_id = $auction_detail['group_id'];
				$return_chit_amount = $auction_detail['plan_amount'];
				$bid_id = $auction_detail['winning_bid_id'];
				
				$total_amount_paid = '0';
				$is_on_EMI = 'yes';				
				$bid_detail = $this->db->select('bid_amount')->where('auction_id',$auction_id)->get('tbl_bids')->result_array();
				$array_new = array();
				foreach($bid_detail as $keys=>$values){
					$array_new[] = $values['bid_amount'];
				}
				$min_bid = min($array_new);
				$bid_id = $this->db->select('bid_id')->where('auction_id',$auction_id)->where('bid_amount',$min_bid)->get('tbl_bids')->row_array();
				$min_bid_id = $bid_id['bid_id'];				
				$winning_bid_id = array(
					'winning_bid_id'=> isset($min_bid_id) ? $min_bid_id  : '',
				);
				$winning_id_update = $this->db->where('auction_id',$auction_id)->update('tbl_auction',$winning_bid_id);
				$bid_data = $this->db->where('bid_id',$min_bid_id)->get('tbl_bids')->row_array();
				
				
				
				
				
				$member_id = $bid_data['member_id'];
				$plan_id = $bid_data['plan_id'];
				$slot_number = isset($bid_data['slot_number']) ? $bid_data['slot_number'] : '';
				$plan_details = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
				
				$formen_com_gst = $plan_details['min_bid_amount'] * $plan_details['plan_gst'] / 100;
				
				$admission_amount_gst = $plan_details['admission_amount'] *  $plan_details['plan_gst'] / 100;
				
				$chit_amount2 = ($bid_data['bid_amount'] - $formen_com_gst - $plan_details['admission_amount'] - $admission_amount_gst);
				
				$chit_amount = $bid_data['bid_amount'];
				
				$total_amount_due = $plan_details['plan_amount'];
				$plan_tenure = $plan_details['tenure'];
				$plan_months_completed = $plan_details['months_completed'];
				$remaining_month = $plan_details['remaining_month'];
				$emiamountupdate = array(
					'months_completed' => $plan_months_completed+1,
					'remaining_month' => $remaining_month-1
				);
				$this->db->where('plan_id',$plan_id)->update('tbl_plans',$emiamountupdate);
				$forgo_amount = $total_amount_due - $chit_amount;
				$emi_amount = ($total_amount_due / ($plan_tenure-$plan_months_completed));
				$total_emi = $plan_tenure - $plan_months_completed;//
				$due_emi = $plan_tenure - $plan_months_completed;
				$emi_paid = '0';
				$is_active = '1';
				
				$data = array(
					'plan_id' => isset($plan_id) ? $plan_id : '',//
					'group_id' =>  isset($group_id) ? $group_id : '',//
					'member_id' =>  isset($member_id) ? $member_id : '',//
					'auction_id' =>  isset($auction_id) ? $auction_id : '',//
					'return_chit_amount' =>  isset($return_chit_amount) ? $return_chit_amount : '',//
					'total_amount_paid' => isset($total_amount_paid) ? $total_amount_paid : '',//
					'total_amount_due' => isset($total_amount_due) ? $total_amount_due : '',//
					'chit_amount' => isset($chit_amount2) ? $chit_amount2 : '',//
					'forgo_amount' => isset($forgo_amount) ? $forgo_amount : '',//
					'is_on_EMI' => isset($is_on_EMI) ? $is_on_EMI : '',//
					'emi_amount' => isset($emi_amount) ? $emi_amount : '',//
					'total_emi' => isset($total_emi) ? $total_emi : '',//
					'due_emi' => isset($due_emi) ? $due_emi : '',//
					'emi_paid' => isset($emi_paid) ? $emi_paid : '',//
					'is_active' => isset($is_active) ? $is_active : '',
					'slot_number' => isset($slot_number) ? $slot_number : '',
					'chit_month' =>date("M,Y"),
					'added_date' => date('Y-m-d h:i:s')//	 
				); 			
				$emi_amount = round($emi_amount, 0);
				$chit_emi_months = $plan_months_completed+1;
				$this->db->insert('tbl_chits',$data);
				$chit_id = $this->db->insert_id();

				$win_bid_acc = array(
					'is_bid_accepted'=>'yes'
				);
				$this->db->where('bid_id',$min_bid_id)->update('tbl_bids',$win_bid_acc);				
				$member_name_detail = $this->db->select('name')->where('member_id',$member_id)->get('tbl_members')->row_array();
						$member_name = $member_name_detail['name'];
						$foreman_fees_detail = $this->db->select('foreman_fees,total_months')->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
						$foreman_fees = $foreman_fees_detail['foreman_fees'];
						$total_months = $foreman_fees_detail['total_months'];
						$group_details_member = $this->db->select('total_members')->get('tbl_groups')->row_array(); 
						// $group_details_member = $this->db->select('total_members')->where('group_id',$group_id)->get('tbl_groups')->row_array(); 
						$plan_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
						$group_member = $plan_detail['total_subscription'];
						$forman_amount = $total_amount_due*$foreman_fees/100;
						$divident_amount = ($total_amount_due -($chit_amount + $forman_amount)) / $group_member;
						
						if($plan_detail['auction_type'] == 'fixed_auction'){
						    
						    $divident_data = array(
    							'member_name'=>isset($member_name) ? $member_name : '',
    							'member_id'=>isset($member_id) ? $member_id : '',
    							'plan_id'=>isset($plan_id) ? $plan_id : '',
    							'group_id'=>isset($group_id) ? $group_id : '',
    							'auction_id'=>$auction_id,
    							'divident_amount'=>$divident_amount,
    							'month'=>isset($auction_no) ? $auction_no : '',
    							'total_months'=>isset($total_months) ? $total_months : '',
    							'added_date' => date('Y-m-d h:i:s')
    						);
    						$dividint_months_sub = $plan_months_completed+1;
    							$this->db->insert('tbl_divident',$divident_data);
    							$emidivident = array(
    								'divident' => $divident_amount
    							);
    							// $this->db->where('emi_no',$dividint_months_sub)->where('group_id',$group_id)->where('plan_id',$plan_id)->update('tbl_emi',$emidivident);
    							$this->db->where('emi_no',$dividint_months_sub)->where('plan_id',$plan_id)->update('tbl_emi',$emidivident);
						}else{
						    $divident_amount2 = ($total_amount_due -($chit_amount + $forman_amount));
						    
						    $port_array = array(
						        'plan_id' => isset($plan_id) ? $plan_id : '',
						        'group_id' => isset($group_id) ? $group_id : '',
						        'auction_id' => $auction_id,
						        'divident_amount' => $divident_amount2,
						        'added_date' => date('y-m-d H:i:s'),
						        'update_date' => '',
						        );
						    $this->db->insert('divident_port',$port_array);
						}
						
						
                                $ledger_to_Divident = $this->db->where('code','501')->get('tbl_transaction_type_master')->row_array();
                                if(!empty($ledger_to_Divident['transaction_type_master_id'])){
                                    $get_selection_data = $this->db->where('transaction_type_id',$ledger_to_Divident['transaction_type_master_id'])->get('tbl_transcation_type_category_selection_master')->row_array();
                                    if(!empty($get_selection_data['general_ledger_id'])){
                                        $get_general_to = $this->db->where('id',$get_selection_data['general_ledger_id'])->get('tbl_ledger_account')->row_array();
                                    }if(!empty($get_selection_data['general_ledger_id_from'])){
                                        $get_general_from = $this->db->where('id',$get_selection_data['general_ledger_id_from'])->get('tbl_ledger_account')->row_array();
                                    }if(!empty($get_selection_data['category_id'])){
                                        $getcategree = $this->db->where('category_id',$get_selection_data['category_id'])->get('tbl_category')->row_array();
                                    }
                                }
                            
						$this->create_final_bid_ledger($chit_id);	
							
				$output = array(
					'status'=>'success',
					'message'=>'auction close',	
					'winning bid id'=>$min_bid_id,			
					'auction_id'=>$auction_id
				);
			}
			else{
			   	$output = array(
					'status'=>'Failure',
					'message'=>'auction is already close',	
					'winning bid id'=>0,			
					'auction_id'=>0
				);
			}
		echo json_encode($output);die;
	}
	
	
	public function cancelSubscription(){
	  $data =  json_decode($this->data);
      $slot_number = isset($data->slot_number) ? $data->slot_number : '';
      $order_id = isset($data->order_id) ? $data->order_id : '';
      $reason = isset($data->reason) ? $data->reason : '';
	  if($slot_number != '' && $order_id !=''){
        $this->db->where('slot_number',$slot_number)->where('order_id',$order_id)->update('tbl_orders',array('slot_status' => 'cancelled','cancel_reason' => $reason ));
        $getdata1 = $this->db->where('order_id',$order_id)->get('tbl_orders')->row_array();
        $member_id = isset($getdata1['member_id']) ? $getdata1['member_id'] : '';
        $member_name = isset($getdata1['name']) ? $getdata1['name'] : '';
        $plan_id = isset($getdata1['plan_id']) ? $getdata1['plan_id'] : '';
        $group_id = isset($getdata1['group_id']) ? $getdata1['group_id'] : '';

    	$data1 = array(
			'emi_status'  => "cancelled",
			'added_date' => date('Y-m-d h:i:s')
		);
		$this->db->where('member_id',$member_id)->where('plan_id',$plan_id)->where('group_id',$group_id)->where('emi_status','due')->update('tbl_emi',$data1);
        $getdata = $this->db->where('slot_number',$slot_number)->where('order_id',$order_id)->get('tbl_orders')->row_array();
        $planDetails = $this->db->where('plan_id',$getdata['plan_id'])->get('tbl_plans')->row_array();
        $data = array(
    	    'member_id' => '',
    	    'plan_id'   => isset($getdata['plan_id']) ? $getdata['plan_id'] : '',
    	    'group_id'  => isset($getdata['group_id']) ? $getdata['group_id'] : '',
    	    'member_name' => '',
    	    'plan_amount' => isset($getdata['plan_amount']) ? $getdata['plan_amount'] : '',
    	    'start_month' => isset($getdata['start_month']) ? $getdata['start_month'] : '',
    	    'emi' => isset($getdata['emi']) ? $getdata['emi'] : '',
    	    'tenure' => isset($getdata['tenure']) ? $getdata['tenure'] : '',
    	    'months_completed' => isset($planDetails['months_completed']) ? $planDetails['months_completed'] : '',
    	    'agent_commission' => isset($getdata['agent_commission'])  ? $getdata['agent_commission'] : '',
    	    'end_month' => isset($getdata['end_month']) ? $getdata['end_month'] : '',
    	    'total_months' => isset($getdata['total_months']) ? $getdata['total_months'] : '',
    	    'groups_count'  => isset($planDetails['groups_counts']) ? $planDetails['groups_counts'] : '',
    	    'admission_fees'    => isset($getdata['admission_fees']) ? $getdata['admission_fees'] : '',
    	    'agent_id'  => '0',
    	    'is_added_by_agent' => '0',
    	    'transaction_id' => '',
    	    'payment_mode' => 'offline',
    	    'slot_number' => isset($getdata['slot_number']) ? $getdata['slot_number'] : '',
    	    'added_date' => date('Y-m-d h:i:s')
        );
        $this->db->insert('tbl_orders',$data);
        $insert_id = $this->db->insert_id();

		$slot_number = $getdata['slot_number'];
        $member_data = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
		$plan_data = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
        if(!empty($check_insert_id['insert_id'])){
                $insert_id =$check_insert_id['insert_id'] + 1;
            }else{
                $insert_id = 1;
            }
            $ledgerdata1 = array(
                    'insert_id'=> $insert_id,
                    'c_code' => '400',
                    'category_desc' => 'Subscription',
                    'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
                    'transaction_mode' => 'J1 - Internal',
                    'transaction_type' => 'Subscription Cancellation',
                    'transaction_description' => '',
                    'amount' => isset($plan_data['emi']) ? $plan_data['emi'] : '',
                    'dr_cr' =>'Dr',
                    'sub_id' => isset($member_data['subscriber_id']) ? $member_data['subscriber_id'] : '',
                    'account_name' => isset($member_data['name']) ? $member_data['name'] : '',
                    'added_date' => date('Y-m-d h:i:s'),
                    'account_description' => $this->getGlAccount('1003'),
                    'gl_account' => '1003',
                    'type' => 'Payment',
					'user' => 'Senthil',
					'slot_number' => $slot_number,
					'reversal' => 'yes',
                );
                 $insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
                 $insert_id = $this->db->insert_id();
                 $selest_ensert_id = $this->db->where('general_ledger_master_id',$insert_id)->get('tbl_general_ledger_master')->row_array();
                  $ledgerdata2 = array(
                    'insert_id'=> $selest_ensert_id['insert_id'],
                    'c_code' => '400',
                    'category_desc' => 'Subscription',
                    'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
                    'transaction_mode' => 'J1 - Internal',
                    'transaction_type' => 'Subscription Cancellation',
                    'transaction_description' => '',
                    'amount' => isset($plan_data['emi']) ? $plan_data['emi'] : '',
                    'dr_cr' =>'Cr',
                    'sub_id' => isset($member_data['subscriber_id']) ? $member_data['subscriber_id'] : '',
                    'account_name' => isset($member_data['name']) ? $member_data['name'] : '',
                    'added_date' => date('Y-m-d h:i:s'),
                    'account_description' => $this->getGlAccount('1002'),
                    'gl_account' => '1002',
                    'type' => 'Payment',
					'user' => 'Senthil',
					'slot_number' => $slot_number,
					'reversal' => 'yes',
                );
                $this->db->insert('tbl_general_ledger_master',$ledgerdata2);
        if($insert_id != ''){
            $output = array(
               'status' => Success,
               'message' => 'Cancel Subscription',
               'data' => []
            );
        }else{
           $output = array(
               'status' => Failure,
               'message' => 'Something Wrong',
               'data' => []
            ); 
        }
        
	  }else{
	    $output = array(
	      'status' => Failure,
	      'message' => "Data not found",
	      'data' => []
	    ); 
	  }
	  echo json_encode($output); die;
	}
	
	public function resignSlotSubcription(){
	  $data =  json_decode($this->data);
      $slot_number = isset($data->slot_number) ? $data->slot_number : '';
      $member_id = isset($data->member_id) ? $data->member_id : '';
      $order_id = isset($data->order_id) ? $data->order_id : '';
     if($slot_number != '' && $member_id != ''){
      $member_details = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
      $data = array(
        'member_id' => isset($member_id) ? $member_id : '',
        'member_name' => isset($member_details['name']) ? $member_details['name'] : '',
        'slot_status' => 'assigned'
      );
      $this->db->where('slot_number',$slot_number)->where('slot_status','vacant')->where('order_id',$order_id)->update('tbl_orders',$data);
      $getorder =  $this->db->where('order_id',$order_id)->get('tbl_orders')->row_array();
      $start_month = isset($getorder['start_month']) ? $getorder['start_month'] : '';
      $emi = isset($getorder['emi']) ? $getorder['emi'] : '';
	  $months = explode(" ",$getorder['tenure']);
	  $getmonths = isset($months[0]) ? $months[0] : ''; 
	 
	  $date2 = date('m', strtotime($start_month));
	  $date2 = 0;
	  $emi = round($emi, 0);
	  for($i=1; $i<=$getmonths; $i++){
		 // one column add in emi table plan name (pending)
		$date3 = date('M,Y', strtotime($start_month. ' + '.$date2.'month'));
		$date2  = $date2 + 1; 
		$data1 = array(
		'member_id' => isset($getorder['member_id']) ? $getorder['member_id'] : '',
		'plan_id'   => isset($getorder['plan_id']) ? $getorder['plan_id'] : '',
		'group_id'  => isset($getorder['group_id']) ? $getorder['group_id'] : '',
		'emi_month' => $date3,
		'plan_emi' => isset($emi) ? $emi : '',
		'emi_no' => $i,
		'total_emi' => isset($getorder['total_months']) ? $getorder['total_months'] : '',
		'emi_status'  => "due",
		'slot_number' => isset($slot_number) ? $slot_number : '',
		'is_partial_payment' => "No",
		'is_chit_taken' => "no",
		'chit_status' => 'close',
		'added_date' => date('Y-m-d h:i:s')
	   );
	   $this->db->insert('tbl_emi',$data1);
	   $insert_id1 = $this->db->insert_id();
	   if(!empty($insert_id1)){
			$output = array(
				'status' => Success,
				'message' => 'Created Emi Successfully',
				'data' => [],
			);	 
	   }else{
		  $output = array(
				'status' => Failure,
				'message' => "Invalid Data.",
				'data' => []
			); 
		}
	 }

	 $member_data = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
	 $plan_data = $this->db->where('plan_id',$getorder['plan_id'])->get('tbl_plans')->row_array();
            $ledgerdata1 = array(
                    'insert_id'=> isset($insert_id) ? $insert_id :'1',
                    'c_code' => '400',
                    'category_desc' => 'Subscription',
                    'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
                    'transaction_mode' => 'J1 - Internal',
                    'transaction_type' => 'Subscription Due',
                    'transaction_description' => 'Subscribers A/c',
                    'amount' => isset($plan_data['emi']) ? $plan_data['emi'] : '',
                    'dr_cr' =>'Dr',
                    'sub_id' => isset($member_data['subscriber_id']) ? $member_data['subscriber_id'] : '',
                    'account_name' => isset($member_data['name']) ? $member_data['name'] : '',
                    'added_date' => date('Y-m-d h:i:s'),
                    'account_description' => $this->getGlAccount('1002'),
                    'gl_account' => '1002',
                    'type' => 'Payment',
					'user' =>'Senthil',
					'slot_number' => $slot_number,
                );
                 $insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
                 $insert_id = $this->db->insert_id();
                 $selest_ensert_id = $this->db->where('general_ledger_master_id',$insert_id)->get('tbl_general_ledger_master')->row_array();
                  $ledgerdata2 = array(
                    'insert_id'=> $selest_ensert_id['insert_id'],
                    'c_code' => '400',
                    'category_desc' => 'Subscription',
                    'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
                    'transaction_mode' => 'J1 - Internal',
                    'transaction_type' => 'Subscription Due',
                    'transaction_description' => 'Plan A/c',
                    'amount' => isset($plan_data['emi']) ? $plan_data['emi'] : '',
                    'dr_cr' =>'Cr',
                    'sub_id' => isset($member_data['subscriber_id']) ? $member_data['subscriber_id'] : '',
                    'account_name' => isset($member_data['name']) ? $member_data['name'] : '',
                    'added_date' => date('Y-m-d h:i:s'),
                    'account_description' => $this->getGlAccount('1003'),
                    'gl_account' => '1003',
                    'type' => 'Payment',
					'user' =>'Senthil',
					'slot_number' => $slot_number,

                );
                $this->db->insert('tbl_general_ledger_master',$ledgerdata2);
		$output = array(
		'status' => Success,
		'message' => 'Insert Order table  Subscription',
		'data' => []
		);
		
		}else{
			$output = array(
			'status' => Failure,
			'message' => "Slot number and Member Id are blanked",
			'data' => []
			); 
		}
		echo json_encode($output); die;
	}
	

   public function getControlSheetwithfilter(){
        $data =  json_decode($this->data);
        $month = isset($data->mnth_filter) ? $data->mnth_filter : '';
        $year = isset($data->year) ? $data->year : '';
        $month_filter = $month.",".$year;
        
        $getOrder = $this->db->select('member_id')->where('member_id !=',0)->group_by('member_id')->get('tbl_orders')->result_array();
        $member_ids = array();
        foreach($getOrder as $keys=>$values){
            $member_ids[] = $values['member_id'];
        }
        $member_unique =  array_unique($member_ids);
        $data = array(); 
        foreach($member_unique as $keys=>$values){
            $member_id = $values;
            $member_detail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
            $trenscation_detail = $this->db->order_by('transaction_id','desc')->where('transaction_month',$month_filter)->where('subscriber_id',$member_id)->get('tbl_transactions')->row_array();
            if(!empty($trenscation_detail)){
                $opening_balance = $trenscation_detail['opening_balance'];
                $emi_detail = $this->db->where('member_id',$member_id)->where('emi_month',$month_filter)->get('tbl_emi')->result_array();
                $sum_of_due = 0;
                $sum_of_paid = 0;
                $sum_of_divident = 0;
                foreach($emi_detail as $key=>$val){
                   if($val['emi_status'] == 'paid'){
                       $sum_of_paid += $val['plan_emi'];
                   }elseif($val['emi_status'] == 'due'){
                      if($val['is_partial_payment'] == 'Yes'){
                          $sum_of_due += $val['amount_due'];
                      }else{
                          $sum_of_due += $val['plan_emi'];
                      }
                   } elseif(!empty($val['divident'])){
                       $sum_of_divident += $val['divident'];
                   }
                }
                $chit_detail = $this->db->where('chit_month',$month_filter)->where('member_id',$member_id)->get('tbl_chits')->row_array();
                if(!empty($chit_detail)){
                    $chit_amount = $chit_detail['chit_amount'];
                }else{
                    $chit_amount = 0;
                }
                
                $getTransaction = $this->db->where('subscriber_id',$member_id)->where('transaction_month',$month_filter)->get('tbl_transactions')->result_array();
                $paid_amount = 0;
                foreach($getTransaction as $key => $value){
                     $paid_amount = $paid_amount + $value['transaction_amount'];
                }
                
                 $getCurrentMonthsAmount = $this->db->where('emi_status','due')->where('member_id',$member_id)->where('emi_month',$month_filter)->get('tbl_emi')->row_array();
                 $plan_emi = isset($getCurrentMonthsAmount['plan_emi']) ? $getCurrentMonthsAmount['plan_emi'] : '0';
                 $net_amount = (($sum_of_due)+($plan_emi) - ($chit_amount)+('0'));
                 $Balance_amount = $net_amount - $sum_of_paid;
        
                $data[] = array(
                    'Member_name' => $member_detail['name'],
                    'Total_emi_due' => isset($sum_of_due) ? $sum_of_due : '0',
                    'Total_emi_paid' => isset($sum_of_paid) ? $sum_of_paid : '0',
                    'Balance' => $sum_of_paid - $sum_of_due,
                    'Member_mobile' =>  $member_detail['mobile'],
                    'Opening_balance' => isset($opening_balance) ? $opening_balance : '0',
                    'Current_month_due' => isset($sum_of_due) ? $sum_of_due : '0',
                    'Paid_amount' => isset($paid_amount) ?  $paid_amount : '0',
                    'Chit_taken' => isset($chit_amount) ? $chit_amount : '0',
                    'Net_Amount' => isset($net_amount) ? $net_amount : '0',
                    'Balance_amount' => isset($Balance_amount) ? $Balance_amount : '0',
                    'Share_of_discount' => isset($sum_of_divident) ? $sum_of_divident :'0',
                    'Net_curr_moth_due' => $sum_of_due - $sum_of_divident,
                    );
            }else{
                 $transcation_detail = $this->db->order_by('transaction_id','desc')->where('subscriber_id',$member_id)->get('tbl_transactions')->row_array();
                 if(!empty($transcation_detail)){
                     $opening_balance = $transcation_detail['current_balance'];
                     $last_month = $transcation_detail['transaction_month'];
                     $newDate = date("m", strtotime($last_month));
                     $newDate2 = date("m", strtotime($month_filter));
                     $start_date = (int)$newDate + 1;
                     $end_date = (int)$newDate2;
                     $emi_details = array();
                     for( $i = $start_date ; $i<= $end_date ; $i++ ){
                         $dateObj   = DateTime::createFromFormat('!m', $i);
                         $monthName = $dateObj->format('M');
                         $month_filter2 = $monthName.",".$year;
                         $emi_details[] = $this->db->where('member_id',$member_id)->where('emi_month',$month_filter2)->get('tbl_emi')->result_array();
                     }
                      $sum_of_due = 0;
                      $sum_of_paid = 0;
                      $sum_of_divident = 0;
                     foreach($emi_details as $ky=>$vy){
                             foreach($vy as $k=>$v){
                                 if($v['emi_status'] == 'paid'){
                                       $sum_of_paid += $v['plan_emi'];
                                   }elseif($v['emi_status'] == 'due'){
                                      if($v['is_partial_payment'] == 'Yes'){
                                          $sum_of_due += $v['amount_due'];
                                      }else{
                                          $sum_of_due += $v['plan_emi'];
                                      }
                                   } elseif(!empty($v['divident'])){
                                       $sum_of_divident += $v['divident'];
                                   }
                             }
                         }
                         $getTransaction = $this->db->where('subscriber_id',$member_id)->where('transaction_month',$month_filter)->get('tbl_transactions')->result_array();
                        $paid_amount = 0;
                        foreach($getTransaction as $key => $value){
                             $paid_amount = $paid_amount + $value['transaction_amount'];
                        }
                    $op = $opening_balance + $sum_of_due;
                     $net_amount = (($sum_of_due)+($plan_emi) - ($chit_amount)+('0'));
                      $Balance_amount = $net_amount - $sum_of_paid;
                     $data[] = array(
                    'Member_name' => $member_detail['name'],
                    'Total_emi_due' => isset($sum_of_due) ? $sum_of_due : '0',
                    'Total_emi_paid' => isset($sum_of_paid) ? $sum_of_paid : '0',
                    'Balance' => $sum_of_paid - $sum_of_due,
                    'Member_mobile' =>  $member_detail['mobile'],
                    'Opening_balance' => isset($op) ? $op : '0',
                    'Current_month_due' => isset($sum_of_due) ? $sum_of_due : '0',
                    'Paid_amount' => isset($paid_amount) ?  $paid_amount : '0',
                    'Chit_taken' => isset($chit_amount) ? $chit_amount : '0',
                    'Net_Amount' => isset($net_amount) ? $net_amount : '0',
                    'Balance_amount' => isset($Balance_amount) ? $Balance_amount : '0',
                    'Share_of_discount' => isset($sum_of_divident) ? $sum_of_divident :'0',
                    'Net_curr_moth_due' => $sum_of_due - $sum_of_divident,
                    );

                 }else{
                      $newDate2 = date("m", strtotime($month_filter));
                       $end_date = (int)$newDate2;
                       $emi_details = array();
                         for( $i = 1 ; $i<= $end_date ; $i++ ){
                             $dateObj   = DateTime::createFromFormat('!m', $i);
                             $monthName = $dateObj->format('M');
                             $month_filter2 = $monthName.",".$year;
                             $emi_details[] = $this->db->where('member_id',$member_id)->where('emi_month',$month_filter2)->get('tbl_emi')->result_array();
                         }
                          $sum_of_due = 0;
                          $sum_of_paid = 0;
                          $sum_of_divident = 0;
                         foreach($emi_details as $ky=>$vy){
                                 foreach($vy as $k=>$v){
                                     if($v['emi_status'] == 'paid'){
                                           $sum_of_paid += $v['plan_emi'];
                                       }elseif($v['emi_status'] == 'due'){
                                          if($v['is_partial_payment'] == 'Yes'){
                                              $sum_of_due += $v['amount_due'];
                                          }else{
                                              $sum_of_due += $v['plan_emi'];
                                          }
                                       } elseif(!empty($v['divident'])){
                                           $sum_of_divident += $v['divident'];
                                       }
                                 }
                        }
                        $getTransaction = $this->db->where('subscriber_id',$member_id)->where('transaction_month',$month_filter)->get('tbl_transactions')->result_array();
                        $paid_amount = 0;
                        foreach($getTransaction as $key => $value){
                             $paid_amount = $paid_amount + $value['transaction_amount'];
                        }
                         $net_amount = (($sum_of_due)+($plan_emi) - ($chit_amount)+('0'));
                         $Balance_amount = $net_amount - $sum_of_paid;
                         $data[] = array(
                            'Member_name' => $member_detail['name'],
                            'Total_emi_due' => isset($sum_of_due) ? $sum_of_due : '0',
                            'Total_emi_paid' => isset($sum_of_paid) ? $sum_of_paid : '0',
                            'Balance' => $sum_of_paid - $sum_of_due,
                            'Member_mobile' =>  $member_detail['mobile'],
                            'Opening_balance' => isset($sum_of_due) ? $sum_of_due : '0',
                            'Current_month_due' => isset($sum_of_due) ? $sum_of_due : '0',
                            'Paid_amount' => isset($paid_amount) ?  $paid_amount : '0',
                            'Chit_taken' => isset($chit_amount) ? $chit_amount : '0',
                            'Net_Amount' => isset($net_amount) ? $net_amount : '0',
                            'Balance_amount' => isset($Balance_amount) ? $Balance_amount : '0',
                            'Share_of_discount' => isset($sum_of_divident) ? $sum_of_divident :'0',
                            'Net_curr_moth_due' => $sum_of_due - $sum_of_divident,
                        );
                 }
            }
        }
        if(!empty($data)){
        $output = array(
          'status' => Success,
          'message' => 'Control Sheet Fetched Successfully',
          'data' => $data
        );
        }else{
            $output = array(
    	      'status' => Failure,
    	      'message' => "Data Not Found",
    	      'data' => []
    	    );  
        }
        echo json_encode($output); die;
   }
   
   public function getControlSheet(){ // get control report
        $data =  json_decode($this->data);
        $month = isset($data->mnth_filter) ? $data->mnth_filter : '';
        $year = isset($data->year) ? $data->year : '';
        $month_filter = $month.",".$year;
        
        $getOrder = $this->db->select('member_id')->where('member_id !=',0)->group_by('member_id')->get('tbl_orders')->result_array();
        $member_ids = array();
        foreach($getOrder as $keys=>$values){
            $member_ids[] = $values['member_id'];
        }
        $member_unique =  array_unique($member_ids);
        $data = array();
        foreach($member_unique as $keys=>$values){
            $member_id = $values;
            $member_detail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
             $newDate2 = date("m", strtotime($month_filter));
             $end_date = (int)$newDate2 - 1;
             $emi_details = array();
                 for( $i = 1 ; $i<= $end_date ; $i++ ){
                             $dateObj   = DateTime::createFromFormat('!m', $i);
                             $monthName = $dateObj->format('M');
                             $month_filter2 = $monthName.",".$year;
                             $emi_details[] = $this->db->where('member_id',$member_id)->where('emi_month',$month_filter2)->get('tbl_emi')->result_array();
                 }
                  $sum_of_due = 0;
                  $sum_of_paid = 0;
                  $sum_of_divident = 0;
                 foreach($emi_details as $ky=>$vy){
                         foreach($vy as $k=>$v){
                             if($v['emi_status'] == 'paid'){
                                   $sum_of_paid += $v['plan_emi'];
                               }elseif($v['emi_status'] == 'due'){
                                  if($v['is_partial_payment'] == 'Yes'){
                                      $sum_of_due += $v['amount_due'];
                                  }else{
                                      if(!empty($v['divident'])){
                                          $sum_of_due += $v['plan_emi'] - $v['divident'];
                                      }else{
                                        $sum_of_due += $v['plan_emi'];
                                      }
                                  }
                               } elseif(!empty($v['divident'])){
                                   $sum_of_divident += $v['divident'];
                               }
                         }
                }
                $current_month_due = $this->db->where('member_id',$member_id)->where('emi_month',$month_filter)->get('tbl_emi')->result_array();
                
                $sum_current_month_due = 0;
                $current_month_divident = 0;
                $current_emies = 0;
                $emi_no_check = array();
                // print_r($current_month_due);die;
                foreach($current_month_due as $keys=>$values){
                      if($values['emi_status'] == 'due'){
                          if($values['is_partial_payment'] == 'Yes'){
                              $sum_current_month_due += $values['amount_due'];
                          }else{
                              $sum_current_month_due += $values['plan_emi'];
                          }
                          }if(!empty($values['divident'])){
                            $current_month_divident += $values['divident'];
                        }else{
                            $current_month_divident += 0;
                        }
                       $current_emies +=  $values['plan_emi'];
                       $emi_no_check[] = array(
                           'plan_id' => isset($values['plan_id']) ? $values['plan_id'] : '',
                           'group_id' => isset($values['group_id']) ? $values['group_id'] : '',
                           'emi_no' => isset($values['emi_no']) ? $values['emi_no'] : '',
                           );
                 }
                //  print_r($current_month_divident);die;
                 $input = array_map("unserialize", array_unique(array_map("serialize", $emi_no_check)));
                 $chit_amount =0;
                 foreach($input as $keys=>$values){
                     $plan_id = $values['plan_id'];
                     $group_id = $values['group_id'];
                     $emi_no = $values['emi_no'];
                     $auction_detail = $this->db->select('auction_id')->where('plan_id',$plan_id)->where('group_id',$group_id)->where('auction_no',$emi_no)->get('tbl_auction')->row_array();
                     if(!empty($auction_detail)){
                          $auctrion_id = $auction_detail['auction_id'];
                          $chit_detail = $this->db->select('added_date,chit_amount')->where('auction_id',$auctrion_id)->where('plan_id',$plan_id)->where('group_id',$group_id)->where('member_id',$member_id)->get('tbl_chits')->row_array();
                          if(!empty($chit_detail['chit_amount'])){
                              $chit_amount += $chit_detail['chit_amount'];
                          }else{
                              $chit_amount += 0;
                          }
                     }
                 }
                $paid_detail = $this->db->where('subscriber_id',$member_id)->where('transaction_month',$month_filter)->where('type','receipt')->get('tbl_transactions')->result_array();
                $paid_amount = 0;
                if(!empty($paid_detail)){
                    foreach($paid_detail as $keys=>$values){
                        $paid_amount += $values['transaction_amount'];
                    }
                }
                
                $Net_curr_moth_due = $current_emies - $current_month_divident;
                $net_amount = $sum_of_due + $Net_curr_moth_due - $chit_amount;
                $balance_amount = $net_amount - $paid_amount;
                 $data[] = array(
                    'Member_name' => $member_detail['name'],//
                    'Total_emi_due' => isset($current_emies) ? $current_emies : '0',//
                    'Total_emi_paid' => isset($sum_of_paid) ? $sum_of_paid : '0',//
                    // 'Balance' => $sum_of_paid - $sum_of_due,
                    'Balance' => isset($sum_of_due) ? $sum_of_due : '0', // 
                    'Member_mobile' =>  $member_detail['mobile'],//
                    'Opening_balance' => isset($sum_of_due) ? $sum_of_due : '0', // 
                    'Current_month_due' => isset($current_emies) ? $current_emies : '0', //
                    'Paid_amount' => isset($paid_amount) ?  $paid_amount : '0',//
                    'Chit_taken' => isset($chit_amount) ? $chit_amount : '0', // 
                    'Net_Amount' => isset($net_amount) ? $net_amount : '0',
                    'Balance_amount' => isset($balance_amount) ? $balance_amount : '0',
                    'Share_of_discount' => isset($current_month_divident) ? $current_month_divident :'0',//
                    'Net_curr_moth_due' => $Net_curr_moth_due,//
                );
        }
        if(!empty($data)){
            $output = array(
          'status' => Success,
          'message' => 'Control Sheet Fetched Successfully',
          'data' => $data
        );
        }else{
            $output = array(
    	      'status' => Failure,
    	      'message' => "Data Not Found",
    	      'data' => []
    	    );  
        }
        echo json_encode($output); die;
        
   }
   
   public function addCollateral(){
    $data =  json_decode($this->data);
    $data1 = array(
       'name' => isset($data->collateral_name) ? $data->collateral_name : '',
       'description' => isset($data->description) ? $data->description : '',
       'parent_id' => isset($data->parent_id) ? $data->parent_id : '',
       'added_date' => date('y-m-d h:i:s')
    );
    $this->db->insert('tbl_collateral_master',$data1);
    $insert_id1 = $this->db->insert_id();
   
    if($insert_id1 != ''){
      $output = array(
       'status' => Success,
       'message' => 'Add Collateral Successfully',
       'data' => []
      );
    }else{
      $output = array(
	      'status' => Failure,
	      'message' => "Add Collateral Unsuccessfully",
	      'data' => []
	  );    
    }
    echo json_encode($output); die;
   }
   
    public function listCollateral(){
      $getCollateral =  $this->db->get('tbl_collateral_master')->result_array();
      if(!empty($getCollateral)){
          $output = array(
           'status' => Success,
           'message' => 'GET Collateral Fetched Successfully',
           'data' => $getCollateral
          );
      }else{
         $output = array(
	      'status' => Failure,
	      'message' => "GET Collateral Unsuccessfully",
	      'data' => []
	     );     
      }
      echo json_encode($output); die;
    }
    
    public function addSubscriberCollateral(){
      $data =  json_decode($this->data);
      $collateral_id = isset($data->collateral_id) ? $data->collateral_id : '';
      $sub_collateral_id = isset($data->sub_collateral_id) ? $data->sub_collateral_id : '';
      $selectSubscription = isset($data->selectSubscription) ? implode(',',$data->selectSubscription) : '';
      $image = isset($data->image) ? $data->image : '';
      $amount = isset($data->amount) ? $data->amount : '';
      $exemption_amount = isset($data->exemption_amount) ? $data->exemption_amount : '';
      $exemption_reason = isset($data->exemption_reason) ? $data->exemption_reason : '';
      $member_id = isset($data->member_id) ? $data->member_id : '';
      $member_name = $this->db->select('name')->where('member_id',$member_id)->get('tbl_members')->row_array();
      $collateral_name = $this->db->select('name')->where('collateral_id',$collateral_id)->get('tbl_collateral_master')->row_array();
      $sub_collateral_name = $this->db->select('name')->where('parent_id',$collateral_id)->get('tbl_collateral_master')->row_array();
      $data = array(
        'collateral_id' => isset($collateral_id) ? $collateral_id : '',
        'collateral_sub_type_id' => isset($sub_collateral_id) ? $sub_collateral_id : '',
        'member_id' => isset($member_id) ? $member_id : '',
        'member_name' => isset($member_name['name']) ? $member_name['name'] : '',
        'collateral_name' => isset($collateral_name['name']) ? $collateral_name['name'] : '',
        'collateral_sub_name' => isset($sub_collateral_name['name']) ? $sub_collateral_name['name'] : '',
        'subscription_locked' => isset($selectSubscription) ? $selectSubscription : '',
        'image' => $image,
        'estimated_amount' => $amount,
        'exemption_amount' => $exemption_amount,
        'exemption_reason' => $exemption_reason,
        'added_date'=>date('Y-m-d h:i:s')
      );
      $check_exist = $this->db->where('member_id',$member_id)->get('tbl_subscriber_collateral')->num_rows();
      if($check_exist > 0){
        $this->db->where('member_id',$member_id)->update('tbl_subscriber_collateral',$data);
      }else{
         $this->db->insert('tbl_subscriber_collateral',$data);  
         $insert_id = $this->db->insert_id();
      }
      if($member_id != ''){
          $output = array(
           'status' => Success,
           'message' => 'Add Subscriber Collateral Successfully',
           'data' => []
          );
      }else{
         $output = array(
	      'status' => Failure,
	      'message' => "Add Subscriber Collateral Unsuccessfully",
	      'data' => []
	     );     
      }
      echo json_encode($output); die;
    }
    
    public function reports(){
     $data =  json_decode($this->data); 
     $member_id = isset($data->member_id) ? $data->member_id : '';
     if($member_id != ''){
		$order_detail = $this->db->where('member_id',$member_id)->get('tbl_orders')->result_array();
		$member_all_detail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
		$member_detail = array(
		    'name'=>$member_all_detail['name'],
		    'mobile'=>$member_all_detail['mobile'],
		    'dob'=>$member_all_detail['dob']
		    );
		$all_data = array();
		$total_plan_order = array();
		foreach($order_detail as $keys=>$values){
			$total_plan_order[] = $values['plan_id'];
		}

		$total_plan = array_unique($total_plan_order);
		foreach($total_plan as $keys=>$plan_id){
			$bid_detail_amount = array();
			$divident_amount = array();
			$plan_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
			$bid_detail = $this->db->where('member_id',$member_id)->where('plan_id',$plan_id)->get('tbl_bids')->result_array();
			foreach($bid_detail as $keys=> $values){
				$bid_detail_amount[] = $values['bid_amount'];
				$emi_values = implode(',',$bid_detail_amount);		
				$bid_detail_amount = array(
					'bid_amount'=>$emi_values
				);		
			}			
			$chit_all_detail = $this->db->where('member_id',$member_id)->where('plan_id',$plan_id)->get('tbl_chits')->row_array();
			if(!empty($chit_all_detail)){
			    $auction_id = $chit_all_detail['auction_id'];
			    $auction_detail = $this->db->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
			    $winning_bid_id = $auction_detail['winning_bid_id'];
			    $bid_detail_with_winning = $this->db->where('bid_id',$winning_bid_id)->get('tbl_bids')->row_array();
			    $bid_id_amount = $bid_detail_with_winning['bid_amount'];
			}
			$chit_detail = array(
			    'chit_amount' => isset($chit_all_detail['chit_amount']) ? $chit_all_detail['chit_amount'] : 'No' ,
			    'chit_added_date'=>isset($chit_all_detail['added_date']) ? $chit_all_detail['added_date'] :'No',
			    'winning_bid_amount'=>isset($bid_id_amount) ? $bid_id_amount :'No',
			    );
			
			$divident_detail = $this->db->where('member_id',$member_id)->where('plan_id',$plan_id)->get('tbl_divident')->result_array();
			foreach($divident_detail as $key=>$value){
				$divident_amount[] = $value['divident_amount'];
				$sum_of_divident = 0;
				foreach($divident_amount as $k=>$v){
					$sum_of_divident += $v; 
				}
				$divident_amount = array(
					'total_divident_amount'=>$sum_of_divident
				);
			}
			$delete_array = array();
			$chit_detail = isset($chit_detail) ? $chit_detail :$delete_array;
			$divident_amount = isset($divident_amount) ? $divident_amount :$delete_array;
			$bid_amount = isset($bid_amount) ? $bid_amount :$delete_array;
			$plan_detail = isset($plan_detail) ? $plan_detail :$delete_array;
			$all_data[] = array_merge($divident_amount,$bid_detail_amount,$chit_detail,$plan_detail);			
		}
		$all_detail_data = array(
		    'member_detail'=>isset($member_detail) ? $member_detail :'',
		    'plan_detail'=>isset($all_data) ? $all_data : ''
		    );

		$output = array(
		'status' => Success,
		'message' => 'Get Reports Fetched Successfully',
		'data' => $all_detail_data
		);
		}else{
			$output = array(
			'status' => Failure,
			'message' => "Invalid Member Id",
			'data' => []
			);  
		}
		echo json_encode($output); die;
    }
    
    public function reportsWithfilter(){ // subscriber summary
        $data =  json_decode($this->data); 
        $member_id = isset($data->member_id) ? $data->member_id : '';
        $mnth_filter = isset($data->mnth_filter) ? $data->mnth_filter : '';
        $year = isset($data->year) ? $data->year : '';
        $InputArray = array($mnth_filter,$year);
        $month_year =    implode(",",$InputArray);
        $all_emi = $this->db->where('member_id',$member_id)->where('emi_month',$month_year)->get('tbl_emi')->result_array();
        
        $newDate2 = date("m", strtotime($mnth_filter));
             $end_date1 = (int)$newDate2 - 1;
             $emi_details = array();
                 for( $i = 1 ; $i<= $end_date1 ; $i++ ){
                             $dateObj   = DateTime::createFromFormat('!m', $i);
                             $monthName = $dateObj->format('M');
                             $month_filter3 = $monthName.",".$year;
                             $emi_details[] = $this->db->where('member_id',$member_id)->where('emi_month',$month_filter3)->get('tbl_emi')->result_array();
                 }
                  $opening_balance = 0;
                 foreach($emi_details as $ky=>$vy){
                         foreach($vy as $k=>$v){
                              if($v['emi_status'] == 'due'){
                                  if($v['is_partial_payment'] == 'Yes'){
                                      $opening_balance += $v['amount_due'];
                                  }else{
                                      $opening_balance += $v['plan_emi'];
                                  }
                              } 
                         }
                }

        if(!empty($all_emi)){
            $plan_all_detail = array();
            foreach($all_emi as $keys=>$values){
                $member_id = isset($values['member_id']) ? $values['member_id'] : '';
                $plan_id = isset($values['plan_id']) ? $values['plan_id'] : '';
                $group_id = isset($values['group_id']) ? $values['group_id'] : '';
                $plan_emi = isset($values['plan_emi']) ? $values['plan_emi'] : '';
                $emi_no = isset($values['emi_no']) ? $values['emi_no'] : '';
                $emi_div =  isset($values['divident']) ? $values['divident'] :'0';
                $slot_number = $values['slot_number'];
                $total_months = $values['total_emi'];
                $plan_detail = $this->db->where('plan_id',$plan_id)->select('plan_name,remaining_month,end_date_for_subscription,plan_amount,months_completed,start_month')->get('tbl_plans')->row_array();
                // $auction_detail = $this->db->select('auction_id')->where('plan_id',$plan_id)->where('auction_no',$emi_no)->get('tbl_auction')->row_array();
                $Mont_Auction = date("F-Y", strtotime($month_year));  
                $auction_detail = $this->db->select('auction_id')->where('plan_id',$plan_id)->where('month',$Mont_Auction)->get('tbl_auction')->row_array();
                if(!empty($auction_detail)){
                    $auctrion_id = $auction_detail['auction_id'];
                    $bid_detail = $this->db->select('forgo_amount')->where('slot_number',$slot_number)->where('auction_id',$auctrion_id)->where('plan_id',$plan_id)->where('member_id',$member_id)->get('tbl_bids')->result_array();
                    $all_bid_amount = array();
                    foreach($bid_detail as $keys=>$values){
                        $all_bid_amount[] = $values['forgo_amount'];
                    }
                    $bid_amount = implode(",",$all_bid_amount);
                    $chit_detail = $this->db->select('added_date,')->where('slot_number',$slot_number)->where('auction_id',$auctrion_id)->where('plan_id',$plan_id)->where('member_id',$member_id)->get('tbl_chits')->row_array();
                    $chit_detail_date =  isset($chit_detail['added_date']) ? $chit_detail['added_date'] :'No';
                    $winnning_bid_id = $this->db->select('forgo_amount')->where('is_bid_accepted','yes')->where('slot_number',$slot_number)->where('auction_id',$auctrion_id)->where('plan_id',$plan_id)->where('member_id',$member_id)->get('tbl_bids')->row_array();
                }
                $divident_amount = $this->db->select('divident_amount')->where('plan_id',$plan_id)->where('group_id',$group_id)->where('member_id',$member_id)->get('tbl_divident')->result_array();
                if(!empty($divident_amount)){
                $sum_of_divident1 = 0;
                foreach($divident_amount as $keys=>$values){
                    $sum_of_divident1 += $values['divident_amount'];
                }}
                $plan_all_detail[] = array(
                    'plan_name' => isset($plan_detail['plan_name']) ? $plan_detail['plan_name'] :'',
                    'plan_amount' => isset($plan_detail['plan_amount']) ? $plan_detail['plan_amount'] :'',
                    'divident' => isset($emi_div) ? $emi_div :'0',
                    'total_months' => isset($total_months) ? $total_months :'',
                    'emi' => isset($plan_emi) ? $plan_emi :'',
                    'remaining_month' => isset($plan_detail['remaining_month']) ? $plan_detail['remaining_month'] :'',
                    'start_month' => isset($plan_detail['start_month']) ? $plan_detail['start_month'] :'',
                    'months_completed' => isset($plan_detail['months_completed']) ? $plan_detail['months_completed'] :'',
                    'subscription_id' => isset($slot_number) ? $slot_number :'',
                    'discount' => isset($winnning_bid_id['forgo_amount']) ? $winnning_bid_id['forgo_amount'] :'',
                    'end_month' => isset($plan_detail['end_date_for_subscription']) ? $plan_detail['end_date_for_subscription'] :'',
                    'balance_discount' => isset($sum_of_divident1) ? $sum_of_divident1 :'',
                    'total_divident' => isset($sum_of_divident1) ? $sum_of_divident1 :'',
                    'chit_taken' => isset($chit_detail_date) ? $chit_detail_date :'No',
                    'winning_bid_amount' => isset($winnning_bid_id['forgo_amount']) ? $winnning_bid_id['forgo_amount'] :'No',
                    );
            }
            
                if(!empty($plan_all_detail)){
                    $sum_of_emi = 0; $sum_of_divident = 0;
                    foreach($plan_all_detail as $keys=>$values){
                            $sum_of_emi += isset($values['emi']) ? $values['emi'] :0;
                            if(is_numeric($values['divident'])){
                            $sum_of_divident += isset($values['divident']) ? $values['divident'] :0;
                            }else{
                              $sum_of_divident +=0; 
                            }
                    } 
                    
                    $sum_of_emi = isset($sum_of_emi) ? $sum_of_emi :'0';
                    $sum_of_divident = isset($sum_of_divident) ? $sum_of_divident :'0';
                    $GrossAmount = ($sum_of_emi - $sum_of_divident);
                    
                     $chit_detail_for_invoice = array();
                    foreach($plan_all_detail as $keys=>$values){
                        if($values['chit_taken']!='No'){
                            $chit_data = array(
                                'plan_name'=>$values['plan_name'],
                                'plan_amount'=>$values['plan_amount'],
                                'winning_bid_amount'=>$values['winning_bid_amount'],
                                );
                                $chit_detail_for_invoice[] = $chit_data;
                        }
                    }
                    
                    if(!empty($chit_detail_for_invoice)){
                        $sum_of_plan_amount = 0;
                        $sum_of_winning_bid_amount = 0;
                        foreach($chit_detail_for_invoice as $k=>$v){
                           $plnamt = isset($v['plan_amount'])?$v['plan_amount']:0;
                            $wngbidamt =  isset($v['winning_bid_amount'])?$v['winning_bid_amount']:0;
                             $sum_of_winning_bid_amount =+ $wngbidamt;
                              $sum_of_plan_amount =+ $plnamt;
                        }
                        $defference_of_plan_amount_and_bid = $sum_of_plan_amount - $sum_of_winning_bid_amount;
                    }
                    
                        $g_amount = isset($GrossAmount)?$GrossAmount:'0';
                         $pld = isset($defference_of_plan_amount_and_bid)?$defference_of_plan_amount_and_bid:'0';
                        $defference_of_ga_pld = $g_amount - $pld;
                        
                        $calculation = array(
                            'sum_of_emi' => isset($sum_of_emi) ? $sum_of_emi :'0',
                            'Sum_of_divident' =>isset($sum_of_divident) ? $sum_of_divident :'0',
                            'gross_amount' => isset($GrossAmount) ? $GrossAmount :'0',
                            'sum_of_plan_amount' => isset($sum_of_plan_amount) ? $sum_of_plan_amount :'0',
                            'sum_of_winning_bid_amount' =>isset($sum_of_winning_bid_amount) ? $sum_of_winning_bid_amount : '0',
                            'defference_of_plan_and_bid' =>isset($defference_of_plan_amount_and_bid) ? $defference_of_plan_amount_and_bid :'0',
                            'net_amount_payable' => $defference_of_ga_pld ? $defference_of_ga_pld :'',
                            );
                }  
                        
            
            
            $member_detail = $this->db->where('member_id',$member_id)->select('dob,mobile,last_name,name,member_id')->get('tbl_members')->row_array();
            $new_array12 =  array('opening_balance' => isset($opening_balance) ? $opening_balance :'');
            $new_data = array_merge($member_detail,$new_array12);
            
             $output = array(
    			'status' => Success,
    			'message' => "Fastch Successfully",
    			'data' => $plan_all_detail,
    			'member_data' =>$new_data,
    			'calculation' => isset($calculation) ? $calculation : '[]',
    			'chit_details' => isset($chit_detail_for_invoice) ? $chit_detail_for_invoice :'[]',
    		); 
        }
        else{
             $member_detail = $this->db->where('member_id',$member_id)->select('dob,mobile,last_name,name,member_id')->get('tbl_members')->row_array();
            $output = array(
			'status' => Failure,
			'message' => "No Emi ",
			'data' => [],
			'member_data' =>$member_detail,
			'calculation' => [],
    		'chit_details' => [],
			);  
        }
        echo json_encode($output); die;
    }
    public function invoice(){ // invoice summary
        $data =  json_decode($this->data); 
        $member_id = isset($data->member_id) ? $data->member_id : '';
        $mnth_filter = isset($data->mnth_filter) ? $data->mnth_filter : '';
        $year = isset($data->year) ? $data->year : '';
        $InputArray = array($mnth_filter,$year);
        $month_year =    implode(",",$InputArray);
        $all_emi = $this->db->where('member_id',$member_id)->where('emi_month',$month_year)->get('tbl_emi')->result_array();
        
        $newDate2 = date("m", strtotime($mnth_filter));
             $end_date1 = (int)$newDate2 - 1;
             $emi_details = array();
                 for( $i = 1 ; $i<= $end_date1 ; $i++ ){
                             $dateObj   = DateTime::createFromFormat('!m', $i);
                             $monthName = $dateObj->format('M');
                             $month_filter3 = $monthName.",".$year;
                             $emi_details[] = $this->db->where('member_id',$member_id)->where('emi_month',$month_filter3)->get('tbl_emi')->result_array();
                 }
                  $opening_balance = 0;
                 foreach($emi_details as $ky=>$vy){
                         foreach($vy as $k=>$v){
                              if($v['emi_status'] == 'due'){
                                  if($v['is_partial_payment'] == 'Yes'){
                                      $opening_balance += $v['amount_due'];
                                  }else{
                                      $opening_balance += $v['plan_emi'];
                                  }
                              } 
                         }
                }

        if(!empty($all_emi)){
            $plan_all_detail = array();
            foreach($all_emi as $keys=>$values){
                $member_id = isset($values['member_id']) ? $values['member_id'] : '';
                $plan_id = isset($values['plan_id']) ? $values['plan_id'] : '';
                $group_id = isset($values['group_id']) ? $values['group_id'] : '';
                $plan_emi = isset($values['plan_emi']) ? $values['plan_emi'] : '';
                $emi_no = isset($values['emi_no']) ? $values['emi_no'] : '';
                $emi_div =  isset($values['divident']) ? $values['divident'] :'0';
                $slot_number = $values['slot_number'];
                $total_months = $values['total_emi'];
                $plan_detail = $this->db->where('plan_id',$plan_id)->select('plan_name,remaining_month,end_date_for_subscription,plan_amount,months_completed,start_month')->get('tbl_plans')->row_array();
                $auction_detail = $this->db->select('auction_id')->where('plan_id',$plan_id)->where('group_id',$group_id)->order_by('auction_id', 'desc')->get('tbl_auction')->row_array();
                if(!empty($auction_detail)){
                    $auctrion_id = $auction_detail['auction_id'];
                    $bid_detail = $this->db->select('forgo_amount')->where('slot_number',$slot_number)->where('auction_id',$auctrion_id)->where('plan_id',$plan_id)->where('group_id',$group_id)->where('member_id',$member_id)->get('tbl_bids')->result_array();
                    $all_bid_amount = array();
                    foreach($bid_detail as $keys=>$values){
                        $all_bid_amount[] = $values['forgo_amount'];
                    }
                    $bid_amount = implode(",",$all_bid_amount);
                    $chit_detail = $this->db->select('added_date,')->where('slot_number',$slot_number)->where('auction_id',$auctrion_id)->where('plan_id',$plan_id)->where('group_id',$group_id)->where('member_id',$member_id)->get('tbl_chits')->row_array();
                    $chit_detail_date =  isset($chit_detail['added_date']) ? $chit_detail['added_date'] :'No';
                    $winnning_bid_id = $this->db->select('forgo_amount')->where('is_bid_accepted','yes')->where('slot_number',$slot_number)->where('auction_id',$auctrion_id)->where('plan_id',$plan_id)->where('group_id',$group_id)->get('tbl_bids')->row_array();
                }
                $divident_amount = $this->db->select('divident_amount')->where('plan_id',$plan_id)->where('group_id',$group_id)->where('member_id',$member_id)->get('tbl_divident')->result_array();
                if(!empty($divident_amount)){
                $sum_of_divident1 = 0;
                foreach($divident_amount as $keys=>$values){
                    $sum_of_divident1 += $values['divident_amount'];
                }}
                $plan_all_detail[] = array(
                    'plan_name' => isset($plan_detail['plan_name']) ? $plan_detail['plan_name'] :'',
                    'plan_amount' => isset($plan_detail['plan_amount']) ? $plan_detail['plan_amount'] :'',
                    'divident' => isset($emi_div) ? $emi_div :'0',
                    'total_months' => isset($total_months) ? $total_months :'',
                    'emi' => isset($plan_emi) ? $plan_emi :'',
                    'remaining_month' => isset($plan_detail['remaining_month']) ? $plan_detail['remaining_month'] :'',
                    'start_month' => isset($plan_detail['start_month']) ? $plan_detail['start_month'] :'',
                    'months_completed' => isset($plan_detail['months_completed']) ? $plan_detail['months_completed'] :'',
                    'subscription_id' => isset($slot_number) ? $slot_number :'',
                    'discount' => isset($winnning_bid_id['forgo_amount']) ? $winnning_bid_id['forgo_amount'] :'',
                    'end_month' => isset($plan_detail['end_date_for_subscription']) ? $plan_detail['end_date_for_subscription'] :'',
                    'balance_discount' => isset($sum_of_divident1) ? $sum_of_divident1 :'',
                    'total_divident' => isset($sum_of_divident1) ? $sum_of_divident1 :'',
                    'chit_taken' => isset($chit_detail_date) ? $chit_detail_date :'No',
                    'winning_bid_amount' => isset($winnning_bid_id['forgo_amount']) ? $winnning_bid_id['forgo_amount'] :'No',
                    );
            }
            
                if(!empty($plan_all_detail)){
                    $sum_of_emi = 0; $sum_of_divident = 0;
                    foreach($plan_all_detail as $keys=>$values){
                            $sum_of_emi += isset($values['emi']) ? $values['emi'] :0;
                            if(is_numeric($values['divident'])){
                            $sum_of_divident += isset($values['divident']) ? $values['divident'] :0;
                            }else{
                              $sum_of_divident +=0; 
                            }
                    } 
                    
                    $sum_of_emi = isset($sum_of_emi) ? $sum_of_emi :'0';
                    $sum_of_divident = isset($sum_of_divident) ? $sum_of_divident :'0';
                    $GrossAmount = ($sum_of_emi - $sum_of_divident);
                    
                     $chit_detail_for_invoice = array();
                    foreach($plan_all_detail as $keys=>$values){
                        if($values['chit_taken']!='No'){
                            $chit_data = array(
                                'plan_name'=>$values['plan_name'],
                                'plan_amount'=>$values['plan_amount'],
                                'winning_bid_amount'=>$values['winning_bid_amount'],
                                );
                                $chit_detail_for_invoice[] = $chit_data;
                        }
                    }
                    
                    if(!empty($chit_detail_for_invoice)){
                        $sum_of_plan_amount = 0;
                        $sum_of_winning_bid_amount = 0;
                        foreach($chit_detail_for_invoice as $k=>$v){
                           $plnamt = isset($v['plan_amount'])?$v['plan_amount']:0;
                            $wngbidamt =  isset($v['winning_bid_amount'])?$v['winning_bid_amount']:0;
                             $sum_of_winning_bid_amount =+ $wngbidamt;
                              $sum_of_plan_amount =+ $plnamt;
                        }
                        $defference_of_plan_amount_and_bid = $sum_of_plan_amount - $sum_of_winning_bid_amount;
                    }
                    
                        $g_amount = isset($GrossAmount)?$GrossAmount:'0';
                         $pld = isset($defference_of_plan_amount_and_bid)?$defference_of_plan_amount_and_bid:'0';
                        $defference_of_ga_pld = $g_amount - $pld;
                        
                        $calculation = array(
                            'sum_of_emi' => isset($sum_of_emi) ? $sum_of_emi :'0',
                            'Sum_of_divident' =>isset($sum_of_divident) ? $sum_of_divident :'0',
                            'gross_amount' => isset($GrossAmount) ? $GrossAmount :'0',
                            'sum_of_plan_amount' => isset($sum_of_plan_amount) ? $sum_of_plan_amount :'0',
                            'sum_of_winning_bid_amount' =>isset($sum_of_winning_bid_amount) ? $sum_of_winning_bid_amount : '0',
                            'defference_of_plan_and_bid' =>isset($defference_of_plan_amount_and_bid) ? $defference_of_plan_amount_and_bid :'0',
                            'net_amount_payable' => $defference_of_ga_pld ? $defference_of_ga_pld :'',
                            );
                }  
                        
            
            
            $member_detail = $this->db->where('member_id',$member_id)->select('dob,mobile,last_name,name,member_id')->get('tbl_members')->row_array();
            $new_array12 =  array('opening_balance' => isset($opening_balance) ? $opening_balance :'');
            $new_data = array_merge($member_detail,$new_array12);
            
             $output = array(
    			'status' => Success,
    			'message' => "Fastch Successfully",
    			'data' => $plan_all_detail,
    			'member_data' =>$new_data,
    			'calculation' => isset($calculation) ? $calculation : '[]',
    			'chit_details' => isset($chit_detail_for_invoice) ? $chit_detail_for_invoice :'[]',
    		); 
        }
        else{
             $member_detail = $this->db->where('member_id',$member_id)->select('dob,mobile,last_name,name,member_id')->get('tbl_members')->row_array();
            $output = array(
			'status' => Failure,
			'message' => "No Emi ",
			'data' => [],
			'member_data' =>$member_detail,
			'calculation' => [],
    		'chit_details' => [],
			);  
        }
        echo json_encode($output); die;
    }
    
    
    
    	public function member_risk_calculation(){
		$data =  json_decode($this->data); 
        $member_id = isset($data->member_id) ? $data->member_id : '';
		$getall_member_emi = $this->db->where('member_id',$member_id)->get('tbl_emi')->result_array();
		$sum_of_due = 0;
		foreach($getall_member_emi as $keys => $values){
			if($values['emi_status'] == 'due'){
				if($values['is_partial_payment'] == 'Yes'){
					$sum_of_due += $values['amount_due'];
				}
				else{
					$sum_of_due += $values['plan_emi'];
				}
			}
		}
		$sum_of_paid = 0;
		foreach($getall_member_emi as $keys=>$values){
			if($values['emi_status'] == 'paid'){
				$sum_of_paid += $values['plan_emi'];
			}
		}
		$calculate_of_risk = $sum_of_paid - $sum_of_due;
		$due_paid = array(
		    'total_emi_due'=>$sum_of_due,
		    'total_emi_paid'=>$sum_of_paid
		    );
		   

		if(!empty($calculate_of_risk)){
			$output = array(
			 'status' => Success,
			 'message' => 'GET Collateral Fetched Successfully',
			 'data' => isset($calculate_of_risk) ? $calculate_of_risk :'0',
			 'due_paid'=>$due_paid
			);
		}else{
		   $output = array(
			'status' => Failure,
			'message' => "GET Collateral Unsuccessfully",
			'data' => '0'
		   );     
		}
		echo json_encode($output); die;	
	}
	
	public function getcompneytransaction(){
	    $data =  json_decode($this->data);
        $type = isset($data->type) ? $data->type : '';
        $getcompneytransaction = $this->db->where('type',$type)->get('tbl_transactions')->result_array();
        	if(!empty($getcompneytransaction)){
			$output = array(
			 'status' => Success,
			 'message' => 'GET Compnay Fetched Successfully',
			 'data' => isset($getcompneytransaction) ? $getcompneytransaction :'0',
			);
		}else{
		   $output = array(
			'status' => Failure,
			'message' => "GET Compnay Unsuccessfully",
			'data' => '0'
		   );     
		}
		echo json_encode($output); die;	
	}
	
// 	public function gst(){
// 	    $data =  json_decode($this->data);
//         $member_id = isset($data->member_id) ? $data->member_id : '';
//         $chit_detail = $this->db->where('member_id',$member_id)->get('tbl_chits')->result_array();
//         foreach($chit_detail as $keys=>$values){
//             $plan_id = $values['plan_id'];
//             $plan_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
//             $plan_amount = $plan_detail['plan_amount'];
//             $forman_fees = $plan_detail['foreman_fees'];
//             $forman_amount = $plan_amount * $forman_fees / 100 ;
//             $forgo_amount = $values['forgo_amount'];
//             $bid_amount = $values['chit_amount'];
//             $order_detail = $this->db->where('plan_id',$plan_id)->where('slot_status','assigned')->get('tbl_orders')->num_rows;
//             print_r($order_detail);die;
//         }
// 	}

    public function submitGst(){
        $data =  json_decode($this->data);
        $gst_percentage = isset($data->gst_percentage) ? $data->gst_percentage : '';
        $tax = isset($data->tax) ? $data->tax : '';
        $data = array(
            'name' => $tax,
            'gst_percentage' => $gst_percentage,
            'added_date' => date('Y-m-d h:i:s')
            );
        $this->db->insert('tbl_gst',$data);
        $insert_id = $this->db->insert_id();
        if(!empty($insert_id)){
			$output = array(
			 'status' => Success,
			 'message' => 'Gst Insert Successfully',
			 'data' => ''
			);
		}else{
		   $output = array(
			'status' => Failure,
			'message' => 'Somthing error',
			'data' => '0'
		   );     
		}
		echo json_encode($output); die;	
    }
    
    public function updateGst(){
        $data =  json_decode($this->data);
        $id = isset($data->id) ? $data->id : '';
        $gst_percentage = isset($data->gst_percentage) ? $data->gst_percentage : '';
        $tax = isset($data->tax) ? $data->tax : '';
        $data = array(
            'name' => $tax,
            'gst_percentage' => $gst_percentage,
            'added_date' => date('Y-m-d h:i:s')
            );
        $this->db->where('gst_id',$id)->update('tbl_gst',$data);
        if(!empty($id)){
			$output = array(
			 'status' => Success,
			 'message' => 'Gst Update Successfully',
			 'data' => ''
			);
		}else{
		   $output = array(
			'status' => Failure,
			'message' => 'Somthing error',
			'data' => '0'
		   );     
		}
		echo json_encode($output); die;	
    }
    
    public function current_opening($member_id,$transcation_amount,$type){
        $date = date("M,Y");
		$member_last_transcation_detail = $this->db->order_by('transaction_id','DESC')->where('transaction_month',$date)->select('current_balance,opening_balance')->where('subscriber_id',$member_id)->get('tbl_transactions')->row_array();
		if(empty($member_last_transcation_detail)){
			$order_detail = $this->db->where('member_id',$member_id)->get('tbl_orders')->result_array();
			$total_plan_order = array();
			foreach($order_detail as $keys=>$values){
				$total_plan_order[] = $values['plan_id'];
			}	
			$total_plan = array_unique($total_plan_order);
			$total_emi = array();
			foreach($total_plan as $keys=>$values){
				$plan_data = $this->db->where('plan_id',$values)->get('tbl_plans')->row_array();
				$months_completed = $plan_data['months_completed'];				
				for($i=1 ; $i<=$months_completed ; $i++){
					$emi = $this->db->where('member_id',$member_id)->where('emi_no',$i)->where('emi_status','due')->get('tbl_emi')->result_array();
					$total_emi[] = $emi;
				}
			}
			$sum_of_due_amount = 0;
			foreach ($total_emi as $key=>$value){
				foreach($value as $keys=>$values ){
				if($values['is_partial_payment'] == 'Yes'){
					$sum_of_due_amount += $values['amount_due'];
				}
				else{
					$sum_of_due_amount += $values['plan_emi'];
				}
			}
			}
			if($type == 'receipt'){
			$opening_balance = $sum_of_due_amount;
			$current_amount = $sum_of_due_amount - $transcation_amount;	
			}else{
			    $opening_balance = $sum_of_due_amount;
			$current_amount = $sum_of_due_amount + $transcation_amount;	
			}
		}else{
			$cb = $member_last_transcation_detail['current_balance'];
			$op = $member_last_transcation_detail['opening_balance'];
			$opening_balance = $cb;
			if($type == 'receipt'){
			$current_amount = $cb - $transcation_amount;
			}else{
			$current_amount = $cb + $transcation_amount;
			}
		}
		$new_arr = array(
		  'opening_balance' => $opening_balance,
		  'current_amount' => $current_amount
		);
		return $new_arr; 
	}
	public function addserviceprovider(){
	    $data =  json_decode($this->data);
        $service_provider_id = isset($data->service_provider_id) ? $data->service_provider_id : '';
        $service_provider = isset($data->name) ? $data->name : '';
        $service_provider_designation = isset($data->description) ? $data->description : '';
        $parent_id = isset($data->parent_id) ? $data->parent_id : '';
        if(!empty($service_provider_id)){
            $data = array(
                'name' =>$service_provider,
                'description' => $service_provider_designation,
                'parent_id' => $parent_id,
                'update_date' => date("Y-m-d h:i:s"),
                );
            $this->db->where('service_provider_id',$service_provider_id)->update('tbl_service_provider',$data);
            $message = 'Service Provider Update Successfully';
        }
        else{
             $data = array(
                'name' =>$service_provider,
                'description' => $service_provider_designation,
                'parent_id' => $parent_id,
                'added_date' => date("Y-m-d h:i:s"),
            );
            $this->db->insert('tbl_service_provider',$data);
            $message = 'Service Provider Added Successfully';
        }
            if(!empty($message)){
    			   $output = array(
    				   'status' => Success,
    				   'message' => $message,
    				   'data' => [],
    			   );	
    		   }else{
    			   $output = array(
    				   'status' => Failure,
    				   'message' => $message,
    				   'data' => []
    			   );
    		   }
    	echo json_encode($output); die;	
	}
	public function get_service_provider(){
	   $data =  $this->db->get('tbl_service_provider')->result_array();
	   if(!empty($data)){
    	   $output = array(
	    	   'status' => Success,
			   'message' => 'Servise Provider Fetch Successfully',
			   'data' => $data,
		   );	
	   }else{
		   $output = array(
			   'status' => Failure,
			   'message' => 'in valid data',
			   'data' => ''
		   );
	   }
    	echo json_encode($output); die;	
	}
	
	
	public function saveDenomationForTransaction(){
        $data =  json_decode($this->data);
        $transaction_id = isset($data->transaction_id) ? $data->transaction_id : '';
       	$ten = isset($data->ten) ? $data->ten : '';
    	$twenty = isset($data->twenty) ? $data->twenty : '';
    	$fifty = isset($data->fifty) ? $data->fifty : '';
    	$hundred = isset($data->hundred) ? $data->hundred : '';
    	$two_hundred = isset($data->two_hundred) ? $data->two_hundred : '';
    	$five_hundred = isset($data->five_hundred) ? $data->five_hundred : '';
    	$two_thousand = isset($data->two_thousand) ? $data->two_thousand : '';
     if(!empty($transaction_id)){
    	$data = array(
    	  'ten' => $ten,
    	  'tewenty' => $twenty,
    	  'fifty' => $fifty,
    	  'hundred' => $hundred,
    	  'two_hundred' => $two_hundred,
    	  'five_hundred' => $five_hundred,
    	  'two_thousand' => $two_thousand,
    	  'updated_date' => date('Y-m-d H:i:s')
    	);
	   $this->db->where('transaction_id',$transaction_id)->update('tbl_transactions',$data);
	    $output = array(
	    	   'status' => Success,
			   'message' => 'Update Denomation Successfully',
			   'data' => array(),
		   );
	   
	   }else{
		   $output = array(
			   'status' => Failure,
			   'message' => 'Invaild Transaction ID',
			   'data' => array()
		   );
	   }
    	echo json_encode($output); die;
	}
	
	
	public function getGST(){
	  $data = $this->db->get('tbl_gst')->result_array(); 
	  if(!empty($data)){
	    $output = array(
    	   'status' => Success,
		   'message' => 'Get Gst Value Successfully',
		   'data' => $data
		);  
	  }else{
	     $output = array(
		   'status' => Failure,
		   'message' => 'Invaild Data',
		   'data' => array()
		);  
	  }
	  echo json_encode($output); die;
	}
	
	public function getAlltranscation(){
	    $alldata = $this->db->order_by('transaction_id','desc')->get('tbl_transactions')->result_array();
            $new_data = array();
            foreach($alldata as $keys=>$values){
                $member_id = $values['subscriber_id'];
                $service_provider_id = $values['service_provider_id'];
                if(!empty($member_id) && $member_id != ['0']){
                    $member_detail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
                    $member_name = array('member_name' => $member_detail['name']);
                }
                else{
                    $member_name =array('member_name' =>'No' );
                }
                if(!empty($service_provider_id) && $service_provider_id != ['0']){
                    $service_detail = $this->db->where('service_provider_id',$service_provider_id)->get('tbl_service_provider')->row_array();
                    $service_name = array('service_provider_name' => $service_detail['name']);
                }else{
                     $service_name = array('service_provider_name' => 'No');
                }
                $new_data[] = array_merge($member_name,$service_name,$values);
            }
        if(!empty($new_data)){
    	    $output = array(
        	   'status' => Success,
    		   'message' => 'Get Transaction succesfully',
    		   'data' => $new_data
    		);  
    	  }else{
    	     $output = array(
    		   'status' => Failure,
    		   'message' => 'Invaild Data',
    		   'data' => '0'    		);  
    	  }
    	  echo json_encode($output); die;
	}
	
	public function getAlltranscation_withfilter(){
	     $data =  json_decode($this->data);
        $mnth_filter = isset($data->mnth_filter) ? $data->mnth_filter : '';
        $year = isset($data->year) ? $data->year : '';
        $Transcation_type = isset($data->Transcation_type) ? $data->Transcation_type : '';
        $transcation_for = isset($data->transcation_for) ? $data->transcation_for : '';
        $payment_type = isset($data->payment_type) ? $data->payment_type : '';
         $InputArray = array($mnth_filter,$year);
        $month_year =    implode(",",$InputArray);
         
         if($Transcation_type !='' && $payment_type!='' && $transcation_for !='' ){
             if($transcation_for == 'others'){
               $transcation_data = $this->db->order_by('transaction_id','desc')->where('transaction_type',$Transcation_type)->where('transaction_for!=','pay_emi')->where('transaction_for!=','Chit Handover')->where('type',$payment_type)->get('tbl_transactions')->result_array();
             }else{
                  $transcation_data = $this->db->order_by('transaction_id','desc')->where('transaction_type',$Transcation_type)->where('transaction_for',$transcation_for)->where('type',$payment_type)->get('tbl_transactions')->result_array();
             }
         }else{
            if($Transcation_type!='' && $payment_type!='' ){
                 $transcation_data = $this->db->order_by('transaction_id','desc')->where('transaction_type',$Transcation_type)->where('type',$payment_type)->get('tbl_transactions')->result_array();
            }elseif($Transcation_type!='' && $transcation_for!=''){
                 $transcation_data = $this->db->order_by('transaction_id','desc')->where('transaction_type',$Transcation_type)->where('transaction_for',$transcation_for)->get('tbl_transactions')->result_array();
            }elseif($payment_type!='' && $transcation_for!=''){
                $transcation_data = $this->db->order_by('transaction_id','desc')->where('type',$payment_type)->where('transaction_for',$transcation_for)->get('tbl_transactions')->result_array();
            }
            else{
                if(!empty($payment_type) && $Transcation_type =='' && $transcation_for == '' ){
                    $transcation_data = $this->db->order_by('transaction_id','desc')->where('type',$payment_type)->get('tbl_transactions')->result_array();
                }elseif(!empty($Transcation_type) && $payment_type =='' && $transcation_for == '' ){
                     $transcation_data = $this->db->order_by('transaction_id','desc')->where('transaction_type',$Transcation_type)->get('tbl_transactions')->result_array();
                }
                elseif(!empty($transcation_for) && $payment_type =='' && $Transcation_type == '' ){
                    $transcation_data = $this->db->order_by('transaction_id','desc')->where('transaction_for',$transcation_for)->get('tbl_transactions')->result_array();
                }
            }
         }
         if(!empty($transcation_data)){
          $new_data = array();
            foreach($transcation_data as $keys=>$values){
                $member_id = $values['subscriber_id'];
                $service_provider_id = $values['service_provider_id'];
                if(!empty($member_id) && $member_id != ['0']){
                    $member_detail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
                    $member_name = array('member_name' => $member_detail['name']);
                }
                else{
                    $member_name =array('member_name' =>'No' );
                }
                if(!empty($service_provider_id) && $service_provider_id != ['0']){
                    $service_detail = $this->db->where('service_provider_id',$service_provider_id)->get('tbl_service_provider')->row_array();
                    $service_name = array('service_provider_name' => $service_detail['name']);
                }else{
                     $service_name = array('service_provider_name' => 'No');
                }
                $new_data[] = array_merge($member_name,$service_name,$values);
            }
         }
         if(!empty($new_data)){
    	    $output = array(
        	   'status' => 'Success',
    		   'message' => 'Get Transaction succesfully',
    		   'data' => $new_data
    		);  
    	  }else{
    	     $output = array(
    		   'status' => Failure,
    		   'message' => 'Invaild Data',
    		   'data' => '0'    		);  
    	  }
    	  echo json_encode($output); die;
	}
	
	public function banktranscationcalculation($bank_account_id,$transcation_amount,$type){
	    $bank_detail = $this->db->select('current_account_balance')->where('bank_account_id',$bank_account_id)->get('bank_accounts')->row_array();
	    if(!empty($bank_detail)){
	        if(!empty($bank_detail['current_account_balance'])){ $bank_current_account = $bank_detail['current_account_balance'];}else{ $bank_current_account = 0;}
	        if($type == 'receipt'){
	            $new_transcation_amount = $bank_current_account + $transcation_amount;
    	    }else{
    	        $new_transcation_amount = $bank_current_account - $transcation_amount;
    	    }
    	    $submit_data = array(
    	        'current_account_balance' => $new_transcation_amount
    	        );
    	    $this->db->where('bank_account_id',$bank_account_id)->update('bank_accounts',$submit_data);
	    }
	}
	
	
	public function dummyAPi(){
	   $output = array(
	     'status' => 'Success',
	     'message' => 'Demo'
	   );    
	 echo json_encode($output); die;
	}
	
	
	//Clone Reports 
	
// 	public function automaticReports(){
	  
// 	 $current_month = date('M,Y');
// 	 $getEmi = $this->db->where('emi_month',$current_month)->where('emi_status','due')->get('tbl_emi')->result_array();
// 	 $new_array = array();
//      foreach($getEmi as $key => $value){
//         $member = $this->db->where('member_id',$value['member_id'])->get('tbl_members')->row_array();
//         $new_array[$key]['member_id'] = isset($member['member_id']) ? $member['member_id'] : '';
//         $new_array[$key]['member_name'] = isset($member['name']) ? $member['name'] : '';
//         $new_array[$key]['member_mobile'] = isset($member['mobile']) ? $member['mobile'] : '';
//         $new_array[$key]['gross_emi_amount'] = array_sum($value['plan_emi']);
//         $new_array[$key]['share_of_discount'] = 0;
//         $new_array[$key]['net_current_month_due']= 0;
//         $new_array[$key]['chit_taken']= 0;
//         $new_array[$key]['other_dues_and_payables']= 0;
//         $new_array[$key]['net_amount']= 0;
//         $new_array[$key]['paid_amount']= 0;
//         $new_array[$key]['balance_amount']= 0;
//         $new_array[$key]['report_month']= 0;//      }
    
//     echo json_encode($new_array); die;
     
//     // foreach()
//     // $dta = array(
//     //   'member_id' => ,
//     //   'member_name' =>,
//     //   'member_mobile' =>,
//     //   'gross_emi_amount' => $total,
//     //   'share_of_discount' =>,
//     //   'net_current_month_due' =>,
//     //   'chit_taken' =>,
//     //   'other_dues_and_payables'=>,
//     //   'net_amount' =>,
//     //   'paid_amount' =>,
//     //   'balance_amount' =>,
//     //   'report_month' =>,
//     //   'balance_amount' => 
//     // );     
// 	}
	
	
	public function automaticReports(){
	    $data = json_decode($this->data);
	    if(!empty($data)){
	         $year = isset($data->year) ? $data->year : '';
	         $month = isset($data->mnth_filter) ? $data->mnth_filter : '';
	    }else{
	         $year = date('Y');
	         $month = date('M');
	    }
        $month_filter = $month.",".$year;
	    $getallmemberinemies = $this->db->select('member_id')->get('tbl_emi')->result_array();
	    $allmember = array();
	    foreach($getallmemberinemies as $keys=>$values){
	        $data = array();
	        $allmember[] = $values['member_id'];
	         $members = array_unique($allmember);
    	    foreach($members as $keys => $values){
    	        $member_id = $values;
    	        $newDate2 = date("m", strtotime($month_filter));
                 $end_date = (int)$newDate2 - 1;
                 $emi_details = array();
                     for( $i = 1 ; $i<= $end_date ; $i++ ){
                                 $dateObj   = DateTime::createFromFormat('!m', $i);
                                 $monthName = $dateObj->format('M');
                                 $month_filter2 = $monthName.",".$year;
                                 $emi_details[] = $this->db->where('member_id',$member_id)->where('emi_month',$month_filter2)->get('tbl_emi')->result_array();
                     }
                     $sum_of_due = 0;
                  $sum_of_paid = 0;
                  $sum_of_divident = 0;
                 foreach($emi_details as $ky=>$vy){
                         foreach($vy as $k=>$v){
                             if($v['emi_status'] == 'paid'){
                                   $sum_of_paid += $v['plan_emi'];
                               }elseif($v['emi_status'] == 'due'){
                                  if($v['is_partial_payment'] == 'Yes'){
                                      $sum_of_due += $v['amount_due'];
                                  }else{
                                      if(!empty($v['divident'])){
                                          $sum_of_due += $v['plan_emi'] - $v['divident'];
                                      }else{
                                        $sum_of_due += $v['plan_emi'];
                                      }
                                    //   $sum_of_due += $v['plan_emi'];
                                  }
                               } elseif(!empty($v['divident'])){
                                   $sum_of_divident += $v['divident'];
                               }
                         }
                }
                $current_month_due = $this->db->where('member_id',$member_id)->where('emi_month',$month_filter)->get('tbl_emi')->result_array();
                $sum_current_month_due = 0;
                $current_month_divident = 0;
                $current_emies = 0;
                $emi_no_check = array();
                // print_r($current_month_due);die;
                foreach($current_month_due as $keys=>$values){
                      if($values['emi_status'] == 'due'){
                          if($values['is_partial_payment'] == 'Yes'){
                              $sum_current_month_due += $values['amount_due'];
                          }else{
                              $sum_current_month_due += $values['plan_emi'];
                          }
                          }if(!empty($values['divident'])){
                            $current_month_divident += $values['divident'];
                        }else{
                            $current_month_divident += 0;
                        }
                       $current_emies +=  $values['plan_emi'];
                       $emi_no_check[] = array(
                           'plan_id' => isset($values['plan_id']) ? $values['plan_id'] : '',
                           'group_id' => isset($values['group_id']) ? $values['group_id'] : '',
                           'emi_no' => isset($values['emi_no']) ? $values['emi_no'] : '',
                           );
                 }
                 $input = array_map("unserialize", array_unique(array_map("serialize", $emi_no_check)));
                 $chit_amount =0;
                 foreach($input as $keys=>$values){
                     $plan_id = $values['plan_id'];
                     $group_id = $values['group_id'];
                     $emi_no = $values['emi_no'];
                     $auction_detail = $this->db->select('auction_id')->where('plan_id',$plan_id)->where('group_id',$group_id)->where('auction_no',$emi_no)->get('tbl_auction')->row_array();
                     if(!empty($auction_detail)){
                          $auctrion_id = $auction_detail['auction_id'];
                          $chit_detail = $this->db->select('added_date,chit_amount')->where('auction_id',$auctrion_id)->where('plan_id',$plan_id)->where('group_id',$group_id)->where('member_id',$member_id)->get('tbl_chits')->row_array();
                          if(!empty($chit_detail['chit_amount'])){
                              $chit_amount += $chit_detail['chit_amount'];
                          }else{
                              $chit_amount += 0;
                          }
                     }
                 }
                $paid_detail = $this->db->where('subscriber_id',$member_id)->where('transaction_month',$month_filter)->where('type','receipt')->get('tbl_transactions')->result_array();
                $paid_amount = 0;
                if(!empty($paid_detail)){
                    foreach($paid_detail as $keys=>$values){
                        $paid_amount += $values['transaction_amount'];
                    }
                }
                    $newDate3 = date("m", strtotime($month_filter));
                    $last_date = (int)$newDate3 - 1;
                    $dateObj2   = DateTime::createFromFormat('!m', $last_date);
                    $monthName12 = $dateObj2->format('M');
                    $month_filter31 = $monthName12.",".$year;
                $last_report_data = $this->db->where('member_id',$member_id)->where('report_month',$month_filter31)->get('tbl_reports')->row_array();
                if(!empty($last_report_data['balance_amount'])){
                    $opening_balance = $last_report_data['balance_amount'];
                }else{
                    $opening_balance = $sum_of_due;
                }
                
                $Net_curr_moth_due = $current_emies - $current_month_divident;
                $net_amount = $opening_balance + $Net_curr_moth_due - $chit_amount;
                $balance_amount = $net_amount - $paid_amount;
                $member_detail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
                 $data = array(
                    'Member_name' => $member_detail['name'],//
                    'Member_id' => $member_id,//
                    'Member_mobile' =>  $member_detail['mobile'],//
                    'Opening_balance' => isset($opening_balance) ? $opening_balance : '0', // 
                    'gross_emi_amount' => isset($current_emies) ? $current_emies : '0', //
                    'paid_amount' => isset($paid_amount) ?  $paid_amount : '0',//
                    'chit_taken' => isset($chit_amount) ? $chit_amount : '0', // 
                    'net_amount' => isset($net_amount) ? $net_amount : '0',
                    'balance_amount' => isset($balance_amount) ? $balance_amount : '0',
                    'share_of_discount' => isset($current_month_divident) ? $current_month_divident :'0',//
                    'net_current_month_due' => $Net_curr_moth_due,//
                    'report_month' => isset($month_filter) ? $month_filter :'0',//
                    'added_date' => date('m/d/y'),
                );
                $check_report_data = $this->db->where('member_id',$member_id)->where('report_month',$month_filter)->get('tbl_reports')->row_array();
                if(empty($check_report_data)){
                    $this->db->insert('tbl_reports',$data);
                }else{
                    $this->db->where('member_id',$member_id)->where('report_month',$month_filter)->update('tbl_reports',$data);
                }
    	    }
	    }
	}
	
	public function getreportlist(){
	    $data =  json_decode($this->data);
        $month = isset($data->mnth_filter) ? $data->mnth_filter : '';
        $year = isset($data->year) ? $data->year : '';
        $month_filter = $month.",".$year;
        $data = $this->db->where('report_month',$month_filter)->get('tbl_reports')->result_array();
        if(!empty($data)){
            $output = array(
          'status' => Success,
          'message' => 'Control Sheet Fetched Successfully',
          'data' => $data
        );
        }else{
            $output = array(
    	      'status' => Failure,
    	      'message' => "Data Not Found",
    	      'data' => []
    	    );  
        }
        echo json_encode($output); die;
	}
	
	public function getmastertranscationtype(){
        $data = $this->db->get('tbl_transaction_type_master')->result_array();
        if(!empty($data)){
            $output = array(
          'status' => Success,
          'message' => 'Data Fetched Successfully',
          'data' => $data
        );
        }else{
            $output = array(
    	      'status' => Failure,
    	      'message' => "Data Not Found",
    	      'data' => []
    	    );  
        }
        echo json_encode($output); die;
	}
	
    	public function SubmitGeneralLedgerMaster($submit_data){
	    $check_insert_id = $this->db->select('insert_id')->ORDER_BY('general_ledger_master_id','DESC')->get('tbl_general_ledger_master')->row_array();
            if(!empty($check_insert_id['insert_id'])){
                $insert_id =$check_insert_id['insert_id'] + 1;
            }else{
                $insert_id = 1;
            }
                 if($submit_data['payment_mode'] =='cash'){
                    $account_description ='CASH';
                }else{
                     $account_description = 'BANK';
                }
            if($submit_data['type'] == 'receipt'){
                if(!empty($submit_data['subscriber_id'])){
                    $member_detail = $this->db->where('member_id',$submit_data['subscriber_id'])->get('tbl_members')->row_array();
                    $account_name = $member_detail['name'];
                }
                if($submit_data['transaction_for'] == 'pay_emi'){
                    $c_code = '116';
                    $category_desc = 'Subscription Received ';
                }else{
                     $c_code = '116';
                    $category_desc = 'Chit Handover Received ';
                }
               
				$member_data_2 = $this->get_member_detail($submit_data['subscriber_id']);
                $data = array(
                    'insert_id'=> $insert_id,
                    'c_code' => isset($c_code) ? $c_code :'',
                    'category_desc' => isset($category_desc) ? $category_desc :'0',
                    'transaction_mode' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                    'transaction_type' => isset($submit_data['transaction_type']) ? $submit_data['transaction_type'] :'',
                    'transaction_description' => isset($submit_data['transaction_for']) ? $submit_data['transaction_for'] :'',
                    'amount' => isset($submit_data['transaction_amount']) ? $submit_data['transaction_amount'] :'',
                    'dr_cr' =>'Dr',
                    'sub_id' => isset($submit_data['subscriber_id']) ? $submit_data['subscriber_id'] :'',
                    'account_name' => isset($account_name) ? $account_name : '',
                    'added_date' => date('Y-m-d h:i:s'),
                    'account_description' => isset($account_description) ? $account_description :'',
                    'gl_account' => '1002',
                    'type' => 'Payment',
                );
                 $insert_data =  $this->db->insert('tbl_general_ledger_master',$data);
                 $insert_id = $this->db->insert_id();
                 $selest_ensert_id = $this->db->where('general_ledger_master_id',$insert_id)->get('tbl_general_ledger_master')->row_array();
                  $data2 = array(
                    'insert_id'=> $selest_ensert_id['insert_id'],
                    'c_code' => isset($c_code) ? $c_code :'',
                    'category_desc' => isset($category_desc) ? $category_desc :'0',
                    'transaction_mode' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                    'transaction_type' => isset($submit_data['transaction_type']) ? $submit_data['transaction_type'] :'',
                    'transaction_description' => isset($submit_data['transaction_for']) ? $submit_data['transaction_for'] :'',
                    'amount' => isset($submit_data['transaction_amount']) ? $submit_data['transaction_amount'] :'',
                    'dr_cr' =>'Cr',
                    'sub_id' => isset($submit_data['subscriber_id']) ? $submit_data['subscriber_id'] :'',
                    'account_name' => isset($account_name) ? $account_name : '',
                    'added_date' => date('Y-m-d h:i:s'),
                    'account_description' => 'Subscribers A/c',
                    'gl_account' => '1002',
                    'type' => 'Payment',
                );
                $this->db->insert('tbl_general_ledger_master',$data2);
            }else{
                    if(!empty($submit_data['subscriber_id'])){
                        $member_detail = $this->db->where('member_id',$submit_data['subscriber_id'])->get('tbl_members')->row_array();
                        $account_name = $member_detail['name'];
                    }
                    $data = array(
                        'insert_id'=> $insert_id,
                        'c_code' => isset($employee_code) ? $employee_code :'0',
                        'transaction_mode' =>isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'transaction_type' => isset($submit_data['transaction_type']) ? $submit_data['transaction_type'] :'',
                        'transaction_description' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'amount' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'dr_cr' =>'Cr',
                        'sub_id' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'account_name' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'added_date' => date('Y-m-d h:i:s'),
                        'account_description' => 'Subscribers A/c',
                        'gl_account' => '1002',
                        'type' => 'Receipt',
                    );
                     $insert_data =  $this->db->insert('tbl_general_ledger_master',$data);
                     $insert_id = $this->db->insert_id();
                     
                     $selest_ensert_id = $this->db->where('general_ledger_master_id',$insert_id)->get('tbl_general_ledger_master')->row_array();
                      $data2 = array(
                        'insert_id'=> $selest_ensert_id['insert_id'],
                        'c_code' => isset($employee_code) ? $employee_code :'0',
                        'transaction_mode' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'transaction_type' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'transaction_description' => isset($account_description) ? $account_description :'',
                        'amount' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'dr_cr' =>'Dr',
                        'sub_id' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'account_name' => isset($bank_detail['bank_name']) ? $bank_detail['bank_name'] :'',
                        'added_date' => date('Y-m-d h:i:s'),
                        'account_description' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'gl_account' => isset($submit_data['payment_mode']) ? $submit_data['payment_mode'] :'',
                        'type' => 'Receipt',
                    );
                $this->db->insert('tbl_general_ledger_master',$data2);
            }
	}
	
	    public function CustomerRegister(){
    	    $data =  json_decode($this->data);
    	    $username = isset($data->username) ? $data->username : '';
    	    $mobile = isset($data->mobile) ? $data->mobile : '';
    	    $password = isset($data->password) ? $data -> password : '';
    	    $gmail = isset($data->email) ? $data->email : '';
    	    
    	    $check_username = $this->db->where('mobile',$mobile)->get('users')->num_rows();
    	    $check_email = $this->db->where('email',$gmail)->get('users')->num_rows();
    	    $mobileDigitsLength = strlen($mobile);
    	    
    	   
    	    if($mobileDigitsLength == 10){
    	       if($check_email == 0 AND $check_username == 0){
            	       function email_validation($str) {
                        return (!preg_match(
                    "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^", $str))
                            ? FALSE : TRUE;
                    }
                      
                    // Function call
                    if(!email_validation($gmail)) {
                        $output = array(
                	     'status' => 'Failure',
                	     'message' => 'Invalid email address'
        	            ); 
                    }
                    else {
                        if(!empty($password) And !empty($mobile) And !empty($gmail) And !empty($username)){
                            $ps = md5($password);
                            $data = array(
            	           'username' => isset($username) ? $username : '',
            	           'mobile' => isset($mobile) ? $mobile : '',
            	           'password' => isset($ps) ? $ps : '',
            	           'email' => isset($gmail) ? $gmail : '',
            	           'type' => 'user',
            	           'added_date'=> date('Y-m-d h:i:s'),
            	           );
            	       $this->db->insert('users',$data);
            	       $insert_id_user = $this->db->insert_id();
            	       $member_data = array(
            	           'name' => isset($username) ? $username : '',
            	           'mobile' => isset($mobile) ? $mobile : '',
            	           'email' => isset($gmail) ? $gmail : '',
            	           );
            	       $this->db->insert('tbl_members',$member_data);
            	       $member_insert_id = $this->db->insert_id();
            	       $member_id = array(
            	           'member_id' => isset($member_insert_id) ? $member_insert_id : '',
            	           );
            	       $this->db->where('user_id',$insert_id_user)->update('users',$member_id);
            	       if(!empty($member_insert_id)){
            	            $output = array(
                    	     'status' => 'Success',
                    	     'message' => 'Register  successfully'
                    	   ); 
                            }else{
                                $output = array(
                    	     'status' => 'Failure',
                    	     'message' => 'register failure'
            	            );
                        }
        	       }else{
        	            $output = array(
                	     'status' => 'Failure',
                	     'message' => 'register failure'
        	            ); 
        	       }
                    }
        	    }else{
        	       if($check_username != 0){ $user_message = 'Mobile';}else{$user_message = '';}
        	       if($check_email != 0){ $email_message = 'email';}else{$email_message = '';}
        	       if($check_email != 0 AND $check_username !=0){ $coma = ',';}else{$coma = '';}
        	         $output = array(
                	     'status' => 'Failure',
                	     'message' => $user_message.$coma.$email_message.' is already exist'
        	            ); 
        	    }
    	    }else{
    	        $output = array(
                	     'status' => 'Failure',
                	     'message' => 'Invalid Mobile Number '
        	            ); 
    	    }
    	    echo json_encode($output); die;
	    }
	    
	    public function checkvalidemail(){
	         $data =  json_decode($this->data);
    	     $email = isset($data->email) ? $data->email : '';
    	  function email_validation($str) {
                return (!preg_match(
            "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^", $str))
                    ? FALSE : TRUE;
            }
              
            // Function call
            if(!email_validation($email)) {
                echo "Invalid email address.";
            }
            else {
                echo "Valid email address.";
            }
	    }
	    
	public function CustomerLogin(){
	    $data =  json_decode($this->data);
    	$mobile = isset($data->mobile) ? $data->mobile : '';
    	$password = isset($data->password) ? $data->password : '';
    	$checkdata = $this->db->where('mobile',$mobile)->where('password',md5($password))->get('users')->row_array();
    	
    
    	if(!empty($checkdata)){
    	    $kyc_detail = $this->db->select('pan,aadhar')->where('member_id',$checkdata['member_id'])->get('tbl_kyc')->row_array();
        	$new_array1 = array(
        	    'aadhar' =>'',
        	    'pan' => ''
        	    );
        	$newarray2 = isset($kyc_detail) ? $kyc_detail : $new_array1;
        	$new_arr = array_merge($checkdata,$newarray2);
    	
    	    $output = array(
        	     'status' => 'Success',
        	     'message' => 'login Success',
        	     'data' => $new_arr,
        	   ); 
    	}else{
    	    $checkmobile = $this->db->where('mobile',$mobile)->get('users')->row_array();
    	    if(!empty($checkmobile)){
    	        $output = array(
        	     'status' => 'Failure',
        	     'message' => 'Your password is invalid , please try again'
                );
    	    }else{
    	        $output = array(
        	     'status' => 'Failure',
        	     'message' => 'You are not registered for this event !'
                );
    	    }
    	}
    	echo json_encode($output);die;
	}
	
	public function sendotp($message,$mobileNo){
        $postData = array(
            'username'=> 'mosambi',
            'password'=> '123456',
            'drout' => '3',
            'senderid' => 'MOSMBI',
            'intity_id' =>  '1201161001967871627',
            'template_id' => '1207164844774747538',
            'numbers'=> $mobileNo,
            'language' => 'en',
            'message' => $message
        );
        
        $url="http://dlt.fastsmsindia.com/messages/sendSmsApi";
        
        $ch = curl_init();
        $data = http_build_query($postData);
        $getUrl = $url."?".$data;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $getUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);
        $data_1 = curl_exec($ch);
        if(curl_errno($ch))
        {
          echo 'error:' . curl_error($ch);die;
        }

   }

    
    public function createOtp(){
        $data =  json_decode($this->data);
    	$mobile_no = isset($data->mobile) ? $data->mobile : '';
        $checkMobile = $this->db->where('mobile',$mobile_no)->get('users')->num_rows();
        if($checkMobile !=0){
            $otp = rand(1000,9999);
            $message = "Your One Time Password for Login in Mosambi app is ".$otp." Mosambi Services";
             $this->sendotp($message,$mobile_no);
             $update_data = array(
                 'otp' => $otp,
                 'otp_verify' => '1'
                 );
             $this->db->where('mobile',$mobile_no)->update('users',$update_data);
            $output = array(
        	     'status' => 'Success',
        	     'message' => 'Otp sent sucessfully',
        	     'otp' => $otp,
        	     'mobile' => $mobile_no
        	   );
        }else{
             $output = array(
        	     'status' => 'Failure',
        	     'message' => 'You are not registered for this event !'
                );
        }
        echo json_encode($output);die;
    }
    
    public function otpVerify(){
        $data =  json_decode($this->data);
    	$mobile_no = isset($data->mobile) ? $data->mobile : '';
    	$otp = isset($data->otp) ? $data->otp : '';
        $checkMobile = $this->db->where('mobile',$mobile_no)->get('users')->row_array();
        if(!empty($checkMobile)){
            if($checkMobile['otp_verify'] == 1){
                if($checkMobile['otp'] == $otp){
                    $updateData = array(
                        'otp_verify' => 0
                        );
                        $this->db->where('mobile',$mobile_no)->update('users',$updateData);
                     $output = array(
                	     'status' => 'Success',
                	     'message' => 'Otp verify sucessfully',
                	     'data' => $mobile_no ,
                	   );
                }else{
                   $output = array(
            	     'status' => 'Failure',
            	     'message' => 'Invalid Otp Entered'
                     );  
                }
            }else{
               $output = array(
        	     'status' => 'Failure',
        	     'message' => 'You are already verifyed !'
                 ); 
            }
        }else{
            $output = array(
        	     'status' => 'Failure',
        	     'message' => 'You are not registered for this event !'
             ); 
        }
        echo json_encode($output);die;
    }
    
    public function conformPassword(){
        $data =  json_decode($this->data);
    	$mobile_no = isset($data->mobile) ? $data->mobile : '';
    	$password = isset($data->password) ? $data->password : '';
    	$conform_password = isset($data->conform_password) ? $data->conform_password : '';
    	if($password == $conform_password){
    	    $checkMobile = $this->db->where('mobile',$mobile_no)->where('type','user')->get('users')->num_rows();
            if($checkMobile !=0){
                $update_data = array(
                    'password' => md5($password)
                    );
                $this->db->where('mobile',$mobile_no)->update('users',$update_data);
                $output = array(
            	     'status' => 'Success',
            	     'message' => 'Your password has been changed successfully',
            	     'data' => $mobile_no
            	   );
            }else{
                 $output = array(
            	     'status' => 'Failure',
            	     'message' => 'You are not registered for this event !'
                    );
            }
    	}else{
    	     $output = array(
        	     'status' => 'Failure',
        	     'message' => 'Password and Confirm Password must be match.'
             );
    	}
    	echo json_encode($output);die;
    }
    
    public function AddKyc(){
        $data =  json_decode($this->data);
    	$user_id = isset($data->member_id) ? $data->member_id : '';
    	$aadhar = isset($data->aadhar) ? $data->aadhar : '';
    	$pan = isset($data->pan) ? $data->pan : '';
    	$aadhar_proof = isset($data->aadhar_proof) ? $data->aadhar_proof : '';
    	$aadhar_proof_back = isset($data->aadhar_proof_back) ? $data->aadhar_proof_back : '';
    	$pan_proof = isset($data->pan_proof) ? $data->pan_proof : '';
    	
    	$checkmember = $this->db->where('member_id',$user_id)->get('users')->num_rows();
    	if($checkmember > 0){
    	    if(!empty($aadhar_proof)){
        	   $target = BASEPATH.'../images/kyc/';
                $image_arr = array();
                $image_parts = explode(";base64,",$aadhar_proof);
                $image_type_aux = explode("image/", $image_parts[0]);
                if(!empty($image_type_aux[1])){
                    $image_type = $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);
                    $aadherimgname =  uniqid() . '.'.$image_type;
                    $file = $target.$aadherimgname;
                    $image_arr = $file;
                    file_put_contents($file, $image_base64); 
                }
        	}if(!empty($aadhar_proof_back)){
        	   $target = BASEPATH.'../images/kyc/';
                $image_arr = array();
                $image_parts = explode(";base64,",$aadhar_proof_back);
                $image_type_aux = explode("image/", $image_parts[0]);
                if(!empty($image_type_aux[1])){
                    $image_type = $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);
                    $aadherimgnameback =  uniqid() . '.'.$image_type;
                    $file = $target.$aadherimgnameback;
                    $image_arr = $file;
                    file_put_contents($file, $image_base64); 
                }
        	}if(!empty($pan_proof)){
        	    $target = BASEPATH.'../images/kyc/';
                $image_arr = array();
                $image_parts = explode(";base64,",$pan_proof);
                $image_type_aux = explode("image/", $image_parts[0]);
                if(!empty($image_type_aux[1])){
                    $image_type = $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);
                    $panimgname =  uniqid() . '.'.$image_type;
                    $file = $target.$panimgname;
                    $image_arr = $file;
                    file_put_contents($file, $image_base64); 
                }
        	}
        	if(!empty($panimgname) AND !empty($aadherimgname)  AND !empty($aadherimgnameback)){
        	    $check_data = $this->db->where('member_id',$user_id)->get('tbl_kyc')->num_rows();
        	    if($check_data == 0){
        	        $submit_data = array(
        	           'member_id' => isset($user_id) ? $user_id :'',
        	           'aadhar' => isset($aadhar) ? $aadhar :'',
        	           'pan' => isset($pan) ? $pan :'',
        	           'aadhar_proof' => isset($aadherimgname) ? $aadherimgname :'',
        	           'aadhar_proof_back' => isset($aadherimgnameback) ? $aadherimgnameback :'',
        	           'pan_proof' => isset($panimgname) ? $panimgname :'',
        	           'added_date' => date('Y-m-d h:i:s'),
        	            );
        	       $this->db->insert('tbl_kyc',$submit_data);
        	        $output = array(
                	     'status' => 'Success',
                	     'message' => 'KYC submission successfully',
                	     'data' => ''
                	   );
            	}else{
            	     $submit_data = array(
        	           'member_id' => isset($user_id) ? $user_id :'',
        	           'aadhar' => isset($aadhar) ? $aadhar :'',
        	           'pan' => isset($pan) ? $pan :'',
        	           'aadhar_proof' => isset($aadherimgname) ? $aadherimgname :'',
        	           'pan_proof' => isset($panimgname) ? $panimgname :'',
        	           'added_date' => date('Y-m-d h:i:s'),
        	            );
        	        $this->db->where('member_id',$user_id)->update('tbl_kyc',$submit_data);
        	        $output = array(
                	     'status' => 'Success',
                	     'message' => 'KYC updation successfully',
                	     'data' => ''
                	   );
            	}
        	}else{
        	    if(empty($aadherimgname) AND !empty($panimgname)){
        	        $output = array(
                	     'status' => 'Failure',
                	     'message' => 'Aadhar proof rejected'
                     );
        	    }elseif(empty($panimgname) AND !empty($aadherimgname)){
        	        $output = array(
                	     'status' => 'Failure',
                	     'message' => 'Pan proof rejected'
                     );
        	    }else{
        	        $output = array(
                	     'status' => 'Failure',
                	     'message' => 'Aadhar and pan proof rejected'
                     );
        	    }
        	}
    	}else{
    	    $output = array(
            	     'status' => 'Failure',
            	     'message' => 'You are not registered for this event !'
                 );
    	}
    
    	echo json_encode($output);die;
    }
    
    public function UserCurrentAuction(){
        $data =  json_decode($this->data);
    	$member_id = isset($data->member_id) ? $data->member_id : '';
    	
    	$data = $this->db->select('plan_id,group_id')->where('member_id',$member_id)->where('slot_status','assigned')->get('tbl_orders')->result_array();
    	if(!empty($data)){
    	    $input = array_map("unserialize", array_unique(array_map("serialize", $data)));
            $auction_detail = array();
            foreach($input as $keys => $values){
                $auction_data = $this->db->where('plan_id',$values['plan_id'])->where('group_id',$values['group_id'])->get('tbl_auction')->result_array();
                if(!empty($auction_data)){
                    foreach($auction_data as $key => $value){
                        $auction_detail[] = $value;
                    }
                }
            }
            if(!empty($auction_data)){
                $output = array(
            	     'status' => 'Success',
            	     'message' => 'Available auction fetch successfully',
            	     'data' => $auction_data
            	   );
            }else{
                $output = array(
            	     'status' => 'Failure',
            	     'message' => 'no current available auction'
                 ); 
            }
    	}else{
    	     $output = array(
            	     'status' => 'Failure',
            	     'message' => 'No buy plan for available auction '
                 );
    	}
        echo json_encode($output);die;
    }
    
    public function UserCheckNewPlan(){
        $data =  json_decode($this->data);
    	$member_id = isset($data->member_id) ? $data->member_id : '';
    	
    	$data = $this->db->get('tbl_plans')->result_array();
    	$plan_details = array();
    	if(!empty($data)){
    	    foreach($data as $keys => $values){
    	        $check_order = $this->db->where('plan_id',$values['plan_id'])->where('member_id',$member_id)->where('slot_status','assigned')->get('tbl_orders')->row_array();
    	        if(empty($check_order)){
    	            $plan_details[] = $values;
    	        }
    	    }
    	    if(!empty($plan_details)){
    	       $output = array(
            	     'status' => 'Success',
            	     'message' => 'New plans fetch successfully',
            	     'data' => $plan_details
            	   ); 
    	    }else{
    	         $output = array(
            	     'status' => 'Failure',
            	     'message' => 'No plan available '
                 );
    	    }
    	}else{
    	    $output = array(
            	     'status' => 'Failure',
            	     'message' => 'No plan available '
                 );
    	}
    	echo json_encode($output);die;
    }
    
    public function getplandetail(){
        $data =  json_decode($this->data);
    	$plan_id = isset($data->plan_id) ? $data->plan_id : '';
    	$get_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
    	if(!empty($get_detail)){
    	       $output = array(
            	     'status' => 'Success',
            	     'message' => 'plans fetch successfully',
            	     'data' => $get_detail
            	   ); 
    	    }else{
    	         $output = array(
            	     'status' => 'Failure',
            	     'message' => 'No available plan'
                 );
    	 }
    	 echo json_encode($output);die;
    }
    
    public function getUserTransactionDetail(){
        $data =  json_decode($this->data);
        $transaction_id = $data->transaction_id;
        $get_detail = $this->db->where('transaction_id',$transaction_id)->get('tbl_transactions')->row_array();
        if(!empty($get_detail)){
    	       $output = array(
            	     'status' => 'Success',
            	     'message' => 'Data fetch successfully',
            	     'data' => $get_detail
            	   ); 
    	    }else{
    	         $output = array(
            	     'status' => 'Failure',
            	     'message' => 'No available '
                 );
    	 }
    	 echo json_encode($output);die;
    }
    
    public function uplodeUserprofile(){
        $data =  json_decode($this->data);
    	$profile = isset($data->profile) ? $data->profile : '';
    	$cover_profile = isset($data->cover_profile) ? $data->cover_profile : '';
    	$member_id = isset($data->member_id) ? $data->member_id : '';
    	
    	$get_detail = $this->db->select('profile,profile_cover')->where('member_id',$member_id)->get('tbl_members')->row_array(); 
        
        if(!empty($profile)){
        	   $target = BASEPATH.'../images/user_profile/';
                $image_arr = array();
                $image_parts = explode(";base64,",$profile);
                $image_type_aux = explode("image/", $image_parts[0]);
                if(!empty($image_type_aux[1])){
                    $image_type = $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);
                    $profile_name =  uniqid() . '.'.$image_type;
                    $file = $target.$profile_name;
                    $image_arr = $file;
                    file_put_contents($file, $image_base64); 
                }
                $upload_profile = array(
            	    'profile' => isset($profile_name) ? $profile_name :''
            	    );
            	    
            	    $this->db->where('member_id',$member_id)->update('tbl_members',$upload_profile);
            	    $profile_msg = 'profile image update ';
            	
            	
        	}if(!empty($cover_profile)){
        	   $target = BASEPATH.'../images/user_profile/';
                $image_arr = array();
                $image_parts = explode(";base64,",$cover_profile);
                $image_type_aux = explode("image/", $image_parts[0]);
                if(!empty($image_type_aux[1])){
                    $image_type = $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);
                    $cover_profile_name =  uniqid() . '.'.$image_type;
                    $file = $target.$cover_profile_name;
                    $image_arr = $file;
                    file_put_contents($file, $image_base64); 
                }
                	$upload_profile_cover = array(
                	    'profile_cover' => isset($cover_profile_name) ? $cover_profile_name :''
                	    );
                	
                	    $this->db->where('member_id',$member_id)->update('tbl_members',$upload_profile_cover);
                	    $cover_msg = 'cover image update ';
        	}
        	if(!empty($cover_msg) || !empty($profile_msg)){
        	     $output = array(
            	     'status' => 'Success',
            	     'message' => 'upload successfully',
            	     'data' => []
            	   );
        	}else{
        	    $output = array(
            	     'status' => 'Failure',
            	     'message' => 'Something went worng',
            	     'data' => []
                 );
        	}
        	echo json_encode($output);die;
    }
    public function getprofile(){
        $data =  json_decode($this->data);
    	$member_id = isset($data->member_id) ? $data->member_id : '';
    	$get_detail = $this->db->select('profile,profile_cover')->where('member_id',$member_id)->get('tbl_members')->row_array(); 
    	$path = "http://premad.in/chitfund_api2/system/../images/user_profile/";
    	if(!empty($get_detail)){
        	     $output = array(
            	     'status' => 'Success',
            	     'message' => 'fetch successfully',
            	     'data' => $get_detail,
            	     'path' => $path
            	   );
        	}else{
        	    $output = array(
            	     'status' => 'Failure',
            	     'message' => 'No data found',
            	     'data' => []
                 );
        	}
        	echo json_encode($output);die;
    }
    
    public function getslotuserforauction(){
        $data =  json_decode($this->data);
    	$auction_id = isset($data->auction_id) ? $data->auction_id : '';
    	$member_id = isset($data->member_id) ? $data->member_id : '';
    	
        $auction_detail = $this->db->where('auction_id',$auction_id)->get('tbl_auction')->row_array();
        $getsloat = $this->db->select('slot_number')->where('plan_id',$auction_detail['plan_id'])->where('group_id',$auction_detail['group_id'])->where('member_id',$member_id)->get('tbl_orders')->result_array();
        	if(!empty($getsloat)){
        	     $output = array(
            	     'status' => 'Success',
            	     'message' => 'fetch successfully',
            	     'data' => $getsloat,
            	   );
        	}else{
        	    $output = array(
            	     'status' => 'Failure',
            	     'message' => 'No data found',
            	     'data' => []
                 );
        	}
        	echo json_encode($output);die;
    }
    
    public function getuserchit(){
         $data =  json_decode($this->data);
    	$member_id = isset($data->member_id) ? $data->member_id : '';
    	$chit_data = $this->db->where('member_id',$member_id)->get('tbl_chits')->result_array();
    	if(!empty($chit_data)){
    	    $new_array = array();
    	    foreach($chit_data as $key=>$val){
    	        $plan_data = $this->db->where('plan_id',$val['plan_id'])->get('tbl_plans')->row_array();
    	        $val['plan_name'] = $plan_data['plan_name'];
    	        $val['plan_amount'] = $plan_data['plan_amount'];
    	        $new_array[] = $val;
    	    }
        	     $output = array(
            	     'status' => 'Success',
            	     'message' => 'fetch successfully',
            	     'data' => $new_array,
            	   );
        	}else{
        	    $output = array(
            	     'status' => 'Failure',
            	     'message' => 'No data found',
            	     'data' => []
                 );
        	}
        	echo json_encode($output);die;
    }

	public function getuserforhandoverschit(){
		$data =  json_decode($this->data);
	   $member_id = isset($data->member_id) ? $data->member_id : '';
	//    $chit_data = $this->db->where('member_id',$member_id)->get('tbl_chits')->result_array();
	   $chit_data = $this->db->where('is_hand_over!=','yes')->where('member_id',$member_id)->get('tbl_chits')->result_array();
	   if(!empty($chit_data)){
		   $new_array = array();
		   foreach($chit_data as $key=>$val){
			   $plan_data = $this->db->where('plan_id',$val['plan_id'])->get('tbl_plans')->row_array();
			   $val['plan_name'] = $plan_data['plan_name'];
			   $val['plan_amount'] = $plan_data['plan_amount'];
			   $new_array[] = $val;
		   }
				$output = array(
					'status' => 'Success',
					'message' => 'fetch successfully',
					'data' => $new_array,
				  );
		   }else{
			   $output = array(
					'status' => 'Failure',
					'message' => 'No data found',
					'data' => []
				);
		   }
		   echo json_encode($output);die;
   }
    
    public function subscribeRequestBymember(){
        $data =  json_decode($this->data);
        $member_id = isset($data->member_id) ? $data->member_id : '';
        $plan_id = isset($data->plan_id) ? $data->plan_id : '';
        if(!empty($member_id) && !empty($plan_id)){
            $get_check = $this->db->where('plan_id',$plan_id)->where('member_id',$member_id)->get('tbl_notfy_sub_plan')->num_rows();
            $member_detail = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
            $plan_detail = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
            if($get_check == 0){
                $data = array(
                    'plan_id' => $plan_id ,
                    'member_id' => $member_id ,
                    'member_name' => isset($member_detail['name']) ? $member_detail['name'] :'',
                    'plan_name' => isset($plan_detail['plan_name']) ? $plan_detail['plan_name'] :'',
                    'mobile' => isset($member_detail['mobile']) ? $member_detail['mobile'] :'', 
                    'email' => isset($member_detail['email']) ? $member_detail['email'] :'',
                    'count' => 1,
                    'added_date' => date('y-m-d H:i:s')
                    );
                $this->db->insert('tbl_notfy_sub_plan',$data);
                 $output = array(
            	     'status' => 'Success',
            	     'message' => 'request successfully',
            	     'data' => '',
            	   );
            }else{
                $get_check = $this->db->where('plan_id',$plan_id)->where('member_id',$member_id)->get('tbl_notfy_sub_plan')->row_array();
                $d = array(
                    'count' => $get_check['count'] + 1,
                    );
                $get_check = $this->db->where('plan_id',$plan_id)->where('member_id',$member_id)->update('tbl_notfy_sub_plan',$d);
                $output = array(
            	     'status' => 'Success',
            	     'message' => 'request successfully',
            	     'data' => '',
            	   );
            }
        }else{
             $output = array(
            	     'status' => 'Failure',
            	     'message' => 'No data found',
            	     'data' => ''
                 );
        }
        echo json_encode($output);die;
    }

	public function get_subdcriber_id(){
        $code = 'MYM'.rand(10000,99999);
        $check_exist = $this->db->where('subscriber_id',$code)->get('tbl_members')->num_rows();
        if($check_exist != 0){
            return $this->get_subdcriber_id();
        }else{
            return $code;
        }
    }

	 function create_plan_general_legder($plan_id){

		$plan_data = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
		$admission_fee =   $plan_data['admission_fee'] * $plan_data['plan_amount'] / 100 ;

		$company_member = $this->db->where('user_type','company')->get('tbl_members')->row_array();
		
		$groups = $this->db->where('plan_id',$plan_id)->get('tbl_groups')->result_array();
		foreach($groups as $key=>$val){
			$assign_order_id = $this->db->where('plan_id',$plan_id)->where('group_id',$val['group_id'])->get('tbl_orders')->row_array();
			$ledgerdata1 = array(
				'insert_id'=> '1',
				'c_code' => '505',
				'plan_id' => $plan_id,
				'category_desc' => 'Prized Money due',
				'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
				'transaction_mode' => 'J1 - Internal',
				'transaction_type' => 'Prize disbursal',
				'transaction_description' => 'Gross Prize Amt Due',
				'amount' => isset($plan_data['plan_amount']) ? $plan_data['plan_amount'] : '',
				'dr_cr' =>'Dr',
				'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
				'account_name' => isset($company_member['name']) ? $company_member['name'] : '',
				'added_date' => date('Y-m-d h:i:s'),
				'account_description' => $this->getGlAccount('1003'),
				'gl_account' => '1003',
				'type' => 'Payment',
				'user'=> 'Senthil',
				'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
			);
			$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
			 
			$ledgerdata2 = array(
				'insert_id'=> '1',
				'c_code' => '505',
				'plan_id' => $plan_id,
				'category_desc' => 'Prized Money due',
				'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
				'transaction_mode' => 'J1 - Internal',
				'transaction_type' => 'Prize disbursal',
				'transaction_description' => 'Gross Prize Amt Due',
				'amount' => isset($plan_data['plan_amount']) ? $plan_data['plan_amount'] : '',
				'dr_cr' =>'Cr',
				'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
				'account_name' => isset($company_member['name']) ? $company_member['name'] : '',
				'added_date' => date('Y-m-d h:i:s'),
				'account_description' => $this->getGlAccount('1002'),
				'gl_account' => '1002',
				'type' => 'Payment',
				'user'=> 'Senthil',
				'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
			);
			$this->db->insert('tbl_general_ledger_master',$ledgerdata2);

			//subscription due 
			$ledgerdata1 = array(
                    'insert_id'=> '1',
					'plan_id' => $plan_id,
                    'c_code' => '400',
                    'category_desc' => 'Subscription',
                    'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
                    'transaction_mode' => 'J1 - Internal',
                    'transaction_type' => 'Subscription Due',
                    'transaction_description' => 'Subscribers A/c',
                    'amount' => isset($plan_data['emi']) ? $plan_data['emi'] : '',
                    'dr_cr' =>'Dr',
                    'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
                    'account_name' => isset($company_member['name']) ? $company_member['name'] : '',
                    'added_date' => date('Y-m-d h:i:s'),
                    'account_description' => $this->getGlAccount('1002'),
                    'gl_account' => '1002',
                    'type' => 'Payment',
					'user' =>'Senthil',
					'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
                );
                  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
                  $ledgerdata2 = array(
                    'insert_id'=> '1',
                    'c_code' => '400',
                    'category_desc' => 'Subscription',
                    'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
                    'transaction_mode' => 'J1 - Internal',
                    'transaction_type' => 'Subscription Due',
                    'transaction_description' => 'Plan A/c',
					'amount' => isset($plan_data['emi']) ? $plan_data['emi'] : '',
                    'dr_cr' =>'Cr',
                    'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
                    'account_name' => isset($company_member['name']) ? $company_member['name'] : '',
                    'added_date' => date('Y-m-d h:i:s'),
                    'account_description' => $this->getGlAccount('1003'),
                    'gl_account' => '1003',
                    'type' => 'Payment',
					'user' =>'Senthil',
					'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
                );
                $this->db->insert('tbl_general_ledger_master',$ledgerdata2);

				//subscription due 
			
			$ledgerdata1 = array(
				'insert_id'=> '1',
				'c_code' => '203',
				'plan_id' => $plan_id,
				'category_desc' => 'Application Fee',
				'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
				'transaction_mode' => 'J1 - Internal',
				'transaction_type' => 'Plan Registration',
				'transaction_description' => '',
				'amount' => isset($admission_fee) ? $admission_fee : '',
				'dr_cr' =>'Dr',
				'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
				'account_name' => 'MYM Chit Fund Pvt Ltd',
				'added_date' => date('Y-m-d h:i:s'),
				'account_description' => $this->getGlAccount('1002'),
				'gl_account' => '1002',
				'type' => 'Payment',
				'user'=> 'Senthil',
				'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
			);
			$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
			 
			$ledgerdata2 = array(
				'insert_id'=> '1',
				'c_code' => '203',
				'plan_id' => $plan_id,
				'category_desc' => 'Application Fee',
				'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
				'transaction_mode' => 'J1 - Internal',
				'transaction_type' => 'Plan Registration',
				'transaction_description' => '',
				'amount' => isset($admission_fee) ? $admission_fee : '',
				'dr_cr' =>'Cr',
				'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
				'account_name' => 'MYM Chit Fund Pvt Ltd',
				'added_date' => date('Y-m-d h:i:s'),
				'account_description' => $this->getGlAccount('3400'),
				'gl_account' => '3400',
				'type' => 'Payment',
				'user'=> 'Senthil',
				'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
			);
			$this->db->insert('tbl_general_ledger_master',$ledgerdata2);

			$ledgerdata1 = array(
				'insert_id'=> '1',
				'c_code' => '203',
				'plan_id' => $plan_id,
				'category_desc' => 'Application Fee',
				'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
				'transaction_mode' => 'B1 - Cheque',
				'transaction_type' => 'Plan Registration',
				'transaction_description' => '',
				'amount' => isset($admission_fee) ? $admission_fee : '',
				'dr_cr' =>'Dr',
				'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
				'account_name' => 'MYM Chit Fund Pvt Ltd',
				'added_date' => date('Y-m-d h:i:s'),
				'account_description' => $this->getGlAccount('3400'),
				'gl_account' => '3400',
				'type' => 'Payment',
				'user'=> 'Senthil',
				'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
			);
			$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
			 
			$ledgerdata2 = array(
				'insert_id'=> '1',
				'c_code' => '203',
				'plan_id' => $plan_id,
				'category_desc' => 'Application Fee',
				'plan_name' => isset($plan_data['plan_name']) ? $plan_data['plan_name'] :'',
				'transaction_mode' => 'B1 - Cheque',
				'transaction_type' => 'Plan Registration',
				'transaction_description' => '',
				'amount' => isset($admission_fee) ? $admission_fee : '',
				'dr_cr' =>'Cr',
				'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
				'account_name' => 'MYM Chit Fund Pvt Ltd',
				'added_date' => date('Y-m-d h:i:s'),
				'account_description' => $this->getGlAccount('4102'),
				'gl_account' => '4102',
				'type' => 'Payment',
				'user'=> 'Senthil',
				'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
			);
			$this->db->insert('tbl_general_ledger_master',$ledgerdata2);
		}
		
	 }

	 function AllotSlotToCompany($plan_id){
		$get_groups = $this->db->where('plan_id',$plan_id)->get('tbl_groups')->result_array();
		foreach($get_groups as $key=>$val){
			$order = $this->db->where('plan_id',$plan_id)->where('group_id',$val['group_id'])->get('tbl_orders')->row_array();
			$company_member = $this->db->where('user_type','company')->get('tbl_members')->row_array();
			$plan_data = $this->db->where('plan_id',$plan_id)->get('tbl_plans')->row_array();
			if(!empty($company_member)){
				$data = [
					'slot_status'=>'assigned',
					'member_id' => isset($company_member['member_id']) ? $company_member['member_id'] :'',
					'member_name' => isset($company_member['name']) ? $company_member['name'] :'',
				];
				if(isset($order['order_id']) && $order['order_id'] != ''){
					$this->db->where('order_id',$order['order_id'])->update('tbl_orders',$data);
					$data = array(
						'plan_id' => isset($plan_id) ? $plan_id : '',//
						'group_id' =>  isset($val['group_id']) ? $val['group_id'] : '',//
						'auction_id' =>  isset($auction_id) ? $auction_id : '',//
						'member_id' =>  isset($company_member['member_id']) ? $company_member['member_id'] : '',//
						'return_chit_amount' =>  isset($plan_data['plan_amount']) ? $plan_data['plan_amount'] : '',//
						'total_amount_paid' => isset($total_amount_paid) ? $total_amount_paid : '0',//
						'total_amount_due' => isset($total_amount_due) ? $total_amount_due : '',//
						'chit_amount' => isset($chit_amount) ? $chit_amount : $plan_data['plan_amount'],//
						'forgo_amount' => isset($forgo_amount) ? $forgo_amount : '0',//
						'is_on_EMI' => isset($is_on_EMI) ? $is_on_EMI : '0',//
						'emi_amount' => isset($emi_amount) ? $emi_amount : $plan_data['emi'],//
						'total_emi' => isset($total_emi) ? $total_emi : $plan_data['tenure'],//
						'due_emi' => isset($due_emi) ? $due_emi : $plan_data['tenure'],//
						'emi_paid' => isset($emi_paid) ? $emi_paid : '0',//
						'is_active' => isset($is_active) ? $is_active : '',	//
						'slot_number' => isset($order['slot_number']) ? $order['slot_number'] : '',
						'chit_month' => date("M,Y"),
						'added_date' => date('Y-m-d h:i:s')
					); 
					$this->db->insert('tbl_chits',$data);
				}
			}
		}
	 }

	 function getGlAccount($code){
		$data = $this->db->where('code',$code)->get('gl_account')->row_array();
		return isset($data['name']) ? $data['name'] :'-';
	 }

	function create_final_bid_ledger($chit_id){
		$chit_detail = $this->db->where('chit_id',$chit_id)->get('tbl_chits')->row_array();
		$plandetails = $this->db->where('plan_id',$chit_detail['plan_id'])->get('tbl_plans')->row_array();
		$mamberdetail = $this->db->where('member_id',$chit_detail['member_id'])->get('tbl_members')->row_array();
		$ledgerdata1 = array(
				'insert_id'=> '1',
				'plan_id' => isset($chit_detail['plan_id']) ? $chit_detail['plan_id'] :'',
				'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
				'c_code' => '503',
				'category_desc' => 'Bid_Amount',
				'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
				'transaction_mode' => 'J1 - Internal',
				'transaction_type' => 'Bid_Amount',
				'transaction_description' => 'Final Bid Amt',
				'amount' => isset($chit_detail['forgo_amount']) ? $chit_detail['forgo_amount'] : '',
				'dr_cr' =>'Dr',
				'sub_id' => isset($mamberdetail['subscriber_id']) ? $mamberdetail['subscriber_id'] : '',
				'account_name' => isset($mamberdetail['name']) ? $mamberdetail['name'] : '',
				'added_date' => date('Y-m-d h:i:s'),
				'account_description' => $this->getGlAccount('1003'),
				'gl_account' => '1003',
				'type' => 'Payment',
				'user' => 'Senthil',
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		$ledgerdata2 = array(
			'insert_id'=> 1,
			'plan_id' => isset($chit_detail['plan_id']) ? $chit_detail['plan_id'] :'',
			'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
			'c_code' => '503',
			'category_desc' => 'Bid_Amount',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'Bid_Amount',
			'transaction_description' => 'Final Bid Amt',
			'amount' => isset($chit_detail['forgo_amount']) ? $chit_detail['forgo_amount'] : '',
			'dr_cr' =>'Cr',
			'sub_id' => isset($mamberdetail['subscriber_id']) ? $mamberdetail['subscriber_id'] : '',
			'account_name' => isset($mamberdetail['name']) ? $mamberdetail['name'] : '',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('1004'),
			'gl_account' => '1004',
			'type' => 'Payment',
			'user' => 'Senthil',
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata2);
		// subscrition due ledger
		$total_group_members = $this->db->where('plan_id',$chit_detail['plan_id'])->where('group_id',$chit_detail['group_id'])->where('slot_status','assigned')->get('tbl_orders')->result_array();
		foreach($total_group_members as $key=>$val){
			$member_data_2 = $this->get_member_detail($val['member_id']);
			$ledgerdata1 = array(
				'insert_id'=> '1',
				'c_code' => '400',
				'category_desc' => 'Subscription',
				'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
				'transaction_mode' => 'J1 - Internal',
				'transaction_type' => 'Subscription Due',
				'transaction_description' => 'Subscribers A/c',
				'amount' => isset($plandetails['emi']) ? $plandetails['emi'] : '',
				'dr_cr' =>'Dr',
				'sub_id' => isset($member_data_2['subscriber_id']) ? $member_data_2['subscriber_id'] : '',
				'account_name' => isset($member_data_2['name']) ? $member_data_2['name'] : '',
				'added_date' => date('Y-m-d h:i:s'),
				'account_description' => $this->getGlAccount('1002'),
				'gl_account' => '1002',
				'type' => 'Payment',
				'user' =>'Senthil',
				'slot_number' => isset($val['slot_number']) ? $val['slot_number']: '',
			);
			 $insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		}
		$ledgerdata2 = array(
			'insert_id'=> '1',
			'c_code' => '400',
			'category_desc' => 'Subscription',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'Subscription Due',
			'transaction_description' => 'Plan A/c',
			'amount' => isset($plandetails['plan_amount']) ? $plandetails['plan_amount'] : '',
			'dr_cr' =>'Cr',
			// 'sub_id' => isset($member_data['subscriber_id']) ? $member_data['subscriber_id'] : '',
			// 'account_name' => isset($member_data['name']) ? $member_data['name'] : '',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('1003'),
			'gl_account' => '1003',
			'type' => 'Payment',
			'user' =>'Senthil',
			// 'slot_number' => $slot_number,
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata2);

		// prized money ledger

		$bid_amount = $plandetails['plan_amount'] - $chit_detail['forgo_amount'];
			$ledgerdata1 = array(
				'insert_id'=> '1',
				'plan_id' => isset($chit_detail['plan_id']) ? $chit_detail['plan_id'] :'',
				'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
				'c_code' => '503',
				'category_desc' => 'Prized Money due',
				'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
				'transaction_mode' => 'J1 - Internal',
				'transaction_type' => 'Prize disbursal',
				'transaction_description' => 'Gross Prize Amt Due',
				'amount' => isset($bid_amount) ? $bid_amount : '',
				'dr_cr' =>'Dr',
				'sub_id' => isset($mamberdetail['subscriber_id']) ? $mamberdetail['subscriber_id'] : '',
				'account_name' => isset($mamberdetail['name']) ? $mamberdetail['name'] : '',
				'added_date' => date('Y-m-d h:i:s'),
				'account_description' => $this->getGlAccount('1003'),
				'gl_account' => '1003',
				'type' => 'Payment',
				'user' => 'Senthil',
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		$ledgerdata2 = array(
			'insert_id'=> 1,
			'plan_id' => isset($chit_detail['plan_id']) ? $chit_detail['plan_id'] :'',
			'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
			'c_code' => '503',
			'category_desc' => 'Prized Money due',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'Prize disbursal',
			'transaction_description' => 'Gross Prize Amt Due',
			'amount' => isset($bid_amount) ? $bid_amount : '',
			'dr_cr' =>'Cr',
			'sub_id' => isset($mamberdetail['subscriber_id']) ? $mamberdetail['subscriber_id'] : '',
			'account_name' => isset($mamberdetail['name']) ? $mamberdetail['name'] : '',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('1002'),
			'gl_account' => '1002',
			'type' => 'Payment',
			'user' => 'Senthil',
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata2);

		// plan registration 
		$admission_fee =   $plandetails['admission_fee'] * $plandetails['plan_amount'] / 100 ;
		$ledgerdata1 = array(
			'insert_id'=> '1',
			'c_code' => '203',
			'plan_id' => $plan_id,
			'category_desc' => 'Application Fee',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'Plan Registration',
			'transaction_description' => '',
			'amount' => isset($admission_fee) ? $admission_fee : '',
			'dr_cr' =>'Dr',
			// 'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
			'account_name' => 'MYM Chit Fund Pvt Ltd',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('1002'),
			'gl_account' => '1002',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		 
		$ledgerdata2 = array(
			'insert_id'=> '1',
			'c_code' => '203',
			'plan_id' => $plan_id,
			'category_desc' => 'Application Fee',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'Plan Registration',
			'transaction_description' => '',
			'amount' => isset($admission_fee) ? $admission_fee : '',
			'dr_cr' =>'Cr',
			// 'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
			'account_name' => 'MYM Chit Fund Pvt Ltd',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('3400'),
			'gl_account' => '3400',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata2);

		$ledgerdata1 = array(
			'insert_id'=> '1',
			'c_code' => '203',
			'plan_id' => $plan_id,
			'category_desc' => 'Application Fee',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'B1 - Cheque',
			'transaction_type' => 'Plan Registration',
			'transaction_description' => '',
			'amount' => isset($admission_fee) ? $admission_fee : '',
			'dr_cr' =>'Dr',
			// 'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
			'account_name' => 'MYM Chit Fund Pvt Ltd',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('3400'),
			'gl_account' => '3400',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		 
		$ledgerdata2 = array(
			'insert_id'=> '1',
			'c_code' => '203',
			'plan_id' => $plan_id,
			'category_desc' => 'Application Fee',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'B1 - Cheque',
			'transaction_type' => 'Plan Registration',
			'transaction_description' => '',
			'amount' => isset($admission_fee) ? $admission_fee : '',
			'dr_cr' =>'Cr',
			// 'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
			'account_name' => 'MYM Chit Fund Pvt Ltd',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('4102'),
			'gl_account' => '4102',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata2);

		// commition share account

		$ledgerdata1 = array(
			'insert_id'=> '1',
			'c_code' => '101',
			'plan_id' => $plan_id,
			'category_desc' => 'Commission on Plan',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'MYM_Comm_due',
			'transaction_description' => 'MYM_Comm_due',
			'amount' => isset($plandetails['min_bid_amount']) ? $plandetails['min_bid_amount'] : '',
			'dr_cr' =>'Dr',
			// 'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
			'account_name' => 'MYM Chit Fund Pvt Ltd',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('1004'),
			'gl_account' => '1004',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		 
		$ledgerdata2 = array(
			'insert_id'=> '1',
			'c_code' => '101',
			'plan_id' => $plan_id,
			'category_desc' => 'Commission on Plan',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'MYM_Comm_due',
			'transaction_description' => 'MYM_Comm_due',
			'amount' => isset($plandetails['min_bid_amount']) ? $plandetails['min_bid_amount'] : '',
			'dr_cr' =>'Cr',
			// 'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
			'account_name' => 'MYM Chit Fund Pvt Ltd',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('3200'),
			'gl_account' => '3200',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata2);

		// GST - Foreman Comission

		$forman_commition = $plandetails['min_bid_amount'];
		$gst = $this->db->get('tbl_gst_master')->row_array();
		$gst_per = isset($gst['amount']) ? $gst['amount'] : 17 ;

		$forman_gst = $forman_commition * $gst_per / 100;

		$ledgerdata1 = array(
			'insert_id'=> '1',
			'c_code' => '705',
			'plan_id' => $plan_id,
			'category_desc' => 'GST - Foreman Comission',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'GST',
			'transaction_description' => '',
			'amount' => isset($forman_gst) ? $forman_gst : '',
			'dr_cr' =>'Dr',
			'sub_id' => isset($mamberdetail['subscriber_id']) ? $mamberdetail['subscriber_id'] : '',
			'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
			'account_name' => isset($mamberdetail['name']) ? $mamberdetail['name'] : '',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('1002'),
			'gl_account' => '1002',
			'type' => 'Payment',
			'user'=> 'Senthil',
			'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		 
		$ledgerdata2 = array(
			'insert_id'=> '1',
			'c_code' => '705',
			'plan_id' => $plan_id,
			'category_desc' => 'GST - Foreman Comission',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'GST',
			'transaction_description' => '',
			'amount' => isset($forman_gst) ? $forman_gst : '',
			'dr_cr' =>'Cr',
			'sub_id' => isset($mamberdetail['subscriber_id']) ? $mamberdetail['subscriber_id'] : '',
			'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
			'account_name' => isset($mamberdetail['name']) ? $mamberdetail['name'] : '',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('2500'),
			'gl_account' => '2500',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata2);

		// GST - Registration Fees
		$forman_commition = isset($plandetails['admission_amount']) ? $plandetails['admission_amount'] : '';
		$gst = $this->db->get('tbl_gst_master')->row_array();
		$gst_per = isset($gst['amount']) ? $gst['amount'] : 17 ;
		$forman_gst = $forman_commition * $gst_per / 100;

		$ledgerdata1 = array(
			'insert_id'=> '1',
			'c_code' => '706',
			'plan_id' => $plan_id,
			'category_desc' => 'GST - Registration Fees',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'GST',
			'transaction_description' => '',
			'amount' => isset($forman_gst) ? $forman_gst : '',
			'dr_cr' =>'Dr',
			'sub_id' => isset($mamberdetail['subscriber_id']) ? $mamberdetail['subscriber_id'] : '',
			'account_name' => isset($mamberdetail['name']) ? $mamberdetail['name'] : '',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('1002'),
			'gl_account' => '1002',
			'type' => 'Payment',
			'user'=> 'Senthil',
			'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
		);
		$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		 
		$ledgerdata2 = array(
			'insert_id'=> '1',
			'c_code' => '706',
			'plan_id' => $plan_id,
			'category_desc' => 'GST - Registration Fees',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'GST',
			'transaction_description' => '',
			'amount' => isset($forman_gst) ? $forman_gst : '',
			'dr_cr' =>'Cr',
			'sub_id' => isset($mamberdetail['subscriber_id']) ? $mamberdetail['subscriber_id'] : '',
			'slot_number' => isset($chit_detail['slot_number']) ? $chit_detail['slot_number'] :'',
			'account_name' => isset($mamberdetail['name']) ? $mamberdetail['name'] : '',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('2500'),
			'gl_account' => '2500',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata2);




		// Total Monthly Div Due to All Subscribers

		$divident = $chit_detail['forgo_amount'] - $plandetails['min_bid_amount'];
		$each_divident = $divident / $plandetails['total_subscription'];

		$ledgerdata1 = array(
			'insert_id'=> '1',
			'c_code' => '504',
			'plan_id' => $plan_id,
			'category_desc' => 'Dividend on Subscriptions',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'Dividend',
			'transaction_description' => 'Total Monthly Div Due to All Subscribers',
			'amount' => isset($divident) ? $divident : '',
			'dr_cr' =>'Dr',
			// 'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
			'account_name' => 'MYM Chit Fund Pvt Ltd',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('1004'),
			'gl_account' => '1004',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		 
		$ledgerdata2 = array(
			'insert_id'=> '1',
			'c_code' => '504',
			'plan_id' => $plan_id,
			'category_desc' => 'Dividend on Subscriptions',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'Dividend',
			'transaction_description' => 'Total Monthly Div Due to All Subscribers',
			'amount' => isset($divident) ? $divident : '',
			'dr_cr' =>'Cr',
			// 'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
			'account_name' => 'MYM Chit Fund Pvt Ltd',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('2200'),
			'gl_account' => '2200',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$this->db->insert('tbl_general_ledger_master',$ledgerdata2);

		// Dividend Allocation by plan to Individual subscribers

		
		$ledgerdata1 = array(
			'insert_id'=> '1',
			'c_code' => '501',
			'plan_id' => $plan_id,
			'category_desc' => 'Dividend Disbursal',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'Dividend',
			'transaction_description' => 'Dividend Allocation by plan to Individual subscribers',
			'amount' => isset($divident) ? $divident : '',
			'dr_cr' =>'Dr',
			// 'sub_id' => isset($company_member['subscriber_id']) ? $company_member['subscriber_id'] : '',
			'account_name' => 'MYM Chit Fund Pvt Ltd',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('2200'),
			'gl_account' => '2200',
			'type' => 'Payment',
			'user'=> 'Senthil',
			// 'slot_number' => isset($assign_order_id['slot_number']) ? $assign_order_id['slot_number'] :'',
		);
		$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		$total_group_members = $this->db->where('plan_id',$chit_detail['plan_id'])->where('slot_status','assigned')->get('tbl_orders')->result_array();
		foreach($total_group_members as $key=>$val){
			$member_data_2 = $this->get_member_detail($val['member_id']);
			$ledgerdata1 = array(
				'insert_id'=> '1',
				'c_code' => '501',
				'category_desc' => 'Dividend Disbursal',
				'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
				'transaction_mode' => 'J1 - Internal',
				'transaction_type' => 'Dividend',
				'transaction_description' => 'Dividend Allocation by plan to Individual subscribers',
				'amount' => isset($each_divident) ? $each_divident : '',
				'dr_cr' =>'Cr',
				'sub_id' => isset($member_data_2['subscriber_id']) ? $member_data_2['subscriber_id'] : '',
				'account_name' => isset($member_data_2['name']) ? $member_data_2['name'] : '',
				'added_date' => date('Y-m-d h:i:s'),
				'account_description' => $this->getGlAccount('1002'),
				'gl_account' => '1002',
				'type' => 'Payment',
				'user' =>'Senthil',
				'slot_number' => isset($val['slot_number']) ? $val['slot_number']: '',
			);
			 $insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		}
	}

	function get_member_detail($member_id){
		$data = $this->db->where('member_id',$member_id)->get('tbl_members')->row_array();
		return $data;
	}

	public function PayDuesGeneralLedger($data,$bank_account_id,$payment_mode){
		
		if($payment_mode == 'Cash'){
			$transaction_mode = 'C1 - Cash';
			$gl_account = '1000';
		}else{
			$transaction_mode = 'B2 - Online transfer';
			$gl_account = '1011';

		}
		$emi_data = $this->db->where('emi_id',$data['id'])->get('tbl_emi')->row_array();
		$plandetails = $this->db->where('plan_id',$emi_data['plan_id'])->get('tbl_plans')->row_array();
		$member_data_2 = $this->get_member_detail($emi_data['member_id']);
		
		$ledgerdata1 = array(
			'insert_id'=> '1',
			'c_code' => '206',
			'plan_id' => $emi_data['plan_id'],
			'category_desc' => 'Subscription Money Received',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'Subscription Received',
			'transaction_description' => '',
			'amount' => isset($data['amount']) ? $data['amount'] :'',
			'dr_cr' =>'Dr',
			'sub_id' => isset($member_data_2['subscriber_id']) ? $member_data_2['subscriber_id'] : '',
			'account_name' => isset($member_data_2['name']) ? $member_data_2['name'] : '',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount($gl_account),
			'gl_account' => $gl_account,
			'type' => 'Payment',
			'user'=> 'Senthil',
			'slot_number' => isset($emi_data['slot_number']) ? $emi_data['slot_number'] :'',
		);
		$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
		$ledgerdata1 = array(
			'insert_id'=> '1',
			'c_code' => '206',
			'plan_id' => $emi_data['plan_id'],
			'category_desc' => 'Subscription Money Received',
			'plan_name' => isset($plandetails['plan_name']) ? $plandetails['plan_name'] :'',
			'transaction_mode' => 'J1 - Internal',
			'transaction_type' => 'Subscription Received',
			'transaction_description' => '',
			'amount' => isset($data['amount']) ? $data['amount'] :'',
			'dr_cr' =>'Cr',
			'sub_id' => isset($member_data_2['subscriber_id']) ? $member_data_2['subscriber_id'] : '',
			'account_name' => isset($member_data_2['name']) ? $member_data_2['name'] : '',
			'added_date' => date('Y-m-d h:i:s'),
			'account_description' => $this->getGlAccount('1002'),
			'gl_account' => '1002',
			'type' => 'Payment',
			'user' =>'Senthil',
			'slot_number' => isset($emi_data['slot_number']) ? $emi_data['slot_number'] :'',
		);
		$insert_data =  $this->db->insert('tbl_general_ledger_master',$ledgerdata1);
	}

	
	
}