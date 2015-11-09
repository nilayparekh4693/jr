<?php
//header('Access-Control-Allow-Origin: *');
//header('Access-Control-Allow-Credentials: true');
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Member extends MY_Controller {

    function Member() {
        parent :: __construct();
        $this->load->library('user_agent');
        $this->lang->load('login', $this->config->item('language'));
        
		
    }

    function index() {
    	$this->load->library('Mobile_Detect');
		$detect = new Mobile_Detect;
      	if(($detect->isMobile() || $detect->isNexusTablet()) && !$detect->isiPad()) {		
		    header('Location: http://m.singaporefriendfinder.com/');
		    exit;
		}
    }

    function login() {
    	//echo $_SERVER['HTTP_REFERER'];die;  
    	$this->load->library('Mobile_Detect');
		$detect = new Mobile_Detect;
      	if(($detect->isMobile() || $detect->isNexusTablet()) && !$detect->isiPad()) {		
		    header('Location: http://m.singaporefriendfinder.com/');
		    exit;
		}  		 	
        $this->user->loginRequired(false);
        $data['includefile'] = "login";
        $this->master_view('', $data);
    }

    function login_rest() {
        //$this->user->loginRequired(false);
        $data['includefile'] = "login_rest";
        $this->master_view('', $data);
    }

    # do login to the system

    function dologin() {
        $this->user->loginRequired(false);

        $this->load->library('form_validation');
        $this->form_validation->set_rules("username", "lang:username", "required|maxlength[50]");
        $this->form_validation->set_rules("password", "lang:password", "required|maxlength[50]");

        if ($this->form_validation->run() == FALSE) {
            redirect('member/login', 'location');
            return;
        }

        $username = $this->input->post('username');
        $password = $this->input->post('password');

        if ($this->user->login($username, $password, 'Member')) {            
            #insert log 
            $this->load->model('Userlog_model', 'userlog');
            #update last login ip
            $last_data = array(
                'user_lastlogin_ip' => $this->input->ip_address(),
                'user_live' => time()
            );
            //print_r($last_data);

            $this->db->update('auth_user', $last_data, array('user_auth_id' => $this->session->userdata('user_auth_id')));
            $this->userlog->insertUserLog();

            #directly navigate to url after login
            if ($this->session->userdata('enable_site_login')) {            	
                redirect($this->session->userdata('enable_site_login'));
                $this->session->unset_userdata('enable_site_login');
            }
            elseif($this->session->userdata('redirect_url'))
            {
            	redirect($this->session->userdata('redirect_url'));
				 $this->session->unset_userdata('redirect_url');
			}            
             else {
                redirect("member/myaccount", 'refresh');
            }
        }
         else {
            $this->session->set_flashdata('notification-error', $this->lang->line('invalid_login'));
            redirect('member/login', 'location');
            return;
        }
    }

    function forgotpwd() {
        $this->user->loginRequired(false);

        if ($_POST['btnSubmit'] == 'getNewPwd') {
            $this->load->library('form_validation');
            $this->form_validation->set_rules("username", "lang:username", "required|maxlength[50]");

            if ($this->form_validation->run() == FALSE) {
                redirect('home/index', 'location');
                return;
            }

            $username = $this->input->post('username');
            $userData = $this->user->userExists($username, 'Member');
            if ($userData) {
                $this->member_model->sendNewPwdMember($userData);

                $this->session->set_flashdata('notification', $this->lang->line('forgot_pwd_success_msg'));
                redirect('member/login', 'location');
            } else {
                $this->session->set_flashdata('notification-error', $this->lang->line('user_not_exists'));
                redirect('member/forgotpwd', 'location');
                return;
            }
        } else {
            $data['includefile'] = "forgot_pwd";
            $this->master_view('', $data);
        }
    }

    function logout() {
        //$data['js'] = array("sess_storage");
        //die;
        $this->user->logout();
        redirect('home/index', 'refresh');
    }

    function emailVarify() {
        $retData = $this->user->emailExists($_REQUEST['emaildata'], $_REQUEST['authid']);

        if ($retData)
            echo true;
        else
            echo false;
    }

    function zipVarify() {
        $this->load->model('zip_code_model');
        $retData = $this->zip_code_model->zipExists($_POST['retdata']);

        if ($retData)
            echo true;
        else
            echo false;
    }

    function profileNameVarify() {
        $retData = $this->user->profileNameExists($_POST['retdata']);

        if ($retData)
            echo true;
        else
            echo false;
    }

    function check_date() {
        $data = checkdate($_POST['month'], $_POST['day'], $_POST['year']);
        echo $data;
    }

    function register($step = 1) {
    	
		
        //print_r($_POST);
        //$this->load->library('user_agent');
        if ((!preg_match("/member\/register/", $this->agent->referrer())) && (!preg_match("/home/", $this->agent->referrer())) && ($this->agent->referrer() != base_url())) {
            $this->session->set_userdata('enable_site_login', $this->agent->referrer());
        }
        $this->user->loginRequired(false);

        if ($this->uri->segment(3) == 'step2') {
        	
        	if(!empty($_POST) || !empty($_POST["race"]))
            $step = 2;
			else
			{
				redirect("member/register/", 'refresh');
                return;
			}
			
				
            if (is_array($this->session->userdata('post-data'))) {
                $data['postData'] = $this->session->userdata('post-data');

                $_POST = array_merge($data['postData'], $_POST);
            }

            $pic_FileName = $this->member_model->uploadfile('my_profile_image', $_FILES['my_profile_image'], true, '', 0);

            if ($pic_FileName != '')
                $_POST['member_profile_image'] = $pic_FileName;

            $this->session->set_userdata('post-data', $_POST);
        }
        elseif ($this->uri->segment(3) == 'step3') {
        	
        	if($this->session->userdata('post-data'))
            $step = 3;
			else
			{
				redirect("member/register/step2", 'refresh');
                return;
			}
            

            if (is_array($this->session->userdata('post-data'))) {
                $data['postData'] = $this->session->userdata('post-data');

                $_POST = array_merge($data['postData'], $_POST);
            }


            $this->session->set_userdata('post-data', $_POST);
			//print_r($this->session->userdata('post-data'));die;

            $this->load->helper('captcha');

            // store image html code in a variable
            $data['captcha'] = $this->functions->creatCaptcha();

            // store the captcha word in a session

            $this->session->set_userdata('word', $_SESSION['temp-word']);
        } elseif ($this->uri->segment(3) == 'step4') {        	
        	
            $step = 4;
            /* echo '<pre>';
              print_r($_POST);die; */
            if (is_array($this->session->userdata('post-data'))) {

                $data['postData'] = $this->session->userdata('post-data');

                $_POST = array_merge($data['postData'], $_POST);
            }

            $this->session->set_userdata('post-data', $_POST);
        }

        if (is_array($this->session->userdata('post-data')))
            $data['postData'] = $this->session->userdata('post-data');

        /* if (!isset($data['postData']['looking_for']) && ($step != 1)) {
          redirect("member/register", 'refresh');
          } */

        if ($step == 4) {
        	        	
            if (!($this->input->post() && ($this->input->post('txtcaptcha') == $this->session->userdata('word')))) {
                $this->session->set_flashdata('notification-error', $this->lang->line('member_captcha_error'));
                redirect("member/register/step3", 'refresh');
                return;
            }
            //echo'<pre>';print_r($_POST);die;
            if (!isset($data['postData']['looking_for']) && ($step != 1)) {
                redirect("member/register", 'refresh');
            }

            $result = $this->member_model->insert();
			
            $this->session->unset_userdata('post-data');

            /* --------------------------- commented by pradip --------------- */
            /*  $friendArray = $this->session->userdata('sendFriendData');

              if (isset($friendArray['sendID'])) {
              $this->load->model('Billing_model', 'billing');
              $result = $this->billing->insertTellFriend($friendArray['send_member_id']);

              $this->member_model->updateByParam('member_send_friend', array('send_status' => 'Done'), array('send_id' => $friendArray['send_id']));
              } */
			$this->session->set_userdata('register_thanks', 'step3');
           	redirect("member/register_thanks", 'refresh');
        }

        if ($step == 1) {
            /* -------------------- commented by pradip------------ */
            /*  $friendArray = $this->session->userdata('sendFriendData');
              if (isset($friendArray['sendID'])) {
              $data['postData']['email'] = $friendArray['send_to_mail'];
              } */
        }

        if ($step == 1)
            $data['includefile'] = "register1";
        elseif ($step == 2)
            $data['includefile'] = "register2";
        elseif ($step == 3)
            $data['includefile'] = "register3";
		//print_r($data);die;
    	    $this->master_view('', $data);
    }

    function register_thanks() {
    	    	
    	
    	if($this->session->userdata('register_thanks'))
    	{
			$data['includefile'] = "register_thanks";
        	$this->master_view('', $data);
        	
		}
		else
		{
			redirect("member/register/", 'refresh');
            return;
		}
        
    }

    function active() {

        $retData = $this->user->getDatabyActivationNo($this->uri->segment(3));
        if ($retData) {
            #do not active the member ..just verfiy members email
            //$this->member_model->updateByParam('member_master', array('member_active' => '1'), array('member_auth_id' => $retData->user_auth_id));
            $this->user->userEmailVerify($retData->user_auth_id);
            redirect('member/active_thanks', 'refresh');
        } else {
            $this->session->set_flashdata('notification-error', $this->lang->line('invalid_user'));
            redirect('home/index', 'location');
            return;
        }
    }

    function active_thanks() {
        $data['includefile'] = "active_thanks";

        $this->master_view('', $data);
    }

    function myaccount() {
        $this->user->loginRequired(true);
        $this->member_model->permissionRequired('per_my_account');
        $data[member_membership_id] = $this->member_model->get_member_by_id($this->user->id);
        define('account_no_side_panel', TRUE);

        $data['js'] = array("search", "alertbox");
        $this->load->model('member_album_model');

        $data['photoAlbumCnt'] = $this->member_album_model->countAlbum($this->user->id, 'Photo');
        $data['videoAlbumCnt'] = $this->member_album_model->countAlbum($this->user->id, 'Video');


        //	$data['photoAlbumCnt'] = $this->member_album_model->total_record;
        //print_r($data['photoAlbumCnt']);

        $loggedUserData = $this->member_model->getMemInfobyId($this->user->id, '0', '0');

        $arrStatistics = array();
        $onlineParam = " AND (TIMESTAMPDIFF(MINUTE,FROM_UNIXTIME(user_live),Now()) between 0 and 2)";

        $param = $this->member_model->perfectMatchParam($loggedUserData);

        if ($loggedUserData['member_alert_cupid'] == 'Yes') {
            $cupidpercent = $loggedUserData['member_alert_cupid_per'];
        } else {
            $cupidpercent = 10;
        }
        $data['cupidper'] = $cupidpercent;

        $searchArray_cupid = array(
            'perfectmatch' => '1',
            'cupidPer' => $cupidpercent,
            'PerfectPer' => ''
        );

        $this->member_model->viewAllSiteMember('', '', $searchArray_cupid);
        $arrStatistics['myMatchCnt'] = $this->member_model->total_record;
        //$arrStatistics['myMatchCnt'] = $this->member_model->getSiteMemberData($param, $countFlag=true);

        $searchArray_onlinecupid = array(
            'perfectmatch' => '1',
            'cupidPer' => $cupidpercent,
            'PerfectPer' => '',
            'search_profile_disp' => '4'
        );

        $this->member_model->viewAllSiteMember('', '', $searchArray_onlinecupid);
        $arrStatistics['myMatchCnt-Online'] = $this->member_model->total_record;


        #for perfect match of 100%
        /*
          $searchArray = array(
          'perfectmatch' => '1' ,
          'cupidPer' => '',
          'PerfectPer' => '100',
          );
         */
        #added by zil
        $data['myMatch'] = $this->member_model->viewAllSiteMember(4, 0, $searchArray_cupid);
        //$data['myMatch'] = $this->member_model->viewAllSiteMember(4, 0,$searchArray, false, $param);
        #hotlist
        if ($loggedUserData['member_hot_list'] == '')
            $param = " AND member_id IN (0) ";
        else
            $param = " AND member_id IN (" . $loggedUserData['member_hot_list'] . ") ";
        $arrStatistics['Hot'] = $this->member_model->getSiteMemberData($param, $countFlag = true);
        $arrStatistics['Hot-Online'] = $this->member_model->getSiteMemberData($param . $onlineParam, $countFlag = true);

        #network friends
        $param = "AND (member_id IN (SELECT send_member_id FROM member_network WHERE request_status = 'Accepted' AND received_member_id = '" . $this->user->id . "') OR member_id IN (SELECT received_member_id FROM member_network WHERE request_status = 'Accepted' AND send_member_id = '" . $this->user->id . "'))";
        $arrStatistics['NetworkFriends'] = $this->member_model->getSiteMemberData($param, $countFlag = true);

        $arrStatistics['NetworkFriends-Online'] = $this->member_model->getSiteMemberData($param . $onlineParam, $countFlag = true);

        $arrStatistics['NetworkInvites'] = $this->member_model->countNetwork("request_status = 'Pending' AND send_member_id = " . $this->user->id);
        $arrStatistics['NetworkRequest'] = $this->member_model->countNetwork("request_status = 'Pending' AND received_member_id = " . $this->user->id);

        #friends birthday
        $param .= " AND (MONTH(member_birthdate) = MONTH(CURDATE())) ";

        $arrStatistics['BirthdayCount'] = $this->member_model->getSiteMemberData($param, $countFlag = true);
        $arrStatistics['BirthdayCount-Online'] = $this->member_model->getSiteMemberData($param . $onlineParam, $countFlag = true);

        if ($loggedUserData['member_blocked_list'] == '')
            $param = " AND member_id IN (0) ";
        else
            $param = " AND member_id IN (" . $loggedUserData['member_blocked_list'] . ") ";
        $arrStatistics['Block'] = $this->member_model->getSiteMemberData($param, $countFlag = true);
        $arrStatistics['Block-Online'] = $this->member_model->getSiteMemberData($param . $onlineParam, $countFlag = true);

        //get the mygroup
        $this->load->model('Group_model', 'group');
        /* $this->group->get_current_logged_in_user_groups((int) $config['per_page'], (int) $this->session->userdata('mygroupsoffset')); 
          $arrStatistics['GroupCnt'] = $this->group->total_record;
         */

        $where_grp_str[] = array('group_active' => 1);
        $where_grp_str[] = array('group_auth_id' => $this->user->user_auth_id);
        $where_grp_str[] = array('group_hide_group !=' => 'hide_group');
        $data['groups_own'] = $this->group->view_all(NULL, $where_grp_str, NULL, array('group_id' => 'desc'));
        $arrStatistics['GroupCnt'] = $this->group->total_record;


        //get new members count
        $param = " AND YEARWEEK(member_created_date) = YEARWEEK(CURRENT_DATE) ";
        $arrStatistics['NewMember'] = $this->member_model->getSiteMemberData($param, $countFlag = true);
        $arrStatistics['NewMember-Online'] = $this->member_model->getSiteMemberData($param . $onlineParam, $countFlag = true);

        //get the myblogs
        $this->load->model('blog_topic_model', 'blog_topic');
        $where_str2[] = array('topic_user_auth_id' => $this->session->userdata('user_auth_id'));
        $where_str2[] = array('topic_status =' => 1);
        $where_str2[] = array('topic_publish_date <=' => date('Y-m-d'));
        $this->blog_topic->view_all('topic_id', $where_str2, NULL, NULL, '', 0, TRUE);
        $arrStatistics['MyBlogCount'] = $this->blog_topic->total_record;

        $this->load->model('mailbox_model', 'mailbox');
        $arrStatistics['recMailCount'] = count($this->mailbox->get_inbox_count());
        $arrStatistics['sendMailCount'] = count($this->mailbox->get_outbox_count());

        $arrStatistics['Wink'] = $this->member_model->countWink($this->user->id);

        $this->load->model('member_viewed_model');
        $arrStatistics['ViewCount'] = $this->member_viewed_model->get_view_count($this->user->user_auth_id);        
        $arrStatistics['ViewCount-Online'] = $this->member_viewed_model->get_view_count($this->user->user_auth_id, TRUE);


        $this->load->model('Forum_topic_model', 'forum_topic');
        $forum_where_str = " forum_topic_auth_id ='" . $this->user->user_auth_id . "'";
        $this->forum_topic->get_all_topics('', 0, '', TRUE, $forum_where_str);
        $arrStatistics['ForumCount'] = $this->forum_topic->total_record;


        $this->load->model('news_model', 'news');
        $where_str[] = array('news_start_date <=' => date('Y-m-d'), 'news_end_date >=' => date('Y-m-d'));
        $data['rsNews'] = $this->news->view_all("*", $where_str, NULL, 'news_id desc', 10, 0);

        #to check top 100 rating 
        $data['top_members'] = $this->member_model->top_memebrs(100, $this->user->id);
        //$data['profilepic'] = $this->member_model->get_profile_photo_by_id($this->user->id);
        $data['memberInfo'] = $loggedUserData;
        $data['arrStatistics'] = $arrStatistics;
        $data['WesternData'] = $this->member_model->getWesternSign($loggedUserData['member_birthdate']);
        $data['includefile'] = "myaccount";

        $this->master_view('', $data);
    }

    function upgrade_membership() {
        //$this->user->loginRequired(true);
        $this->load->library('Mobile_Detect');
		$detect = new Mobile_Detect;
      	if(($detect->isMobile() || $detect->isNexusTablet()) && !$detect->isiPad()) {		
		    header('Location: http://m.singaporefriendfinder.com/');
		    exit;
		}
        $this->session->set_userdata('redirect_url', base_url(uri_string()));
       // echo $this->uri->segment(3);die;
        if($this->uri->segment(3) != "benefit")
        {
		$this->user->loginRequired(true, '/member/login/');	
		}
        
        $data['member_membership_id'] = $this->member_model->get_member_by_id($this->user->id);   
        //print_r($data['member_membership_id']['member_membership_renew']);     
        define('account_no_side_panel', TRUE);
        define('currency_side_panel', TRUE);
        $this->load->model('Membership_model', 'membership');

        $data['page_data'] = $this->page_model->get_page_info_by_id(7);
        //print_r($data['page_data']);
        $data['membershipData'] = $this->membership->get_typeinfo_by_id_('4');
        
		$this->db->from("billing_master");
	    $this->db->where(array("pay_member_id" => $this->user->id, "pay_type" => '1', "pay_status" => '2'));
        $this->db->where_in("pay_membership_type", array('4'));
        $this->db->order_by('pay_sub_end_date', 'DESC');
        $query = $this->db->get();
        
        //$memBillInfo = $query->result_array();
        $data['paypal_recurring'] = $query->result_array();
        //echo '<pre>';print_r($data['paypal_recurring'][0]);die;
        $data['pay_profile_row_data'] = json_decode($data['paypal_recurring'][0]['pay_profile_row_data'],true); 
        $data['pay_cancel_row_data'] = json_decode($data['paypal_recurring'][0]['pay_cancel_row_data'],true); 
        //echo '<pre>';print_r($data['pay_profile_row_data']);
        //echo '<pre>';print_r($data['pay_cancel_row_data']);die;
        $data['includefile'] = "upgrade";
		$data['js'] = array("alertbox");
        $this->master_view('', $data);
    }

    function membership_changed() {
        //$this->user->loginRequired(true);
        define('account_no_side_panel', TRUE);

        $this->load->model('Membership_model', 'membership');

        $data['page_data'] = $this->page_model->get_page_info_by_id(7);
        $data['membershipData'] = $this->membership->get_typeinfo_by_id_('4');
        $data['includefile'] = "upgrade_changed";
        $this->master_view('', $data);
    }

    function unauthorized_access() {
        //$this->user->loginRequired(true);
        define('account_no_side_panel', TRUE);
        $MemData = $this->member_model->get_member_by_id($this->user->id, "*", array('auth_user' => 'user_auth_id = member_auth_id'));
        $data['MemData'] = $MemData;
        $data['includefile'] = "unauthorized_access";
        $this->master_view('', $data);
    }

    function subscribe() {
        $this->user->loginRequired(true);

        if ($this->input->post('coupon_code') != '') {
            $this->load->model('Promo_Coupon_Model', 'promo_coupon');
            if ($this->promo_coupon->validCoupon('Membership', $this->input->post('plan'))) {
                $this->load->model('Billing_model', 'billing');
                $result = $this->billing->insertUpgrade($this->user->id, 2, $this->input->post('plan'), $this->input->post('coupon_code'), 'coupon');
                $result = $this->promo_coupon->updateUsedCoupon($this->input->post('coupon_code'));

                redirect("member/upgrade_thanks", 'refresh');
            } else {
                $this->session->set_flashdata('notification-error', $this->lang->line('mem_coupon_code_error'));
                redirect('member/upgrade_membership', 'refresh');
            }
        } else {
            $this->load->library('MyPayPal');
          //  $this->load->library('paypal_recurring');
            $this->load->model('Payment_Method_Model', 'payment_method');
            $data['getPaypalData'] = $this->payment_method->get_info_by_key('PayPal');
			foreach ($data['getPaypalData'] as $PaypalData) {

                if ($PaypalData['config_name'] == 'paypal_username')
                    $PayPalApiUsername = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_password')
                    $PayPalApiPassword = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_signature')
                    $PayPalApiSignature = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_mode')
                    $PayPalMode = $PaypalData['config_value'];
            }

            $this->load->model('Membership_model', 'membership');
            $dataMembership = $this->membership->get_subdata_by_id($this->input->post('plan'));

            $PayPalCancelURL = base_url() . 'member/upgrade_cancel';
            $PayPalSuccessURL = base_url() . 'member/upgrade_thanks';
            $PayPalReturnURL = base_url() . 'member/upgrade_thanks';

            $ItemName = 'Upgrade membership ' . $dataMembership['membership_time'] . ' ' . $dataMembership['membership_period'];
            $ItemPrice = $dataMembership['membership_cost'];
            $ItemTotalPrice = $dataMembership['membership_cost'];
            
            $dataMembership = $this->member_model->get_member_by_id($this->user->id);
            $dataMember = $this->member_model->get_member_by_id($this->user->id);
           // echo $this->user->id;
            
            
           // echo $this->user->id;
         //  print_r($dataMembership['member_membership_renew']);
//die;
            //Data to be sent to paypal
            // '&SOLUTIONTYPE=Mark' .
           $padata = '&CURRENCYCODE=' . urlencode($config['sgd']) .
                    '&PAYMENTACTION=Sale' .
                    '&NOSHIPPING=1'.
                    '&LOCALECODE=SG' .
                    '&LANDINGPAGE=Billing' .
                    '&ALLOWNOTE=1' .
                    '&SOLUTIONTYPE=Sole' .
                    '&PAYMENTREQUEST_0_CURRENCYCODE=' . urlencode($config['sgd']) .
                    '&PAYMENTREQUEST_0_AMT=' . urlencode($ItemTotalPrice) .
                    '&PAYMENTREQUEST_0_ITEMAMT=' . urlencode($ItemTotalPrice) .
                    '&L_PAYMENTREQUEST_0_AMT0=' . urlencode($ItemPrice) .
                    '&L_PAYMENTREQUEST_0_NAME0=' . urlencode($ItemName) .
                //    '&L_BILLINGTYPE0=' . 'RecurringPayments' .
                  //  '&L_BILLINGAGREEMENTDESCRIPTION0=' . base_url() . ' subscription' .
                    '&AMT=' . urlencode($ItemTotalPrice) .
                    //'&PAYMENTREQUEST_0_CUSTOM='.urlencode($DeviceID).
                    '&RETURNURL=' . urlencode($PayPalReturnURL) .
                    '&CANCELURL=' . urlencode($PayPalCancelURL);
//print_r($padata);die;

			if($dataMember['member_membership_renew']=='Yes')
			{
					$padata .='&L_BILLINGTYPE0=' . 'RecurringPayments' .
							'&L_BILLINGAGREEMENTDESCRIPTION0='.$ItemName ;
			}
         
            //We need to execute the "SetExpressCheckOut" method to obtain paypal token
            $paypal = new MyPayPal();
            $httpParsedResponseAr = $paypal->PPHttpPost('SetExpressCheckout', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);

            //Respond according to message we receive from Paypal
/*            $paypal=new paypal_recurring;

			$paypal->environment = 'sandbox';	// or 'beta-sandbox' or 'live'
			$paypal->paymentType = urlencode('Sale');				// or 'Sale' or 'Order'

			// Set request-specific fields.
			$paypal->startDate =  urlencode(date("Y-m-d h:s:i"));
			$paypal->billingPeriod = urlencode(ucfirst($dataMembership['membership_period']));				// or "Day", "Week", "SemiMonth", "Year"
			$paypal->billingFreq = urlencode($dataMembership['membership_time']);		// combination of this and billingPeriod must be at most a year
			$paypal->paymentAmount = urlencode($ItemTotalPrice);
			$paypal->currencyID = urlencode($config['sgd']);			// or other currency code ('GBP', 'EUR', 'JPY', 'CAD', 'AUD')
			$paypal->Desc = base_url() . ' subscription';
			$paypal->ItemName = $ItemName;
			
			$paypal->API_UserName = urlencode($PayPalApiUsername);
			$paypal->API_Password = urlencode($PayPalApiPassword);
			$paypal->API_Signature = urlencode($PayPalApiSignature);
			$paypal->API_Endpoint = "https://api-3t.paypal.com/nvp";

			/*SET SUCCESS AND FAIL URL
			$paypal->returnURL = urlencode($PayPalReturnURL);
			$paypal->cancelURL = urlencode($PayPalCancelURL);


			$task="setExpressCheckout"; //set initial task as Express Checkout
			$httpParsedResponseAr=$paypal->setExpressCheckout();
*/
            if ("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
                
                $this->load->model('Billing_model', 'billing');
                $retInsertID = $this->billing->insertUpgrade($this->user->id, 1, $this->input->post('plan'), '', 'paypal');

                $this->session->set_userdata('billingID', $retInsertID);
   				$this->session->set_userdata('planID',$this->input->post('plan'));
   
                if ($PayPalMode == 'sandbox') {
                    $paypalmode = '.sandbox';
                } else {
                    $paypalmode = '';
                }


                //Redirect user to PayPal store with Token received.
                $paypalurl = 'https://www' . $paypalmode . '.paypal.com/cgi-bin/webscr?cmd=_express-checkout-mobile&token=' . $httpParsedResponseAr["TOKEN"] . '';
                header('Location: ' . $paypalurl);
            } else {
                //Show error message
                echo '<div style="color:red"><b>Error : </b>' . urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]) . '</div>';
                /*echo '<pre>';
                print_r($httpParsedResponseAr);
                echo '</pre>';*/
            }
        }
    }

    function updateaccount() {
        $this->user->loginRequired(true);

        if ($this->input->post('coupon_code') != '') {
            $this->load->model('Promo_Coupon_Model', 'promo_coupon');
            if ($this->promo_coupon->validCoupon('Account', $this->input->post('amount_add'))) {
                $this->load->model('Billing_model', 'billing');
                $result = $this->billing->insertAddFund($this->user->id, 2, $this->input->post('amount_add'), $this->input->post('coupon_code'), 'coupon');

                $result = $this->promo_coupon->updateUsedCoupon($this->input->post('coupon_code'));
                //print_r($result);die;
                $this->session->set_flashdata('notification', $this->lang->line('member_updateac_msg'));
                redirect('myaccount/myinfo', 'location');
            } elseif (!$this->promo_coupon->validCoupon('Account', $this->input->post('amount_add')) && $this->promo_coupon->validCoupon('Account', $this->input->post('coupon_code'))) {
                $this->session->set_flashdata('notification-error', $this->lang->line('mem_coupon_amount_wrong_error'));
                redirect('myaccount/updateaccount', 'refresh');
            } else {
                $this->session->set_flashdata('notification-error', $this->lang->line('mem_coupon_code_error'));
                redirect('myaccount/updateaccount', 'refresh');
            }
        }
        elseif($this->input->post('amount_add') == 0){
					$this->session->set_flashdata('notification-error', $this->lang->line('mem_paypalamount_error'));
                redirect('myaccount/updateaccount', 'refresh');
		}
         else {
            $this->load->library('MyPayPal');
            $this->load->model('Payment_Method_Model', 'payment_method');
            $data['getPaypalData'] = $this->payment_method->get_info_by_key('PayPal');

            foreach ($data['getPaypalData'] as $PaypalData) {

                if ($PaypalData['config_name'] == 'paypal_username')
                    $PayPalApiUsername = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_password')
                    $PayPalApiPassword = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_signature')
                    $PayPalApiSignature = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_mode')
                    $PayPalMode = $PaypalData['config_value'];
            }

            $PayPalCancelURL = base_url() . 'myaccount/updateac_cancel';
            $PayPalSuccessURL = base_url() . 'myaccount/updateac_thanks';
            $PayPalReturnURL = base_url() . 'myaccount/updateac_thanks';

            $ItemName = 'Update Fund';
            $ItemPrice = $this->input->post('amount_add');
            $ItemTotalPrice = $this->input->post('amount_add');

            //Data to be sent to paypal
            $padata = '&CURRENCYCODE=' . urlencode($config['sgd']) .
                    '&PAYMENTACTION=Sale' .
                    '&NOSHIPPING=1'.
                    '&ALLOWNOTE=1' .
                    '&SOLUTIONTYPE=Mark' .
                    '&PAYMENTREQUEST_0_CURRENCYCODE=' . urlencode($config['sgd']) .
                    '&PAYMENTREQUEST_0_AMT=' . urlencode($ItemTotalPrice) .
                    '&PAYMENTREQUEST_0_ITEMAMT=' . urlencode($ItemTotalPrice) .
                    '&L_PAYMENTREQUEST_0_AMT0=' . urlencode($ItemPrice) .
                    '&L_PAYMENTREQUEST_0_NAME0=' . urlencode($ItemName) .
                    '&L_BILLINGTYPE0=' . 'RecurringPayments' .
                    '&L_BILLINGAGREEMENTDESCRIPTION0=' . base_url() . ' subscription' .
                    '&AMT=' . urlencode($ItemTotalPrice) .
                    //'&PAYMENTREQUEST_0_CUSTOM='.urlencode($DeviceID).
                    '&RETURNURL=' . urlencode($PayPalReturnURL) .
                    '&CANCELURL=' . urlencode($PayPalCancelURL);

            //We need to execute the "SetExpressCheckOut" method to obtain paypal token
            $paypal = new MyPayPal();
            $httpParsedResponseAr = $paypal->PPHttpPost('SetExpressCheckout', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);

            //Respond according to message we receive from Paypal
            if ("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
                $this->load->model('Billing_model', 'billing');
                $retInsertID = $this->billing->insertAddFund($this->user->id, 1, $this->input->post('amount_add'), '', 'paypal');

                $this->session->set_userdata('billingID', $retInsertID);

                if ($PayPalMode == 'sandbox') {
                    $paypalmode = '.sandbox';
                } else {
                    $paypalmode = '';
                }

                //Redirect user to PayPal store with Token received.
                $paypalurl = 'https://www' . $paypalmode . '.paypal.com/cgi-bin/webscr?cmd=_express-checkout-mobile&token=' . $httpParsedResponseAr["TOKEN"] . '';
                header('Location: ' . $paypalurl);
            } else {
                //Show error message
                echo '<div style="color:red"><b>Error : </b>' . urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]) . '</div>';
               /* echo '<pre>';
                print_r($httpParsedResponseAr);
                echo '</pre>';*/
            }
        }
    }

    function upgrade_thanks() {
        $this->user->loginRequired(true);
		//$this->load->library('paypal_recurring');
		$this->load->library('MyPayPal');
		
		$this->load->model('Payment_Method_Model', 'payment_method');
		$data['getPaypalData'] = $this->payment_method->get_info_by_key('PayPal');
			
            foreach ($data['getPaypalData'] as $PaypalData) {

                if ($PaypalData['config_name'] == 'paypal_username')
                    $PayPalApiUsername = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_password')
                    $PayPalApiPassword = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_signature')
                    $PayPalApiSignature = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_mode')
                    $PayPalMode = $PaypalData['config_value'];
            }
        
         $this->db->from("billing_master");
		     $this->db->where(array("pay_member_id" => $this->user->id, "pay_type" => '1', "pay_status" => '2'));
		     $this->db->where_not_in("pay_membership_type", array('1', '2', '3'));
		     $this->db->order_by('pay_sub_end_date', 'DESC');
		     $query = $this->db->get();
		   
		     if ($query->num_rows() == 0) {
		           $pay_sub_start_date = date('Y-m-d h:s:i');
		     } else {
		           $memBillInfo = $query->result_array();
		           if(strtotime($memBillInfo[0]['pay_sub_end_date']) > strtotime(date('Y-m-d h:s:i')))
		              $pay_sub_start_date =date('Y-m-d H:i:s', strtotime($memBillInfo[0]['pay_sub_end_date']." 0:0:0")); 
		           else 
		           	  $pay_sub_start_date = date('Y-m-d h:s:i');
	        }
	        $data['includefile'] = "upgrade_thanks"; 
	       //$pay_sub_start_date="2015-04-04 0:0:0";
        if (isset($_GET["token"]) && isset($_GET["PayerID"])) {
        	//get member_membership_renew option and if it is on then only create recurring profile
            $this->load->model('Billing_model', 'billing');
            $billID = $this->session->userdata('billingID');
            $planID = $this->session->userdata('planID');
            $this->load->model('Membership_model', 'membership');
            $dataMembership = $this->membership->get_subdata_by_id($planID);

            $PayPalCancelURL = base_url() . 'member/upgrade_cancel';
            $PayPalSuccessURL = base_url() . 'member/upgrade_thanks';
            $PayPalReturnURL = base_url() . 'member/upgrade_thanks';

 			$dataMember = $this->member_model->get_member_by_id($this->user->id);
           	$this->member_model->activate_membership($this->user->id);
            $ItemName = 'Upgrade membership ' . $dataMembership['membership_time'] . ' ' . $dataMembership['membership_period'];
            $ItemPrice = $dataMembership['membership_cost'];
            $ItemTotalPrice = $dataMembership['membership_cost'];
           
			$paypal = new MyPayPal();
			 
			$token = $_GET["token"];
			$payer_id = $_GET["PayerID"];
				
			$padata = 	'&TOKEN='.urlencode($token).
				'&PAYERID='.urlencode($payer_id).
				'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").
				'&NOSHIPPING=1'.
				
				//set item info here, otherwise we won't see product details later	
				'&L_PAYMENTREQUEST_0_NAME0='.urlencode($ItemName).
				'&L_PAYMENTREQUEST_0_AMT0='.urlencode($ItemPrice).
			
				'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($ItemTotalPrice).
				'&PAYMENTREQUEST_0_AMT='.urlencode($ItemTotalPrice).
				'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($config['sgd']) ;
	
			//We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
			$paypal= new MyPayPal();
			$httpParsedResponseAr = $paypal->PPHttpPost('DoExpressCheckoutPayment', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);
		//	print_r("<pre>");
			//print_r($httpParsedResponseAr);echo $dataMember['member_membership_renew'];
			if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
			{
		   		if($dataMember['member_membership_renew']=='Yes')
	            {
				$padata = 	'&TOKEN='.urlencode($token).
				'&PAYERID='.urlencode($payer_id).
				'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").
				'&NOSHIPPING=1'.
				
				//set item info here, otherwise we won't see product details later	
				'&L_PAYMENTREQUEST_0_NAME0='.urlencode($ItemName).
				'&L_PAYMENTREQUEST_0_AMT0='.urlencode($ItemPrice).
			
				'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($ItemTotalPrice).
				'&PAYMENTREQUEST_0_AMT='.urlencode($ItemTotalPrice).
				'&PROFILESTARTDATE='.($pay_sub_start_date).
				'&BILLINGPERIOD='. urlencode(ucfirst($dataMembership['membership_period'])).
				'&BILLINGFREQUENCY='.urlencode($dataMembership['membership_time']).
				'&DESC='.($ItemName).
				'&AMT='.($ItemTotalPrice).
				'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($config['sgd']);
				
				$recuring_profile_response = $paypal->PPHttpPost('CreateRecurringPaymentsProfile', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);
			
				if("SUCCESS" == strtoupper($recuring_profile_response["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($recuring_profile_response["ACK"]))
					$data['includefile'] = "upgrade_thanks"; 
				else
					$data['includefile'] = "upgrade_error";
				}
				
			}
			else
			{
				$data['includefile'] = "upgrade_error";
			}		
		    $this->billing->updateResponse($billID, 2,$recuring_profile_response);

            /* ----------------- added by pradip ----------------(add 2$ to a friend who refer) */
            $friendArray = $this->session->userdata('sendFriendData');
		    if (isset($friendArray['sendID'])) {
                $this->load->model('Billing_model', 'billing');
                $result = $this->billing->insertTellFriend($friendArray['send_member_id']);

                $this->member_model->updateByParam('member_send_friend', array('send_status' => 'Done'), array('send_id' => $friendArray['send_id']));
                $this->member_model->get_two_dollar($this->session->userdata('useremail'),$friendArray['send_from_mail']);
                $this->session->unset_userdata('sendFriendData');
            }
		    /* ----------------- added by pradip ---------------- */
        }

        $this->master_view('', $data);
    }

    function upgrade_cancel() {
        $this->user->loginRequired(true);

        $this->load->model('Billing_model', 'billing');
        $this->billing->updateResponse($this->session->userdata('billingID'), 3);

        $data['includefile'] = "upgrade_cancel";
        $this->master_view('', $data);
    }

    function searchlist() {
        $this->user->loginRequired(true);
        define('search_side_panel', TRUE);
        $data['js'] = array("search_username", "alertbox");
        $data['member_autocomplete'] = true;

        $this->load->model('Region_Model', 'region');

        $data['includefile'] = "search";

        if (($this->uri->segment(3) != '') && ($this->uri->segment(3) != 'quick') && ($this->uri->segment(3) != 'advanced') && ($this->uri->segment(3) != 'username')) {
            $this->load->model('zip_code_model');

            $data['regionData'] = $this->region->getAllGroupwise();
//echo"<pre>"; print_r($data['regionData']); echo"</pre>";die;
            if (($this->uri->segment(3) == 'purpose') || ($this->uri->segment(3) == 'women-women') || ($this->uri->segment(3) == 'men-men') || ($this->uri->segment(3) == 'women-men') || ($this->uri->segment(3) == 'men-women')) {
                $this->member_model->permissionRequired('per_browse_search');

                foreach ($data['regionData'] as $k => $regData) {
                    foreach ($regData as $k1 => $rData) {
                    	//echo '<pre>';print_r($rData);
                        $zipData = $this->zip_code_model->getZipbyRegion($data['regionData'][$k][$k1]['region_id']);
//echo"<pre>"; print_r($zipData); echo"</pre>";die;
                        if ($this->uri->segment(3) == 'purpose')
                            $cnt = $this->member_model->countResultbyParam("member_hide_profile = 'No' AND member_active = '1' AND member_trash = 0 AND FIND_IN_SET('" . $this->uri->segment(4) . "',member_purpose) AND member_singapore_zipcode IN ('" . $zipData . "')");
                        elseif ($this->uri->segment(3) == 'women-women')
                            $cnt = $this->member_model->countResultbyParam("member_hide_profile = 'No' AND member_active = '1' AND member_trash = 0 AND member_gender = 2 AND member_looking_for = 2 AND member_singapore_zipcode IN ('" . $zipData . "')");
                        elseif ($this->uri->segment(3) == 'men-men')
                            $cnt = $this->member_model->countResultbyParam("member_hide_profile = 'No' AND member_active = '1' AND member_trash = 0 AND member_gender = 1 AND member_looking_for = 1 AND member_singapore_zipcode IN ('" . $zipData . "')");
                        elseif ($this->uri->segment(3) == 'women-men')
                            $cnt = $this->member_model->countResultbyParam("member_hide_profile = 'No' AND member_active = '1' AND member_trash = 0 AND member_gender = 2 AND member_looking_for = 1 AND member_singapore_zipcode IN ('" . $zipData . "')");
                        elseif ($this->uri->segment(3) == 'men-women')
                            $cnt = $this->member_model->countResultbyParam("member_hide_profile = 'No' AND member_active = '1' AND member_trash = 0 AND member_gender = 1 AND member_looking_for = 2 AND member_singapore_zipcode IN ('" . $zipData . "')");

                        $data['regionData'][$k][$k1]['region_cnt'] = $cnt;
                        //echo $this->db->last_query();die;
                    }
                }
            }
        }
        else {
            if (($this->uri->segment(3) == 'quick'))
                $this->member_model->permissionRequired('per_quick_search');

            if (($this->uri->segment(3) == 'advanced'))
                $this->member_model->permissionRequired('per_advance_search');

            if (($this->uri->segment(3) == 'username'))
                $this->member_model->permissionRequired('per_username_search');
        }

        $this->master_view('', $data);
    }

    function perfectmatch($type = '') {
        $this->user->loginRequired(true);

        $this->member_model->permissionRequired('per_perfect_match');

        $memData = $this->member_model->get_member_by_id($this->user->id);
		//echo '<pre>';print_r($memData);die;
        if ($memData['member_alert_cupid'] == 'Yes') {
            $cupidpercent = $memData['member_alert_cupid_per'];
            
        } else {
            $cupidpercent = 10;
        }
        /* --------------- added by pradip  --------------- */
        $this->session->unset_userdata('quick_search_data');
        $this->session->unset_userdata('adv_search_data');
        $this->session->unset_userdata('search_username');
        /* ---------------------------------------------- */

        $search_zip_lat = '';
        $search_zip_lng = '';
        if ($memData['partner_zip_code'] != '') {
            $this->load->model('zip_code_model');
            $zipData = $this->zip_code_model->getInfoByZip($memData['partner_zip_code']);
            $search_zip_lat = $zipData['area_lat'];
            $search_zip_lng = $zipData['area_lng'];
        }

        $searchArray = array(
            'search_i_am' => $memData['member_gender'],
            'search_lookingfor' => $memData['member_looking_for'],
            'search_age_from' => $memData['partner_age_range_from'],
            'search_age_to' => $memData['partner_age_range_to'],
            'search_from_height' => '',
            'search_to_height' => '',
            'search_height_list' => $memData['member_partner_height'],
            'search_body_type' => $memData['member_partner_body_type'],
            'search_hair_color' => $memData['member_partner_hair_color'],
            'search_hair_length' => $memData['member_partner_hair_length'],
            'search_eye_color' => $memData['member_partner_eye_color'],
            'search_alcohal_pref' => $memData['member_partner_alchohol_pref'],
            'search_edu_level' => $memData['member_partner_edu_level'],
            'search_religion' => $memData['member_partner_religion'],
            'search_occupation' => $memData['member_partner_occupation'],
            'search_sextual_orientation' => $memData['member_partner_sex_orientation'],
            'search_marital_status' => $memData['member_partner_marrital_status'],
            'search_noof_children' => $memData['member_partner_children'],
            'search_purpose' => $memData['member_purpose'],
            'search_race' => $memData['member_partner_race'],
            'search_smoking_pref' => $memData['member_partner_smoke_pref'],
            'search_language' => $memData['member_partner_language'],
            'search_interest' => $memData['member_partner_interest'],
            'search_within' => $memData['partner_within_km'],
            'search_zip_code' => $memData['partner_zip_code'],
            'search_zip_lat' => $search_zip_lat,
            'search_zip_lng' => $search_zip_lng,
            'search_zip' => '',
            'search_profile_disp' => 1,
            'search_keyword_option' => 1,
            'reversematch' => '0',
            //aded by zil
            'perfectmatch' => '1',
            'cupidPer' => $cupidpercent,
            'perfectlist' => '1',
            'PerfectPer' => '100',
            'sort_by' => 'member_created_date',
            'searchView' => 'grid',
        );

        if ($type == 'online')
            $searchArray['search_profile_disp'] = 4;
		
        $this->session->set_userdata('siteSearchData', $searchArray);

        redirect('member/search', 'refresh');
    }

    function reversematch() {
        $this->user->loginRequired(true);
        $this->member_model->permissionRequired('per_reverse_match');

        $memData = $this->member_model->get_member_by_id($this->user->id, '*, TIMESTAMPDIFF(YEAR,member_birthdate,CURDATE()) AS Age');
		//echo '<pre>';print_r($memData);die;
        if ($memData['member_alert_cupid'] == 'Yes') {
            $cupidpercent = $memData['member_alert_cupid_per'];
        } else {
            $cupidpercent = 10;
        }
        /* --------------- added by pradip  --------------- */
        $this->session->unset_userdata('quick_search_data');
        $this->session->unset_userdata('adv_search_data');
        $this->session->unset_userdata('search_username');
        /* ----------------------- */

        $search_zip_lat = '';
        $search_zip_lng = '';
        if ($memData['member_singapore_zipcode'] != '') {
            $this->load->model('zip_code_model');
            $zipData = $this->zip_code_model->getInfoByZip($memData['member_singapore_zipcode']);
            $search_zip_lat = $zipData['area_lat'];
            $search_zip_lng = $zipData['area_lng'];
        }

        $searchArray = array(
        	/*'search_profile_id' =>$memData['member_id'],
        	'search_profile_image' =>$memData['member_profile_image'],*/
            'search_i_am' => $memData['member_gender'],
            'search_lookingfor' => $memData['member_looking_for'],
            'search_age_from' => $memData['partner_age_range_from'],
            'search_age_to' => $memData['partner_age_range_to'],
            'search_from_height' => '',
            'search_to_height' => '',
            'search_height_list' => $memData['member_partner_height'],
            'search_body_type' => $memData['member_partner_body_type'],
            'search_hair_color' => $memData['member_partner_hair_color'],
            'search_hair_length' => $memData['member_partner_hair_length'],
            'search_eye_color' => $memData['member_partner_eye_color'],
            'search_alcohal_pref' => $memData['member_partner_alchohol_pref'],
            'search_edu_level' => $memData['member_partner_edu_level'],
            'search_religion' => $memData['member_partner_religion'],
            'search_occupation' => $memData['member_partner_occupation'],
            'search_sextual_orientation' => $memData['member_partner_sex_orientation'],
            'search_marital_status' => $memData['member_partner_marrital_status'],
            'search_noof_children' => $memData['member_partner_children'],
            'search_purpose' => $memData['member_purpose'],
            'search_race' => $memData['member_partner_race'],
            'search_smoking_pref' => $memData['member_partner_smoke_pref'],
            'search_language' => $memData['member_partner_language'],
            'search_interest' => $memData['member_partner_interest'],
            'search_within' => $memData['partner_within_km'],
            'search_zip_code' => $memData['partner_zip_code'],
            'search_zip_lat' => $search_zip_lat,
            'search_zip_lng' => $search_zip_lng,
            'search_zip' => '',
            'search_profile_disp' => 1,
            'search_keyword_option' => 1,
            'perfectmatch' => '0',
            'reversematch' => '1',
            'cupidPer' => $cupidpercent,
            'reverselist' => '1',
            'PerfectPer' => '100',
            'sort_by' => 'member_created_date',
            'searchView' => 'grid',
        );
        //echo '<pre>';print_r( $searchArray);die;
        $this->session->set_userdata('siteSearchData', $searchArray);

        redirect('member/search', 'refresh');
    }

    function browsesearch() {
        $this->user->loginRequired(true);

        $this->member_model->permissionRequired('per_browse_search');

        $this->load->model('zip_code_model');
        $search_zip = $this->zip_code_model->getZipbyRegion($this->uri->segment(3));
//echo $search_zip;die;
        $search_i_am = '';
        $search_lookingfor = '';
        $search_purpose = '';
        if ($this->uri->segment(4) == 'purpose') {
            $search_purpose = $this->uri->segment(5);
        } elseif ($this->uri->segment(4) == 'women-women') {
            $search_i_am = '2';
            $search_lookingfor = '2';
        } elseif ($this->uri->segment(4) == 'men-men') {
            $search_i_am = '1';
            $search_lookingfor = '1';
        } elseif ($this->uri->segment(4) == 'women-men') {
            $search_i_am = '1';
            $search_lookingfor = '2';
        } elseif ($this->uri->segment(4) == 'men-women') {
            $search_i_am = '2';
            $search_lookingfor = '1';
        }

        $searchArray = array(
            'search_i_am' => $search_i_am,
            'search_lookingfor' => $search_lookingfor,
            'search_purpose' => $search_purpose,
            'search_zip' => $search_zip,
            'search_profile_disp' => 1,
            'search_keyword_option' => 1,
//            'reversematch' => '0',
            'broswse_match' => '1', // added by pradip
            'sort_by' => 'member_created_date',
            'searchView' => 'grid',
        );
		//echo '<pre>';print_r($searchArray);die;
        $this->session->set_userdata('siteSearchData', $searchArray);
        

        redirect('member/search', 'refresh');
    }

    function search($offset = 0) {
		
        $searchArray = $this->session->userdata('siteSearchData');
        //echo '<pre>'; print_r($searchArray);die;	
        $data['member_membership_id'] = $this->member_model->get_member_by_id($this->user->id);
        
        if ($this->input->post('QuickSearch') || $this->input->post('AdvSearch') || $this->input->post('IDSearch') || $this->input->post('UserSearch')) {
            //if ($this->input->post('QuickSearch'))
            //	$this->member_model->permissionRequired('per_quick_search');

            /* ------------- code added by pradip ------------- */
            $memData = $this->member_model->get_member_by_id($this->user->id, '*, TIMESTAMPDIFF(YEAR,member_birthdate,CURDATE()) AS Age');
//echo "<pre>"; print_r($memData); echo"</pre>";

            if ($this->input->post('QuickSearch')) {
                $this->session->unset_userdata('adv_search_data');
                $this->session->unset_userdata('search_username');
                $this->session->set_userdata('quick_search_data', $this->input->post());
            }

            if ($this->input->post('AdvSearch')) {
                $this->session->unset_userdata('quick_search_data');
                $this->session->unset_userdata('search_username');
                $this->session->set_userdata('adv_search_data', $this->input->post());
            }

            if ($this->input->post('UserSearch')) {
                $this->session->unset_userdata('quick_search_data');
                $this->session->unset_userdata('adv_search_data');
                //$this->session->unset_userdata('search_username');
                $this->session->set_userdata('search_username', $this->input->post()); //Add by Nilay
//               $this->session->set_userdata('search_username', '1');
            }
$quick_search_data = $this->session->userdata('quick_search_data');
//print_r($quick_search_data);
$array=array_merge($searchArray,$quick_search_data);
//echo '<pre>';print_r($array);die;
                $this->session->set_userdata('quick_array', $array); //Add by Nilay
            /* ------------- --------------------- ------------- */

            if ($this->input->post('AdvSearch'))
                $this->member_model->permissionRequired('per_advance_search');

            if ($this->input->post('IDSearch') || $this->input->post('UserSearch'))
                $this->member_model->permissionRequired('per_username_search');

            $search_zip = '';
            if ($this->input->post('search_region', TRUE) != '') {
                $this->load->model('zip_code_model');
                $search_zip = $this->zip_code_model->getZipbyRegion($this->input->post('search_region', TRUE));
            }

            $search_zip_lat = '';
            $search_zip_lng = '';
            if ($this->input->post('search_zip_code', TRUE) != '') {
                $this->load->model('zip_code_model');
                $zipData = $this->zip_code_model->getInfoByZip($this->input->post('search_zip_code', TRUE));
                $search_zip_lat = $zipData['area_lat'];
                $search_zip_lng = $zipData['area_lng'];
            }
            if ($this->input->post('UserSearch')) {
                $searchArray = array(
                    'search_profile_id' => (($this->input->post('search_profile_id', TRUE) != '') ? $this->input->post('search_profile_id', TRUE) : ''),
                    'sort_by' => 'member_created_date',
                    'searchView' => 'grid',
                    'User_search' => '1',
                    
                );
            } else {
                $searchArray = array(
                    'search_profile_id' => (($this->input->post('search_profile_id', TRUE) != '') ? $this->input->post('search_profile_id', TRUE) : ''),
//                'search_i_am' => (($this->input->post('search_i_am', TRUE) != '') ? $this->input->post('search_i_am', TRUE) : ''),
                    'search_i_am' => $memData['member_gender'],
                    //'search_lookingfor' => (($this->input->post('search_lookingfor', TRUE) != '') ? $this->input->post('search_lookingfor', TRUE) : $memData['member_looking_for']),
                    'search_lookingfor' => (($this->input->post('search_lookingfor', TRUE) != 0) ? $this->input->post('search_lookingfor', TRUE) : implode(',', array('1','2'))),
                    'search_age_from' => (($this->input->post('search_age_from', true) != '') ? $this->input->post('search_age_from', TRUE) : ''),
                    'search_age_to' => (($this->input->post('search_age_to', true) != '') ? $this->input->post('search_age_to', TRUE) : ''),
                    'search_from_height' => (($this->input->post('search_from_height', true) != '') ? $this->input->post('search_from_height', TRUE) : ''),
                    'search_to_height' => (($this->input->post('search_to_height', true) != '') ? $this->input->post('search_to_height', TRUE) : ''),
                    'search_body_type' => (($this->input->post('search_body_type', true) != '') ? $this->input->post('search_body_type', TRUE) : ''),
                    'search_hair_color' => (($this->input->post('search_hair_color', true) != '') ? $this->input->post('search_hair_color', TRUE) : ''),
                    'search_hair_length' => (($this->input->post('search_hair_length', true) != '') ? $this->input->post('search_hair_length', TRUE) : ''),
                    'search_eye_color' => (($this->input->post('search_eye_color', true) != '') ? $this->input->post('search_eye_color', TRUE) : ''),
                    'search_alcohal_pref' => (($this->input->post('search_alcohal_pref', true) != '') ? $this->input->post('search_alcohal_pref', TRUE) : ''),
                    'search_edu_level' => (($this->input->post('search_edu_level', true) != '') ? $this->input->post('search_edu_level', TRUE) : ''),
                    'search_religion' => (($this->input->post('search_religion', true) != '') ? $this->input->post('search_religion', TRUE) : ''),
                    'search_occupation' => (($this->input->post('search_occupation', true) != '') ? $this->input->post('search_occupation', TRUE) : ''),
                    'search_sextual' => $memData['member_sextual'], // added by pradip
                    'search_sextual_orientation' => (($this->input->post('search_sextual_orientation', true) != '0') ? $this->input->post('search_sextual_orientation', TRUE) : $memData['member_partner_sex_orientation']),
                    'search_marital_status' => (($this->input->post('search_marital_status', true) != '') ? $this->input->post('search_marital_status', TRUE) : ''),
                    'search_noof_children' => (($this->input->post('search_noof_children', true) != '') ? $this->input->post('search_noof_children', TRUE) : ''),
                    'search_purpose' => (is_array($this->input->post('search_purpose', true)) ? implode(',', $this->input->post('search_purpose', TRUE)) : ''),
                    'search_race' => (is_array($this->input->post('search_race', true)) ? implode(',', $this->input->post('search_race', TRUE)) : ''),
                    'search_smoking_pref' => (is_array($this->input->post('search_smoking_pref', true)) ? implode(',', $this->input->post('search_smoking_pref', TRUE)) : ''),
                    'search_language' => (is_array($this->input->post('search_language', true)) ? implode(',', $this->input->post('search_language', TRUE)) : ''),
                    'search_interest' => (is_array($this->input->post('search_interest', true)) ? implode(',', $this->input->post('search_interest', TRUE)) : ''),
                    'search_within' => (($this->input->post('search_within', true) != '') ? $this->input->post('search_within', TRUE) : ''),
                    'search_zip_code' => (($this->input->post('search_zip_code', true) != '') ? $this->input->post('search_zip_code', TRUE) : ''),
                    'search_zip_lat' => $search_zip_lat,
                    'search_zip_lng' => $search_zip_lng,
                    'search_zip' => $search_zip,
                    'search_region' => (($this->input->post('search_region', TRUE) != '') ? $this->input->post('search_region', TRUE) : ''),
                    'search_profile_disp' => (($this->input->post('optProfileDisp', true) != '') ? $this->input->post('optProfileDisp', TRUE) : '1'),
                    'search_keyword_option' => (($this->input->post('optKeyword', true) != '') ? $this->input->post('optKeyword', TRUE) : '1'),
                    'reversematch' => '0',
                    'perfectmatch' => '0', // added by pradip
                    'sort_by' => 'member_created_date',
                    'searchView' => 'grid',
                );
            }
           // echo '<pre>'; print_r($searchArray); die;   // added by pradip
            $this->session->set_userdata('siteSearchData', $searchArray);
        } elseif ($this->input->post('sort_by')) {
            $searchArray = $this->session->userdata('siteSearchData');
            $searchArray['sort_by'] = $this->input->post('sort_by');
            $this->session->set_userdata('siteSearchData', $searchArray);
        }
       
        elseif ($this->input->post('member_perfect_per')) {
            $searchArray = $this->session->userdata('siteSearchData');
            $searchArray['PerfectPer'] = $this->input->post('member_perfect_per');
            $searchArray['cupidPer'] = '';
            $this->session->set_userdata('siteSearchData', $searchArray);
        }

        $session_data = $this->session->userdata('siteSearchData');
        //print_r($session_data);

        if ($offset != 0)
            $this->session->set_userdata('memberoffset', $offset);
        elseif ($offset == 0)
            $this->session->unset_userdata('memberoffset');

        //$this->session->set_userdata('page_size', 4);//$this->system->page_size);
        //$config['per_page']	= 4;//$this->system->page_size;
        //$this->session->set_userdata('page_size', 16); //$this->system->page_size);
        $this->session->set_userdata('page_size', 16); //$this->system->page_size);
        $config['per_page'] = $this->session->userdata('page_size');

//        $this->session->set_userdata('search_username', '1');
        $data['member_record'] = $this->member_model->viewAllSiteMember((int) $config['per_page'], (int) $this->session->userdata('memberoffset'), $session_data);
        //echo '<pre>';print_r($data['member_record']);
//        $this->session->unset_userdata('search_username');

        $data['members_count'] = count($data['member_record']);
        //print_r($data['members_count']);
       // $data['online_count'] = $this->member_model->get_online_results((int) $config['per_page'], (int) $this->session->userdata('memberoffset'), $session_data, TRUE);
//print_r($data['online_count']);die;
		$sort_by = $this->input->post('sort_by');
        $data['online_record'] = $this->member_model->get_online('', FALSE, '', $sort_by, '', 0);
        /*$data['online_count'] = $this->member_model->get_online_results((int) $config['per_page'], (int) $this->session->userdata('memberoffset'), $param_data, TRUE);*/
        //$data['online_count'] = $this->member_model->total_record;
        $data['url_variable'] = $this->uri->segment(3);
               
        //echo '<pre>';print_r($data['online_count']);
        //print_r($get_member_id);die;
        //$data['id']=$value;
        //print_r($value);die;

        /* echo '<pre>';
          print_r($data['get_online']);die; */
        #client change
        #if the user is not logged in on pagination it shold be redirected to registartion page
        if (!$this->user->isLoggedIn() || $this->user->user_perm != 'Member') {
//            $config['base_url'] = base_url() . 'member/register';
            $config['base_url'] = base_url() . 'member/search';
        } else {
            $config['base_url'] = base_url() . 'member/search';
        }
        $config['total_rows'] = ($data['url_variable'] == "online_user") ? $data['online_count'] : $data['members_count'];

        $config['full_tag_open'] = '<ul class="pagination pull-right">';
        $config['full_tag_close'] = '</ul>';

        $config['first_link'] = "<i class='fa fa-angle-double-left fa-lg'></i>";
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';

        $config['prev_link'] = "<i class='fa fa-angle-left fa-lg'></i>";
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';

        $config['last_link'] = "<i class='fa fa-angle-double-right fa-lg'></i>";
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';

        $config['next_link'] = "<i class='fa fa-angle-right fa-lg'></i>";
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="active">';
        $config['cur_tag_close'] = '</li>';

        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';

        $config['uri_segment'] = 3;
        $config['num_links'] = 2;

        $this->pagination->initialize($config);


        $data['loggedUserData'] = $this->member_model->get_member_by_id($this->user->id, 'member_hot_list, member_blocked_list');
        $data['includefile'] = "search_listing";
        $data['js'] = array("search", "alertbox");

        if ($session_data['perfectlist'] == 1) {
            $data['pagetitle'] = $this->lang->line('mem_my') . " " . $this->lang->line('mem_Matches');
            //$data['pagetitle'] = $this->lang->line('mem_PerfectMatch');
            $data['subpagetitle'] = $this->lang->line('mem_results');
            $data['pagetext'] = $this->lang->line('mem_results_text1');
        } elseif ($session_data['reverselist'] == 1) {
            $data['pagetitle'] = $this->lang->line('mem_reverse_match');
            $data['subpagetitle'] = $this->lang->line('mem_results');
            $data['pagetext'] = $this->lang->line('mem_results_text2');
        } else {
            $data['pagetitle'] = ''; //$this->lang->line('mem_quick');
            $data['subpagetitle'] = $this->lang->line('mem_search') . " " . $this->lang->line('mem_results');
            $data['pagetext'] = $this->lang->line('mem_results_text');
        }

        $this->master_view('', $data);
    }

    function showview() {
        $searchArray = $this->session->userdata('siteSearchData');
        $searchArray['searchView'] = str_replace('li-', '', $_POST['viewtype']);

        $this->session->set_userdata('siteSearchData', $searchArray);
    }

    function clickhotlist() {
        if (!$this->user->isLoggedIn() || $this->user->user_perm != 'Member') {
            echo $this->lang->line('men_login_required');
        } else {
            if ($this->member_model->permissionRequired('per_hot_section', false)) {
                $retVal = $this->member_model->updatehotlist($_POST['memID'], $_POST['Type']);
                echo $retVal . "###SEP###" . $_POST['Type'];
            } else
                echo $this->lang->line('men_plz_upgrade_membership');
        }
    }

    function clickblockedlist() {
        if (!$this->user->isLoggedIn() || $this->user->user_perm != 'Member') {
            echo $this->lang->line('men_login_required');
        } else {
            if ($this->member_model->permissionRequired('per_hot_section', false)) {
                $retVal = $this->member_model->updateblockedlist($_POST['memID'], $_POST['Type']);

                echo $retVal . "###SEP###" . $_POST['Type'];
            } else
                echo $this->lang->line('men_plz_upgrade_membership');
        }
    }

    function clicksendwink() {
        if (!$this->user->isLoggedIn() || $this->user->user_perm != 'Member') {
            echo $this->lang->line('men_login_required');
        } else {
            if ($this->member_model->permissionRequired('per_wink_section', false)) {
                $retVal = $this->member_model->sendWink($this->user->id, $_POST['memID']);
                echo $retVal;
            } else
                echo $this->lang->line('men_plz_upgrade_membership');
        }
    }
    
    function ClickSendPhotoRequest() {
        if (!$this->user->isLoggedIn() || $this->user->user_perm != 'Member') {
            echo $this->lang->line('men_login_required');
        } else {
            if ($this->member_model->permissionRequired('per_wink_section', false)) {
                $retVal = $this->member_model->PhotoRequest($this->user->id, $_POST['memID']);
                echo $retVal;
            } else
                echo $this->lang->line('men_plz_upgrade_membership');
        }
    }

    function clicknetwork() {
        if (!$this->user->isLoggedIn() || $this->user->user_perm != 'Member') {
            echo $this->lang->line('men_login_required');
        } else {
            if ($this->member_model->permissionRequired('per_network_section', false)) {
                if ($_POST['Type'] == 'Add') {
                    $retVal = $this->member_model->AddNetwork($this->user->id, $_POST['memID']);
                } elseif ($_POST['Type'] == 'Remove') {
                    if ($_POST['networkType'] == 'friendsnetwork') {
                        $retVal = $this->member_model->DelNetwork($this->user->id, $_POST['memID']);
                        if ($retVal == $this->lang->line('men_del_Network_msg_notexists')) {
                            $retVal = $this->member_model->DelNetwork($_POST['memID'], $this->user->id);
                        }
                    } else
                        $retVal = $this->member_model->DelNetwork($this->user->id, $_POST['memID']);
                }

                echo $retVal;
            } else
                echo $this->lang->line('men_plz_upgrade_membership');
        }
    }

    function updatenetworkrequest() {
        if (!$this->user->isLoggedIn() || $this->user->user_perm != 'Member') {
            echo $this->lang->line('men_login_required');
        } else {
            if ($this->member_model->permissionRequired('per_network_request_section', false)) {
                $retVal = $this->member_model->UpdateNetwork($_POST['memID'], $this->user->id, $_POST['Type']);
                if ($_POST['Type'] == 'Declined') {
                    $this->member_model->DelNetwork($_POST['memID'], $this->user->id);
                    $retVal = $this->lang->line('men_decline_Network_msg');
                } else {
                    $retVal = $this->lang->line('men_accept_Network_msg');
                }

                echo $retVal;
            } else
                echo $this->lang->line('men_plz_upgrade_membership');
        }
    }

    function sendtofriend() {
        $this->user->loginRequired(true);
        $this->member_model->permissionRequired('per_user_profile');
        $data['includefile'] = "send_to_friend";
        $data['loggedUserData'] = $this->member_model->get_member_by_id($this->user->id, 'member_email');
        $data['popupFlag'] = true;
        $data['js'] = array("search");

        $this->master_view('', $data);
    }

    function submitFriend() {
        $this->user->loginRequired(true);

        if ($this->uri->segment(3) == 'thanks') {
            $data['includefile'] = "messages_thanks";
            $data['popupFlag'] = true;
            $data['succMsg'] = $this->lang->line('men_send_friend_msg');

            $this->master_view('', $data);
        } else {
            $retVal = $this->member_model->insertSendToFriend($_POST);
            echo $this->lang->line('men_send_friend_msg');
        }
    }

    function accountlist() {
        $this->member_model->permissionRequired('per_user_profile');
        $this->user->loginRequired(true);

        $loggedUserData = $this->member_model->get_member_by_id($this->user->id, 'member_hot_list, member_blocked_list');        

        if (($this->uri->segment(3) == 'hotlist') || ($this->uri->segment(3) == 'hotlist-online')) {
            $this->member_model->permissionRequired('per_hot_section');
            $paramArray = array(
                'search_member_id' => (($loggedUserData['member_hot_list'] != '') ? $loggedUserData['member_hot_list'] : '0'),
                'reversematch' => '0',
            );

            if ($this->uri->segment(3) == 'hotlist-online')
                $paramArray['search_profile_disp'] = '4';
        }
        elseif (($this->uri->segment(3) == 'blockedlist') || ($this->uri->segment(3) == 'blockedlist-online')) {
            $paramArray = array(
                'search_member_id' => (($loggedUserData['member_blocked_list'] != '') ? $loggedUserData['member_blocked_list'] : '0'),
                'reversematch' => '0',
            );

            if ($this->uri->segment(3) == 'blockedlist-online')
                $paramArray['search_profile_disp'] = '4';
        }
        elseif ($this->uri->segment(3) == 'winklist') {
            $this->member_model->permissionRequired('per_wink_section');
            $paramArray = array(
                'reversematch' => '0',
                'winklist' => '1',
            );
        } elseif ($this->uri->segment(3) == 'invitesnetwork') {
            $this->member_model->permissionRequired('per_network_list_invite');
            $paramArray = array(
                'reversematch' => '0',
                'inviteslist' => '1',
            );
        } elseif ($this->uri->segment(3) == 'requestnetwork') {
            $this->member_model->permissionRequired('per_network_request_section');
            $paramArray = array(
                'reversematch' => '0',
                'requestlist' => '1',
            );
        } elseif (($this->uri->segment(3) == 'friendsnetwork') || ($this->uri->segment(3) == 'friendsnetwork-online')) {
            $this->member_model->permissionRequired('per_network_section');
            $paramArray = array(
                'reversematch' => '0',
                'friendslist' => '1',
            );

            if ($this->uri->segment(3) == 'friendsnetwork-online')
                $paramArray['search_profile_disp'] = '4';
        }
        elseif (($this->uri->segment(3) == 'friendsbirthday') || ($this->uri->segment(3) == 'friendsbirthday-online')) {
            $this->member_model->permissionRequired('per_network_section');
            $paramArray = array(
                'reversematch' => '0',
                'friendsbirthday' => '1',
            );

            if ($this->uri->segment(3) == 'friendsbirthday-online')
                $paramArray['search_profile_disp'] = '4';
        }
        elseif (($this->uri->segment(3) == 'newmember') || ($this->uri->segment(3) == 'newmember-online')) {
            $paramArray = array(
                'reversematch' => '0',
                'newmember' => '1',
            );

            if ($this->uri->segment(3) == 'newmember-online')
                $paramArray['search_profile_disp'] = '4';
        }
        elseif (($this->uri->segment(3) == 'view') || ($this->uri->segment(3) == 'view-online')) {
            $paramArray = array(
                'reversematch' => '0',
                'view' => '1',
            );

            if ($this->uri->segment(3) == 'view-online')
                $paramArray['search_profile_disp'] = '4';
        }

        $this->session->set_userdata('siteMyAcData', $paramArray);

        if ($this->input->post('sort_by')) {
            $paramArray = $this->session->userdata('siteMyAcData');
            $paramArray['sort_by'] = $this->input->post('sort_by');

            $this->session->set_userdata('siteMyAcData', $paramArray);

            $searchArray = $this->session->userdata('siteSearchData');
            $searchArray['sort_by'] = $this->input->post('sort_by');

            $this->session->set_userdata('siteSearchData', $searchArray);
        } else {
            $paramArray = $this->session->userdata('siteMyAcData');
            $searchArray = $this->session->userdata('siteSearchData');

            $paramArray['sort_by'] = (($searchArray['sort_by'] != '') ? $searchArray['sort_by'] : 'member_created_date');
            $paramArray['searchView'] = (($searchArray['searchView'] != '') ? $searchArray['searchView'] : 'list');

            $searchArray['sort_by'] = $paramArray['sort_by'];
            $searchArray['searchView'] = $paramArray['searchView'];

            $this->session->set_userdata('siteMyAcData', $paramArray);
            $this->session->set_userdata('siteSearchData', $searchArray);
        }

        $param_data = $this->session->userdata('siteMyAcData');

        if ($this->uri->segment(4))
            $this->session->set_userdata('offset', $this->uri->segment(4));
        else
            $this->session->set_userdata('offset', 0);

        $this->session->set_userdata('page_size', 16); //$this->system->page_size);
        $config['per_page'] = $this->session->userdata('page_size');

        $data['member_record'] = $this->member_model->viewAllSiteMember((int) $config['per_page'], (int) $this->session->userdata('offset'), $param_data);
        $data['members_count'] = $this->member_model->total_record;

        $data['online_count'] = $this->member_model->get_online_results((int) $config['per_page'], (int) $this->session->userdata('memberoffset'), $param_data, TRUE);

        if ($this->uri->segment(3) == 'hotlist')
            $config['base_url'] = base_url() . 'member/accountlist/hotlist';
        elseif ($this->uri->segment(3) == 'hotlist-online')
            $config['base_url'] = base_url() . 'member/accountlist/hotlist-online';
        elseif ($this->uri->segment(3) == 'blockedlist')
            $config['base_url'] = base_url() . 'member/accountlist/blockedlist';
        elseif ($this->uri->segment(3) == 'blockedlist-online')
            $config['base_url'] = base_url() . 'member/accountlist/blockedlist-online';
        elseif ($this->uri->segment(3) == 'winklist')
            $config['base_url'] = base_url() . 'member/accountlist/winklist';
        elseif ($this->uri->segment(3) == 'invitesnetwork')
            $config['base_url'] = base_url() . 'member/accountlist/invitesnetwork';
        elseif ($this->uri->segment(3) == 'requestnetwork')
            $config['base_url'] = base_url() . 'member/accountlist/requestnetwork';
        elseif ($this->uri->segment(3) == 'friendsnetwork')
            $config['base_url'] = base_url() . 'member/accountlist/friendsnetwork';
        elseif ($this->uri->segment(3) == 'friendsnetwork-online')
            $config['base_url'] = base_url() . 'member/accountlist/friendsnetwork-online';
        elseif ($this->uri->segment(3) == 'newmember')
            $config['base_url'] = base_url() . 'member/accountlist/newmemebr';
        elseif ($this->uri->segment(3) == 'newmember-online')
            $config['base_url'] = base_url() . 'member/accountlist/newmemebr-online';
        elseif ($this->uri->segment(3) == 'view')
            $config['base_url'] = base_url() . 'member/accountlist/view';
        elseif ($this->uri->segment(3) == 'view-online' && $this->uri->segment(4) == 'view')
            $config['base_url'] = base_url() . 'member/accountlist/view-online';

        $config['total_rows'] = $data['members_count'];

        $config['full_tag_open'] = '<ul class="pagination pull-right">';
        $config['full_tag_close'] = '</ul>';

        $config['first_link'] = "<i class='fa fa-angle-double-left fa-lg'></i>";
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';

        $config['prev_link'] = "<i class='fa fa-angle-left fa-lg'></i>";
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';

        $config['last_link'] = "<i class='fa fa-angle-double-right fa-lg'></i>";
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';

        $config['next_link'] = "<i class='fa fa-angle-right fa-lg'></i>";
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="active">';
        $config['cur_tag_close'] = '</li>';

        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';

        $config['uri_segment'] = 4;
        $config['num_links'] = 2;

        $this->pagination->initialize($config);

        $data['loggedUserData'] = $loggedUserData;
        $data['includefile'] = "search_listing";
        $data['js'] = array("search");
        $data['pagetitle'] = $this->lang->line('mem_my_account') . " :::: ";

        if ($this->uri->segment(3) == 'hotlist')
            $data['subpagetitle'] = $this->lang->line('mem_my_hotlist');
        elseif ($this->uri->segment(3) == 'hotlist-online')
            $data['subpagetitle'] = $this->lang->line('mem_my_hotlist') . '&nbsp;' . $this->lang->line('online');
        elseif ($this->uri->segment(3) == 'blockedlist')
            $data['subpagetitle'] = $this->lang->line('mem_my_blockedlist');
        elseif ($this->uri->segment(3) == 'blockedlist-online')
            $data['subpagetitle'] = $this->lang->line('mem_my_blockedlist') . '&nbsp;' . $this->lang->line('online');
        elseif ($this->uri->segment(3) == 'winklist')
            $data['subpagetitle'] = $this->lang->line('men_winked_at_you');
        elseif ($this->uri->segment(3) == 'invitesnetwork')
            $data['subpagetitle'] = $this->lang->line('men_invites_network');
        elseif ($this->uri->segment(3) == 'requestnetwork')
            $data['subpagetitle'] = $this->lang->line('men_request_network');
        elseif ($this->uri->segment(3) == 'friendsnetwork')
            $data['subpagetitle'] = $this->lang->line('men_friends_network');
        elseif ($this->uri->segment(3) == 'friendsnetwork-online')
            $data['subpagetitle'] = $this->lang->line('men_friends_network') . '&nbsp;' . $this->lang->line('online');
        elseif ($this->uri->segment(3) == 'newmember')
            $data['subpagetitle'] = $this->lang->line('new_members1');
        elseif ($this->uri->segment(3) == 'newmember-online')
            $data['subpagetitle'] = $this->lang->line('new_members1') . '&nbsp;' . $this->lang->line('online');
        elseif ($this->uri->segment(3) == 'view')
            $data['subpagetitle'] = $this->lang->line('view');
        elseif ($this->uri->segment(3) == 'view-online')
            $data['subpagetitle'] = $this->lang->line('view') . '&nbsp;' . $this->lang->line('online');

        $this->master_view('', $data);
    }

    function quickview() {
        $data[member_membership_id] = $this->member_model->get_member_by_id($this->user->id);
        $data['memberInfo'] = $this->member_model->getMemInfobyId($this->uri->segment(3), 1);

        if ($this->uri->segment(3) != $this->user->id) {
            $this->load->model('member_viewed_model');
            $this->member_viewed_model->member_view($data['memberInfo']['user_auth_id']);
        }

        $data['loggedUserData'] = $this->member_model->get_member_by_id($this->user->id, 'member_hot_list, member_blocked_list');
        $data['js'] = array("search", "alertbox");
        $data['includefile'] = "quick-profile";
        $data['popupFlag'] = true;

        $this->master_view('', $data);
    }

    function profile()
    {	
	
        $this->load->library('Mobile_Detect');
        $detect = new Mobile_Detect;
      		if(($detect->isMobile() || $detect->isNexusTablet()) && !$detect->isiPad()) {      		
      		
			   if ($this->uri->segment(4) == 'sendFriend') {
	      			
	      			header('Location: http://m.singaporefriendfinder.com/home/myaccount/'.$this->uri->segment(3).'/'.$this->uri->segment(4).'/'.$this->uri->segment(5).'/'.$this->uri->segment(6).'');	
	      		}
	      		else
	      		{
					header('Location: http://m.singaporefriendfinder.com/');	
			   		exit;
				}	  	
			
		}  
		#allow to view profile only if the member is active
        $member_id = $this->uri->segment(3);
        $member_data = $this->member_model->get_member_by_id($member_id, 'member_active,member_auth_id');
        //print_r($member_data);
		$data['member_block_data'] = $this->member_model->get_member_by_id($this->uri->segment(3), 'member_blocked_list');
        //print_r($data['member_block_data']);

        if (empty($member_data) || $member_data['member_active'] == '0') {
            $data['js'] = array("search"); //"jquery.raty"
            $data['includefile'] = "unauthorized_profile";
        } else {

            if ($this->uri->segment(4) == 'sendFriend') {
                $friendArray = array();
                $friendArray['viewMember'] = $this->uri->segment(3);
                $friendArray['sendID'] = $this->uri->segment(6);
                $friendArray['uniqueCode'] = $this->uri->segment(5);
				//echo '<pre>';print_r($friendArray);die;
                $retData = $this->member_model->getFriendData($friendArray['uniqueCode'], $friendArray['sendID']);

                if (is_array($retData)) {
                    $getFData = $retData;
                    $friendArray = array_merge($friendArray, $getFData);

                    $this->session->set_userdata('sendFriendData', $friendArray);
                }
            }
            $this->session->set_userdata('redirect_url', base_url(uri_string()));
			
			if($this->uri->segment(4) == "newface")	
            $this->user->loginRequired(true, '/member/register/');
			else
			$this->user->loginRequired(true, '/member/login/');
				
            $this->member_model->permissionRequired('per_user_profile');

            $this->load->model('member_album_model');
            $this->load->model('member_wall_model');

            if ($_POST['btnWallPost'] == 'WallPost') {
                $data = array(
                    'wall_member_id' => $this->uri->segment(3),
                    'wall_write_member_id' => $this->user->id,
                    'wall_text' => $_POST['wall_text'],
                );
                $result = $this->member_wall_model->insertWall($data);

                redirect('member/profile/' . $this->uri->segment(3) . '/wall', 'location');
                return;
            } elseif ($_POST['btnWallCommentPost'] == 'WallCommentPost') {
                $data = array(
                    'comment_wall_id' => $_POST['comment_wall_id'],
                    'comment_write_member_id' => $this->user->id,
                    'comment_text' => $_POST['comment_text'],
                );
                $result = $this->member_wall_model->insertWallComment($data);

                redirect('member/profile/' . $this->uri->segment(3) . '/wall', 'location');
                return;
            } elseif ($this->uri->segment(4) == 'walldelete') {
                $result = $this->member_wall_model->deleteWall($this->uri->segment(5));

                redirect('member/profile/' . $this->uri->segment(3) . '/wall', 'location');
                return;
            } elseif ($this->uri->segment(4) == 'wallcommentdelete') {
                $result = $this->member_wall_model->deleteWallComment($this->uri->segment(5));

                redirect('member/profile/' . $this->uri->segment(3) . '/wall', 'location');
                return;
            } elseif ($this->uri->segment(4) == 'wallcommentlike') {
                $result = $this->member_wall_model->likeWallComment($this->uri->segment(5), $this->user->id);

                redirect('member/profile/' . $this->uri->segment(3) . '/wall', 'location');
                return;
            }

            $data['memberInfo'] = $this->member_model->getMemInfobyId($this->uri->segment(3), 1);
			//echo '<pre>';print_r($data['memberInfo']);die;
            $data['profilepic'] = $this->member_model->get_profile_photo_by_id($this->uri->segment(3));
			
            if ($this->uri->segment(3) != $this->user->id) {            
                $this->load->model('member_viewed_model');
                $data['matchInfo'] = $this->member_viewed_model->member_view($data['memberInfo']['user_auth_id']);
            //echo '<pre>';print_r($data['matchInfo']); die;
            }

            /* --------------------- added by pradip for quick search -------------- */
            if ($this->session->userdata('quick_search_data')) {
   	          $data['matchInfo'] = $this->member_model->getQuicksearchMatchInfo($this->uri->segment(3), $this->user->id);
               //echo '<pre>';print_r($data['matchInfo']);die;
               
            } elseif ($this->session->userdata('adv_search_data')) {
                $data['matchInfo'] = $this->member_model->getAdvSearchMatchInfo($this->uri->segment(3), $this->user->id);
                 
            }
            /* --------------------- added by E090 only elseif of search_username and siteSearchData -------------- */
            /*elseif ($this->session->userdata('search_username')) {
                $data['matchInfo'] = $this->member_model->getAdvSearchMatchInfo($this->uri->segment(3), $this->user->id);
                
            }*/
            /*elseif ($this->session->userdata('siteSearchData')) {
               $data['matchInfo'] = $this->member_model->getReverseMatchInfo($this->uri->segment(3), $this->user->id);
               //echo '<pre>';print_r($data['matchInfo']);die;
               //$data['matchInfo'] = $this->member_model->perfectMatchParam($this->uri->segment(3), $this->user->id);
               
            }*/
           
            
             else {
                $data = $this->session->userdata('siteSearchData');
               //echo '<pre>';print_r($data);
                if ($data['reverselist'] == '1') {
                	
                    $data['matchInfo'] = $this->member_model->getReverseMatchInfo($this->uri->segment(3), $this->user->id);
                    
                    
                }                
                else {
                    $data['matchInfo'] = $this->member_model->getMatchInfo($this->uri->segment(3), $this->user->id);
                   //echo '<pre>';print_r($data['matchInfo']);
                    
                }
            }
            /*    ----------------------------------------------    */

            //$data['matchInfo'] = $this->member_model->getMatchInfo($this->uri->segment(3), $this->user->id);
            $data['memberInfo'] = $this->member_model->getMemInfobyId($this->uri->segment(3), 1);
			//echo '<pre>';print_r($data['matchInfo']);
            $data['RecentPhotoInfo'] = $this->member_album_model->getRecentPhotoInfobyMem($this->uri->segment(3));
            $data['WallInfo'] = $this->member_wall_model->getWallInfobyMem($this->uri->segment(3), 2);
			//echo '<pre>';print_r($data['WallInfo']);
            if ($this->member_model->permissionRequired('per_photo_video_section', false)) {
                $data['photoInfo'] = $this->member_album_model->getPhotoInfobyMem($this->uri->segment(3));

                $data['videoInfo'] = $this->member_album_model->getVideoInfobyMem($this->uri->segment(3));
            } else {
                $data['upgradeInfo'] = true;
            }

            $data['loggedUserData'] = $this->member_model->get_member_by_id($this->user->id, 'member_hot_list, member_blocked_list');
			
            $data['get_rate_array'] = $this->member_model->get_hotnot_member($member_data['member_auth_id']);
            $data['get_rate'] = $data['get_rate_array']['rate'];

            //get based on rate
            $data['get_average_rate_array'] = $this->member_model->get_hotnot_member($member_data['member_auth_id'], TRUE);
            $this->db->from("western_zodiac");
		    $this->db->where(array("zodiac_id" => 10));	        
	        $query = $this->db->get();
	                
	        $data['western_zodiac_result'] = $query->result_array();  
//echo '<pre>';print_r($data['western_zodiac_result']);
            $sum = 0;
            foreach ($data['get_average_rate_array'] as $key => $val) {
                $sum += $val['rate'];
            }

            //echo $sum;
            if ($sum != 0)
                $average = $sum / count($data['get_average_rate_array']);
            else
                $average = $sum;

            $data['get_total'] = $sum;
            $data['get_average_rate'] = $average;


            $data['js'] = array("search"); //"jquery.raty"
            $data['includefile'] = "profile-view";
        }
        //  print_r("<pre>");
//print_r($data);
        $this->master_view('', $data);
    }

    function wallview() {
        $this->user->loginRequired(true, '/member/register/');

        $this->load->model('member_wall_model');

        if ($_POST['btnWallCommentPost'] == 'WallCommentPost') {
            $data = array(
                'comment_wall_id' => $_POST['comment_wall_id'],
                'comment_write_member_id' => $this->user->id,
                'comment_text' => $_POST['comment_text'],
            );
            $result = $this->member_wall_model->insertWallComment($data);

            redirect('member/wallview/' . $this->uri->segment(3), 'location');
            return;
        } elseif ($this->uri->segment(4) == 'walldelete') {
            $result = $this->member_wall_model->deleteWall($this->uri->segment(5));

            redirect('member/wallview/' . $this->uri->segment(3), 'location');
            return;
        } elseif ($this->uri->segment(4) == 'wallcommentdelete') {
            $result = $this->member_wall_model->deleteWallComment($this->uri->segment(5));

            redirect('member/wallview/' . $this->uri->segment(3), 'location');
            return;
        } elseif ($this->uri->segment(4) == 'wallcommentlike') {
            $result = $this->member_wall_model->likeWallComment($this->uri->segment(5), $this->user->id);

            redirect('member/wallview/' . $this->uri->segment(3), 'location');
            return;
        }

        $data['WallInfo'] = $this->member_wall_model->getWallInfobyMem($this->uri->segment(3));

        $data['popupFlag'] = true;
        $data['js'] = array("search");
        $data['includefile'] = "profile-wall-view";

        $this->master_view('', $data);
    }

    function googlemap() {
        $this->member_model->permissionRequired('per_user_profile');
        if ($this->uri->segment(3))
            $data['Info1'] = $this->member_model->getMemAddInfobyId($this->uri->segment(3));

        /*if ($this->uri->segment(4))
            $data['Info2'] = $this->member_model->getMemAddInfobyId($this->uri->segment(4));*/

        $data['includefile'] = "google-map";
        $data['popupFlag'] = true;

        $this->master_view('', $data);
    }

    function mysignature() {
        if ($this->input->post('submit')) {
            $this->load->library('form_validation');
            $this->form_validation->set_rules("signature", "lang:signature", 'required|maxlength[255]');

            if ($this->form_validation->run() == FALSE) {
                $this->master_view('mysignature', $data);
                return;
            }

            $this->member_model->updateByParam('member_master', array('member_signature' => $this->input->post('signature')), array('member_auth_id' => $this->user->user_auth_id));

            $data['includefile'] = "messages_thanks";
            $data['popupFlag'] = true;
            $data['ParantLoadFlag'] = false;
            $data['succMsg'] = $this->lang->line('signature_update');

            $this->master_view('', $data);
            return;
        }

        $data['member_signature'] = $this->member_model->get_member_by_id($this->user->id, 'member_signature');
        $data['includefile'] = 'mysignature';
        $data['popupFlag'] = true;
        $data['js'] = array("blog");

        $this->master_view('', $data);
    }

    function tell_a_friend() {
        if ($this->input->post('btnTellFriend')) {
            $this->load->library('form_validation');
            $this->form_validation->set_rules('send_to_mail', "lang:men_to", 'required');

            if ($this->form_validation->run() == FALSE) {
                $data['includefile'] = 'telltofriend';
                $data['js'] = array("search", "alertbox");
                $this->master_view('', $data);
                return;
            }

            #word censor
            $this->load->model('Bad_Words_Model', 'bad_words');
            $result = $this->bad_words->word_censor($this->input->post('send_messages'));

            if ($result == FALSE) {
                $data['error'] = $this->lang->line('bad_word_found');
                $data['includefile'] = 'telltofriend';
                $data['js'] = array("search");
                $this->master_view('', $data);
                return;
            } else {
                $retVal = $this->member_model->insertSendToFriend($_POST);

                $this->session->set_flashdata('notification', $this->lang->line('men_send_friend_msg'));
                redirect('member/tell_a_friend');
            }
        }

        $data['includefile'] = 'telltofriend';
        $data['js'] = array("search", "alertbox");

        $this->master_view('', $data);
    }

    /**
     * function hot_not.
     * @access public
     */
     
    //13OCt2015 E090 user comment old function for client changes and new function available below this function
     
    /*function hot_not($member_id = '', $offset = 0) {

        $this->user->loginRequired(true);
        $this->member_model->permissionRequired('per_hotornot');

        $data['title'] = $this->lang->line('hot_not');
        $data['js'] = array("jquery.bxslider.min", "search", "hotnot");
        $data['top_members'] = $this->member_model->top_memebrs('8');

        $data['NetworkFriends'] = $this->member_model->get_network_friends_info();

        $data['sliderindex'] = 0;
        $memberids = array();
        foreach ($data['NetworkFriends'] as $key => $val) {
            array_push($memberids, $val['member_id']);
        }
        #if the member id passed as paraamter is not in network
        if ($member_id != '') {

            if (!in_array($member_id, $memberids)) {

                $data['includefile'] = 'hotnot';
                $this->master_view('', $data);
                return;
            }
        }

        #if no paramter
        if ($member_id == '') {

            #choose the member whose rating has not been done yet 
            #if  all memberes rating done by default 1st member active
            foreach ($data['NetworkFriends'] as $key => $val) {

                $result = $this->member_model->get_hotnot_member($val['member_auth_id']);
                #if rating is left for any member
                if (empty($result)) {

                    $member_id = $val['member_id'];
                    $next = $key + 1;

                    break;
                }
            }
            #if all members rating are done then 1st member
            if ($member_id == '') {
                $member_id = $data['NetworkFriends']['0']['member_id'];
                $next = '1';
            }
        } else {
            foreach ($data['NetworkFriends'] as $key => $val) {

                if ($val['member_id'] == $member_id) {

                    $member_id = $val['member_id'];
                    $next = $key + 1;
                }
            }
        }

        $data['next_id'] = $data['NetworkFriends'][$next]['member_id'];
        if ($next > 9) {
            $data['sliderindex'] = ($next - 9);
        } else {
            $data['sliderindex'] = 0;
        }

        #if no next element redirect to first
        if (empty($data['next_id'])) {
            $data['next_id'] = $data['NetworkFriends']['0']['member_id'];
        }


        $data['member_details'] = $this->member_model->get_network_member_by_id($member_id);
        //print_r($data['member_details']);

        $data['get_network_freinds'] = $this->member_model->get_network_member_by_id($memberids, TRUE);

        //print_r($data['get_network_freinds']);die;

        $data['get_rate_array'] = $this->member_model->get_hotnot_member($data['member_details']['member_auth_id']);

        $data['get_rate'] = $data['get_rate_array']['rate'];
        //	print_r($data['get_rate']);die;
        //get based on rate
        $data['get_average_rate_array'] = $this->member_model->get_hotnot_member($data['member_details']['member_auth_id'], TRUE);

        $sum = 0;
        foreach ($data['get_average_rate_array'] as $key => $val) {
            $sum += $val['rate'];
        }

        //echo $sum;
        if ($sum != 0)
            $average = $sum / count($data['get_average_rate_array']);
        else
            $average = $sum;

        $data['get_total'] = $sum;
        $data['get_average_rate'] = $average;
        $this->member_model->update_avg($data['get_average_rate'], $member_id);

        $data['includefile'] = 'hotnot';

        $this->master_view('', $data);
    }*/


		function hot_not($member_id = '', $offset = 0) {

        $this->user->loginRequired(true);
        $this->member_model->permissionRequired('per_hotornot');

        $data['title'] = $this->lang->line('hot_not');
        $data['js'] = array("jquery.bxslider.min", "search", "hotnot");
        $data['top_members_new'] = $this->member_model->top_memebrs_new();
        //echo '<pre>';print_r($data['top_members_new']);
		$data['top_members'] = $this->member_model->top_memebrs('8');
        $data['NetworkFriends'] = $this->member_model->get_network_friends_info();

        $data['sliderindex'] = 0;
        $memberids = array();
        foreach ($data['top_members_new'] as $key => $val) {
            array_push($memberids, $val['member_id']);
        }
        #if the member id passed as paraamter is not in network
        if ($member_id != '') {

            if (!in_array($member_id, $memberids)) {

                $data['includefile'] = 'hotnot';
                $this->master_view('', $data);
                return;
            }
        }

        #if no paramter
        if ($member_id == '') {

            #choose the member whose rating has not been done yet 
            #if  all memberes rating done by default 1st member active
            foreach ($data['top_members_new'] as $key => $val) {

                $result = $this->member_model->get_hotnot_member($val['member_auth_id']);
                #if rating is left for any member
                if (empty($result)) {

                    $member_id = $val['member_id'];
                    $next = $key + 1;

                    break;
                }
            }
            #if all members rating are done then 1st member
            if ($member_id == '') {
                $member_id = $data['top_members_new']['0']['member_id'];
                $next = '1';
            }
        } else {
            foreach ($data['top_members_new'] as $key => $val) {

                if ($val['member_id'] == $member_id) {

                    $member_id = $val['member_id'];
                    $next = $key + 1;
                }
            }
        }

        $data['next_id'] = $data['top_members_new'][$next]['member_id'];
        if ($next > 9) {
            $data['sliderindex'] = ($next - 9);
        } else {
            $data['sliderindex'] = 0;
        }

        #if no next element redirect to first
        if (empty($data['next_id'])) {
            $data['next_id'] = $data['top_members_new']['0']['member_id'];
        }


        $data['member_details'] = $this->member_model->get_network_member_by_id($member_id);
        //echo '<pre>';print_r($data['member_details']);
        $data['get_network_freinds'] = $this->member_model->get_network_member_by_id($memberids, TRUE);

        //print_r($data['get_network_freinds']);die;

        $data['get_rate_array'] = $this->member_model->get_hotnot_member($data['member_details']['member_auth_id']);
//print_r($data['get_rate_array']);die;
        $data['get_rate'] = $data['get_rate_array']['rate'];
        	//print_r($data['get_rate_array']);
        //get based on rate
        $data['get_average_rate_array'] = $this->member_model->get_hotnot_member($data['member_details']['member_auth_id'], TRUE);

        $sum = 0;
        foreach ($data['get_average_rate_array'] as $key => $val) {
            $sum += $val['rate'];
        }

        //echo $sum;
        if ($sum != 0)
            $average = $sum / count($data['get_average_rate_array']);
        else
            $average = $sum;

        $data['get_total'] = $sum;
        $data['get_average_rate'] = $average;
        $this->member_model->update_avg($data['get_average_rate'], $member_id);

        $data['includefile'] = 'hotnot';

        $this->master_view('', $data);
    }

    function update_hotnot() {
        if (!$this->user->isLoggedIn() || $this->user->user_perm != 'Member') {
            echo $this->lang->line('men_login_required');
        } else {
            $member_auth_id = $this->member_model->get_info_by_id($this->uri->segment(3));
            $retVal = $this->member_model->updatehotlist2($member_auth_id['member_auth_id'], $_POST['rate']);

            echo $retVal;
        }
    }

    function top_members($offset = 0) {
        $paramArray = array(
            'reversematch' => '0',
            'toplist' => '1',
        );

        $this->session->set_userdata('siteMyAcData', $paramArray);

        if ($this->input->post('sort_by')) {
            $paramArray = $this->session->userdata('siteMyAcData');
            $paramArray['sort_by'] = $this->input->post('sort_by');

            $this->session->set_userdata('siteMyAcData', $paramArray);

            $searchArray = $this->session->userdata('siteSearchData');
            $searchArray['sort_by'] = $this->input->post('sort_by');

            $this->session->set_userdata('siteSearchData', $searchArray);
        } else {
            $paramArray = $this->session->userdata('siteMyAcData');
            $searchArray = $this->session->userdata('siteSearchData');

            $paramArray['sort_by'] = (($searchArray['sort_by'] != '') ? $searchArray['sort_by'] : 'member_created_date');
            $paramArray['searchView'] = (($searchArray['searchView'] != '') ? $searchArray['searchView'] : 'list');

            $searchArray['sort_by'] = $paramArray['sort_by'];
            $searchArray['searchView'] = $paramArray['searchView'];

            $this->session->set_userdata('siteMyAcData', $paramArray);
            $this->session->set_userdata('siteSearchData', $searchArray);
        }

        $param_data = $this->session->userdata('siteMyAcData');


        if ($offset != 0)
            $this->session->set_userdata('memberoffset', $offset);
        elseif ($offset == 0)
            $this->session->unset_userdata('memberoffset');

        $this->session->set_userdata('page_size', 12); //$this->system->page_size);
        $config['per_page'] = $this->session->userdata('page_size');

        $data['member_record'] = $this->member_model->viewAllSiteMember((int) $config['per_page'], (int) $this->session->userdata('memberoffset'), $param_data);
        $data['members_count'] = $this->member_model->total_record;
		$data['online_record'] = $this->member_model->get_online('', FALSE, '', $sort_by, '', 0);
        $data['online_count'] = $this->member_model->get_online_results((int) $config['per_page'], (int) $this->session->userdata('memberoffset'), $param_data, TRUE);

        //$data['top_members'] = $this->member_model->top_memebrs($config['per_page']);
        //$data['total_top'] =  $this->member_model->total_record;

        $config['base_url'] = base_url() . 'member/top_members';
        $config['total_rows'] = $data['members_count'];
        //$config['total_rows'] =  $data['total_top'];

        $config['full_tag_open'] = '<ul class="pagination pull-right">';
        $config['full_tag_close'] = '</ul>';

        $config['first_link'] = "<i class='fa fa-angle-double-left fa-lg'></i>";
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';

        $config['prev_link'] = "<i class='fa fa-angle-left fa-lg'></i>";
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';

        $config['last_link'] = "<i class='fa fa-angle-double-right fa-lg'></i>";
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';

        $config['next_link'] = "<i class='fa fa-angle-right fa-lg'></i>";
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="active">';
        $config['cur_tag_close'] = '</li>';

        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';

        $config['uri_segment'] = 3;
        $config['num_links'] = 2;

        $this->pagination->initialize($config);

        $data['loggedUserData'] = $loggedUserData;
        //$data['includefile'] = 'top-members';
        $data['includefile'] = "search_listing";
        $data['js'] = array("search","alertbox");
        $data['pagetitle'] = $this->lang->line('mem_top') . " ";
        $data['subpagetitle'] = $this->lang->line('mem_Members');

        $this->master_view('', $data);
    }

    function update_live() {
        $this->load->model('mailbox_model', 'mailbox');
        if (!$this->user->logged_in)
            redirect("home/index");

        $this->member_model->update_live($this->user->user_auth_id);

        $get_unread_chat = $this->mailbox->get_unread_chat_message();
        $data['get_unread_chat_count'] = $this->mailbox->total_record;

        $get_online = $this->member_model->get_online('', FALSE, '', $order, '', 0);
        $data['get_online_count'] = $this->member_model->total_record;
        /* echo $data['get_unread_chat_count'];
         */
        echo json_encode($data);
    }

    /**
     * function my_organizer()
     * 
     */
    function my_organizer($activity_date = '') {
        $this->user->loginRequired(true);
        $this->member_model->permissionRequired('per_my_organizer');
        $data['js'] = array("fullcalendar.min", "bootbox.min", "myorganizer", "alertbox");

        $this->session->set_userdata('act_date', $activity_date);

        $join_str = array('membership_master' => 'member_membership_id = membership_id');
        $data['memresult'] = $this->member_model->get_member_by_id($this->user->id, 'membership_id, membership_title, member_fund_balance', $join_str);
        #for expiry days
        if ($data['memresult']['membership_id'] == '1' || $data['memresult']['membership_id'] == '4') {
            $expiry_info = $this->billing->billingdata_by_id();
            #for free 
            if ($expiry_info['membership_id'] == '2') {
                $data['day_res'] = 'Not Applicable';
            } else {

                if (($expiry_info['pay_sub_start_date'] <= date('Y-m-d')) && (date('Y-m-d') <= $expiry_info['pay_sub_end_date'])) {
                    $data['day_res'] = $this->functions->datediff('d', date('Y-m-d'), $expiry_info['pay_sub_end_date']);
                } else {
                    $data['day_res'] = 'Expired';
                }
            }
        }
        if ($date = '') {
            $data['activity_info'] = $this->member_model->get_all_activities();
        } else {
            $data['activity_info'] = $this->member_model->get_all_activities($activity_date);
        }


        $data['title'] = $this->lang->line('my_organizer');

        $data['includefile'] = "myorganizer";

        $this->master_view('', $data);
    }

    function billing_history($offset = 0) {
        $this->user->loginRequired(true);
        $this->member_model->permissionRequired('per_my_organizer');
        $this->load->model('Billing_model', 'billing');


        $data['title'] = $this->lang->line('history');

        $config['base_url'] = base_url() . 'member/billing_history';
        $config['per_page'] = $this->system->page_size;
        $config['full_tag_open'] = '<ul class="pagination pull-right">';
        $config['full_tag_close'] = '</ul>';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li>';
        $config['first_link'] = "<i class='fa fa-angle-double-left fa-lg'></i>";
        $config['last_link'] = "<i class='fa fa-angle-double-right fa-lg'></i>";
        $config['prev_link'] = "<i class='fa fa-angle-left fa-lg'></i>";
        $config['next_link'] = "<i class='fa fa-angle-right fa-lg'></i>";
        $config['prev_tag_close'] = '</li>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active">';
        $config['cur_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['uri_segment'] = 3;

        if ($offset != 0)
            $this->session->set_userdata('offset', $offset);
        elseif ($offset == 0)
            $this->session->unset_userdata('offset');

        $join_str = array('member_master' => array('pay_member_id' => 'member_id'));
        $where_str[] = array('pay_member_id =' => $this->user->id);

        $data['billing'] = $this->billing->view_all(NULL, $where_str, $join_str, 'pay_date DESC', (int) $config['per_page'], (int) $this->session->userdata('offset'));		
        $data['billing_count'] = $this->billing->total_record;
        $config['total_rows'] = $this->billing->total_record;
        $data['membership_title'] = $this->billing->get_membership_title();
        $data['array'] = $this->billing->init_array();

        $data['includefile'] = "billing_history";

        $this->master_view('', $data);
    }

    function create_activity() {
        $this->user->loginRequired(true);
        $data['js'] = array("activity");

        if (!$this->member_model->permissionRequired('per_my_organizer', false)) {
            
        }

        $data['includefile'] = "create_activity";
        $data['popupFlag'] = true;

        $this->master_view('', $data);
    }

    function display_events() {

        $avail_data = $this->member_model->activity_count();

        foreach ($avail_data as $row) {

            //$eventsArray['id'] = $i;
            $eventsArray['title'] = $row->act_count;
            $eventsArray['start'] = date("Y-m-d", strtotime($row->activity_datetime));
            $eventsArray['url'] = base_url() . 'member/my_organizer/' . date("Y-m-d", strtotime($row->activity_datetime));

            $eventsArray['allDay'] = "false";
            $events[] = $eventsArray;
        }

        echo json_encode($events);
    }

    /**
     * function incoming_delete($offset='',$chatid='')
     * delete incoming messages
     * 
     */
    function activity_delete($activityid = '') {
        if (empty($activityid)) {
            redirect("/member/my_organizer/" . $this->session->userdata('act_date'));
        }

        $this->member_model->delete_activity_msg($activityid);
        $this->session->set_flashdata('notification', $this->lang->line('activity_delete_success'));

        redirect("/member/my_organizer/" . $this->session->userdata('act_date'));
    }

    function member_details($member_id) {
        $join_str = array('auth_user' => 'user_auth_id = member_auth_id');

        $member_details = $this->member_model->get_member_by_id($member_id, 'user_login_id,member_id,member_profile_image,TIMESTAMPDIFF(YEAR,member_birthdate,CURDATE()) AS Age', $join_str);
        $detail['name'] = $member_details['user_login_id'];
        $detail['age'] = $member_details['Age'];
        echo json_encode($detail);
    }

    function photo_gallery($offset = 0) {
        $this->user->loginRequired(true);
        $this->member_model->permissionRequired('per_my_profile');

        // define('account_no_side_panel',TRUE); 

        $this->load->model('member_album_model');
        if ($offset != 0)
            $this->session->set_userdata('memberoffset', $offset);
        elseif ($offset == 0)
            $this->session->unset_userdata('memberoffset');
        if ($this->input->post('sort_by')) {
            $searchArray['sort_by'] = $this->input->post('sort_by');
            $this->session->set_userdata('siteSearchData', $searchArray);
        }
        $session_data = $this->session->userdata('siteSearchData');
//print_r($session_data);
        $this->session->set_userdata('page_size', 12); //$this->system->page_size);
        $config['per_page'] = $this->session->userdata('page_size');

        $data['all_photos'] = $this->member_album_model->get_photo_gallery((int) $this->session->userdata('memberoffset'), (int) $config['per_page'], $session_data);


        $data['all_photosCnt'] = $this->member_album_model->total_record;

        $config['base_url'] = base_url() . 'member/photo_gallery';
        $config['total_rows'] = ($data['url_variable'] == "online_user") ? $data['online_count'] : $data['all_photosCnt'];

        $config['full_tag_open'] = '<ul class="pagination pull-right">';
        $config['full_tag_close'] = '</ul>';

        $config['first_link'] = "<i class='fa fa-angle-double-left fa-lg'></i>";
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';

        $config['prev_link'] = "<i class='fa fa-angle-left fa-lg'></i>";
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';

        $config['last_link'] = "<i class='fa fa-angle-double-right fa-lg'></i>";
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';

        $config['next_link'] = "<i class='fa fa-angle-right fa-lg'></i>";
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="active">';
        $config['cur_tag_close'] = '</li>';

        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';

        $config['uri_segment'] = 3;
        $this->pagination->initialize($config);
        //$data['online_count'] = $this->member_model->get_online_results((int) $config['per_page'], (int) $this->session->userdata('memberoffset'), $session_data, TRUE);
        $data['online_record'] = $this->member_model->get_online('', FALSE, '', $order, '', 0);
        //$data['online_count'] = $this->member_model->total_record;
        $data['url_variable'] = $this->uri->segment(3);
        $data['includefile'] = "photo_gallery";
        //$data['js'] = array("myaccount");
        $this->master_view('', $data);
    }

    function video_gallery($offset = 0) {
        $this->user->loginRequired(true);
        $this->member_model->permissionRequired('per_my_profile');

        // define('account_no_side_panel',TRUE); 

        $this->load->model('member_album_model');
        if ($offset != 0)
            $this->session->set_userdata('memberoffset', $offset);
        elseif ($offset == 0)
            $this->session->unset_userdata('memberoffset');

        $this->session->set_userdata('page_size', 12); //$this->system->page_size);
        $config['per_page'] = $this->session->userdata('page_size');
        if ($this->input->post('sort_by')) {
            $searchArray['sort_by'] = $this->input->post('sort_by');
            $this->session->set_userdata('siteSearchData', $searchArray);
        }
        $session_data = $this->session->userdata('siteSearchData');
//print_r( $session_data);
        $data['all_videos'] = $this->member_album_model->get_video_gallery((int) $this->session->userdata('memberoffset'), (int) $config['per_page'], $session_data);


        $data['all_videosCnt'] = $this->member_album_model->total_record;

        $config['base_url'] = base_url() . 'member/video_gallery';
        $config['total_rows'] = ($data['url_variable'] == "online_user") ? $data['online_count'] : $data['all_photosCnt'];

        $config['full_tag_open'] = '<ul class="pagination pull-right">';
        $config['full_tag_close'] = '</ul>';

        $config['first_link'] = "<i class='fa fa-angle-double-left fa-lg'></i>";
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';

        $config['prev_link'] = "<i class='fa fa-angle-left fa-lg'></i>";
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';

        $config['last_link'] = "<i class='fa fa-angle-double-right fa-lg'></i>";
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';

        $config['next_link'] = "<i class='fa fa-angle-right fa-lg'></i>";
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';

        $config['cur_tag_open'] = '<li class="active">';
        $config['cur_tag_close'] = '</li>';

        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';

        $config['uri_segment'] = 3;
        $this->pagination->initialize($config);
        //$data['online_count'] = $this->member_model->get_online_results((int) $config['per_page'], (int) $this->session->userdata('memberoffset'), $session_data, TRUE);
        $data['online_record'] = $this->member_model->get_online('', FALSE, '', $order, '', 0);
        //$data['online_count'] = $this->member_model->total_record;
        $data['url_variable'] = $this->uri->segment(3);
        $data['includefile'] = "video_gallery";
        //$data['js'] = array("myaccount");
        $this->master_view('', $data);
    }

    function check_date_insert_date() {
        $this->member_model->insertActivity();
//        print_r($all_data['insert_data']);
    }

    function search_username() {
        $username = $this->input->post('search');

        $this->load->model('member_model');
        $data['all_data'] = $this->member_model->member_search_by_username($username);

        foreach ($data['all_data'] as $row) {
            $new_row['label'] = htmlentities(stripslashes($row['member_id']));
            $new_row['value'] = htmlentities(stripslashes($row['user_login_id']));
            $row_set[] = $new_row;
        }
        echo json_encode($row_set);
    }

    function filter_search_username() {

        $username = $this->input->post('search');

        $this->load->model('member_model');
        $data['all_data'] = $this->member_model->filter_search_username($username);

        foreach ($data['all_data'] as $row) {
            $new_row['label'] = htmlentities(stripslashes($row['member_id']));
            $new_row['value'] = htmlentities(stripslashes($row['user_login_id']));
            $row_set[] = $new_row;
        }
        echo json_encode($row_set);
    }
    function thumbfileupload($data)
	{
		$config['image_library'] = 'gd2';
        $config['source_image'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/members/0/'.$data['response']['files'][0]['name'];		
        $config['new_image'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/members/0/small_'.$data['response']['files'][0]['name'];
        $config['create_thumb'] = TRUE;
        $config['maintain_ratio'] = TRUE;
        $config['thumb_marker'] = '';
        $config['quality'] = '65%';
        $config['width'] = 97;
        $config['height'] = 97;
		
        $this->load->library('image_lib'); 
		$this->image_lib->initialize($config);
        $this->image_lib->resize();
        echo "<br>".$this->image_lib->display_errors();

        $this->image_lib->clear();
        
        $config['image_library'] = 'gd2';
        $config['source_image'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/members/0/'.$data['response']['files'][0]['name'];		
        $config['new_image'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/members/0/top_'.$data['response']['files'][0]['name'];
        $config['create_thumb'] = TRUE;
        $config['maintain_ratio'] = TRUE;
        $config['thumb_marker'] = '';
        $config['quality'] = '65%';        
        $config['height'] = 185;
		
        $this->load->library('image_lib'); 
		$this->image_lib->initialize($config);
        $this->image_lib->resize();
        echo "<br>".$this->image_lib->display_errors();

        $this->image_lib->clear();	
        
        $config['image_library'] = 'gd2';
        $config['source_image'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/members/0/'.$data['response']['files'][0]['name'];		
        $config['new_image'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/members/0/face_'.$data['response']['files'][0]['name'];
        $config['create_thumb'] = TRUE;
        $config['maintain_ratio'] = TRUE;
        $config['thumb_marker'] = '';
        $config['quality'] = '65%'; 
        $config['width'] = 67;       
        $config['height'] = 67;
		
        $this->load->library('image_lib'); 
		$this->image_lib->initialize($config);
        $this->image_lib->resize();
        echo "<br>".$this->image_lib->display_errors();

        $this->image_lib->clear();
        
        $config['image_library'] = 'gd2';
        $config['source_image'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/members/0/'.$data['response']['files'][0]['name'];		
        $config['new_image'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/members/0/hotnot_'.$data['response']['files'][0]['name'];
        $config['create_thumb'] = TRUE;
        $config['maintain_ratio'] = TRUE;
        $config['thumb_marker'] = '';
        $config['quality'] = '65%'; 
        $config['width'] = 262;       
        $config['height'] = 384;
		
        $this->load->library('image_lib'); 
		$this->image_lib->initialize($config);
        $this->image_lib->resize();
        echo "<br>".$this->image_lib->display_errors();

        $this->image_lib->clear();		
        
        $config['image_library'] = 'gd2';
        $config['source_image'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/members/0/'.$data['response']['files'][0]['name'];		
        $config['new_image'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/members/0/photo_'.$data['response']['files'][0]['name'];
        $config['create_thumb'] = TRUE;
        $config['maintain_ratio'] = TRUE;
        $config['thumb_marker'] = '';
        $config['quality'] = '65%'; 
        $config['width'] = 260;       
        $config['height'] = 180;
		
        $this->load->library('image_lib'); 
		$this->image_lib->initialize($config);
        $this->image_lib->resize();
        echo "<br>".$this->image_lib->display_errors();

        $this->image_lib->clear();			
	}
	function fileupload()
	{		
		require APPPATH . 'libraries/UploadHandler.php';					
		$info = new UploadHandler();						
		$array=json_encode($info,TRUE);
		$array1=json_decode($array,TRUE);		
        $destFolder = $this->uploadFolder;
		$this->thumbfileupload($array1);		
		$data=$this->member_model->file_upload_mobile($array1,$this->uri->segment(3));		
	}
	function paypal_recurring()
	{	
		$this->load->library('email');
        $this->email->from($this->system->admin_email_address);
        $this->email->to(array('sheetal.panchal@augustinfotechteam.com','nilay.parekh@augustinfotechteam.com'));
        $this->email->subject($this->system->site_name . ": " . 'Recurring paypal started');
        $data['title'] = 'Recurring paypal started';     
          
        $this->email->message('Recurring paypal started. current date and time:- '.date('d/m/Y h:i:s a', time())); 
        $this->email->send();
        $this->email->print_debugger();
        $this->email->clear();
		/*$this->db->where('pay_paypal_recurring', 1);
		$this->db->delete('billing_master');*/
	
		//$sql ="SELECT * from billing_master where pay_membership_type = 4 and pay_method='paypal' and pay_profile_row_data !='' and pay_paypal_recurring = 0 order by pay_member_id,pay_id ";
		
		$sql="SELECT * from billing_master where pay_membership_type = 4 and pay_method='paypal' and pay_type=1 and pay_status= 2 and pay_profile_row_data !='' and pay_profile_row_data !='null' and pay_profile_row_data !='\"\"'  and pay_paypal_recurring = 0 order by pay_member_id ";
        
        $query_result = $this->db->query($sql);
		
        $result = $query_result->result_array();
		error_reporting(0);
		//$arr = array();
		$data['report_msg'] ="<h2>Member details with profile id whose paypal recurring transaction updated by cron</h2>";
		$data['report_msg'] .="<table border=1><tr><th>Memberid</th><th>Profileid</th></tr>";
        foreach ($result as $key => $val) {
        	//echo '<pre>';print_r($val);
        	        	
        	$pay_profile_row_data=json_decode($val['pay_profile_row_data'],true);
        	$pay_cancel_row_data=json_decode($val['pay_cancel_row_data'],true);
        	//echo '<pre>';print_r($pay_profile_row_data['PROFILEID']);
        	$profile_Id = $pay_profile_row_data['PROFILEID'];
        	$profileID=str_replace("%2d","-",$profile_Id);
        	//echo '<pre>';print_r($profileID);
        	//delete FROM  `billing_master` where pay_paypal_recurring=1 and pay_profile_row_data LIKE  '%{"PROFILEID":"$profile_Id"%'
        	if($profile_Id!='')
        	{
				
			
        	$this->load->library('MyPayPal');
          //  $this->load->library('paypal_recurring');
            $this->load->model('Payment_Method_Model', 'payment_method');
            $data['getPaypalData'] = $this->payment_method->get_info_by_key('PayPal');
            
			foreach ($data['getPaypalData'] as $PaypalData) {

                if ($PaypalData['config_name'] == 'paypal_username')
                    $PayPalApiUsername = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_password')
                    $PayPalApiPassword = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_signature')
                    $PayPalApiSignature = $PaypalData['config_value'];

                if ($PaypalData['config_name'] == 'paypal_mode')
                    $PayPalMode = $PaypalData['config_value'];
            }
            
        	
	        $padata = '&VERSION=76.0'
                .  '&METHOD=GetRecurringPaymentsProfileDetails'
                .  '&PROFILEID=' . urlencode( $profileID );
 
			    $paypal = new MyPayPal();
           		$httpParsedResponseAr = $paypal->PPHttpPost('SetExpressCheckout', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);
            print_r("<pre>");
				$parsed_response=$httpParsedResponseAr;
				 print_r($parsed_response);
				 // Start date
				 //print_r($parsed_response['PROFILESTARTDATE']);
				 $date = urldecode($parsed_response['PROFILESTARTDATE']);
				 $end_date = urldecode($parsed_response['LASTPAYMENTDATE']);
				 $duration=urldecode($parsed_response['REGULARBILLINGFREQUENCY']);
				 $period=urldecode($parsed_response['REGULARBILLINGPERIOD']);

				// $date = date ("Y-m-d", strtotime("+".$duration." ".$period, strtotime($date)));
				 $total=0;
				 
				 if($parsed_response['PROFILEID']!= '')
				 {
				 	$sql_delete="delete FROM  billing_master where pay_paypal_recurring=1 and pay_profile_row_data LIKE '%$profile_Id%'";
		        	
		        	$this->db->query($sql_delete);
				 	
				 	 $date = date ("Y-m-d", strtotime("+".$duration." ".$period, strtotime($date)));
				 while (strtotime($date) <= strtotime($end_date)) {
				 	$startdate=date ("Y-m-d",strtotime($date));	
				 	
				 	$date = date ("Y-m-d", strtotime("+".$duration." ".$period, strtotime($date)));
				 $enddate=$date;
				 $total++;
				 
				 echo "$startdate - $enddate\n";
			 
				
				$array = array(
				//'pay_date' => $val['pay_date'],
				'pay_date' => $startdate,
	            'pay_member_id' => $val['pay_member_id'],
	            'pay_type' => $val['pay_type'],
	            'pay_coupon_code' => $val['pay_coupon_code'],
	            'pay_membership_type' => $val['pay_membership_type'],
	            'pay_membership_period' => $val['pay_membership_period'],
	            'pay_sub_start_date' => $startdate,
	            'pay_sub_end_date' => $enddate,
	            'pay_method' => $val['pay_method'],
	            'pay_amount' => $val['pay_amount'],
	            'pay_row_data' => $val['pay_row_data'],
	            'pay_profile_row_data' => $val['pay_profile_row_data'],
	            'pay_cancel_row_data' => $val['pay_cancel_row_data'],            
	            'pay_status' => $val['pay_status'],
	            'pay_created_date' => $val['pay_created_date'],
	            'pay_updated_date' => $val['pay_updated_date'],
	            'pay_paypal_recurring' => 1);

				$this->db->set($array);
				$this->db->insert('billing_master');
			 }
	        $data['report_msg'] .= '<tr><td>'.$val['pay_member_id'].'</td><td>'.$profileID.'</td></tr>';
	        
			}
			 
	       } 
	       	
        }
        	$data['report_msg'] .= '</table>';
       	    $this->load->library('email');
	        $this->email->from($this->system->admin_email_address);
	        $this->email->to(array('sheetal.panchal@augustinfotechteam.com','nilay.parekh@augustinfotechteam.com'));
	        $this->email->subject($this->system->site_name . ": " . 'Paypal Recurring Transaction');
	        $data['title'] = 'Paypal Recurring';
	        //$data['report_msg'] = "<tr><td>".$pay_member_id."(".$profileID.")</td></tr>";
	        $this->email->message($data['report_msg']);
	        //echo $data['report_msg'];
	        $this->email->send();
	        $this->email->print_debugger();
	        $this->email->clear();
            
	}
	function select_zipcode()
	{
		$this->load->model('Region_Model', 'region');
		$data['regionData'] = $this->region->init_array();		
		//$data['js'] = array("zipcode");	
			
		$data['includefile'] 	= "select_zipcode";
		$data['popupFlag'] 	= true;
        $this->master_view('', $data);
	}
	
	function select_zipcode_partner()
	{
		$this->load->model('Region_Model', 'region');
		$data['regionData'] = $this->region->init_array();		
		//$data['js'] = array("zipcode");		
		$data['includefile'] 	= "select_zipcode_partner";
		$data['popupFlag'] 	= true;
        $this->master_view('', $data);
	}
	
	function privacy_policy()
   	{
   	//echo 'hi';die;
   		$this->load->model('page_model');
   		$get_page = $this->page_model->get_info_by_page_sef_url('privacy-policy');
   		if($get_page['revision_page_type'] == "PopUpPage")
        {
        	
			$data['content'] = $get_page['revision_page_content'];
			
			$data['slug'] = $get_page['revision_page_slug'];
			$this->load->view('popup_layout', $data); 
        }
   	}
		
}

/* End of file home.php */
/* Location: ./application/controllers/page.php */
