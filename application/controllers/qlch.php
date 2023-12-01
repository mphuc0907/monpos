<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

Class qlch  extends CI_Controller{
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = $this->cms_authentication->check();
    }
    public function index()
    {
        if ($this->auth == null || !in_array(11, $this->auth['group_permission']))
            $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');

        $data['seo']['title'] = "Phần mềm quản lý cửa hàng";
        $manufacture = $this->db->from('products_manufacture')->get()->result_array();
        $data['data']['_prd_manufacture'] = $manufacture;
        $data['data']['user'] = $this->auth;
        $store = $this->db->from('stores')->get()->result_array();
        $data['data']['store'] = $store;
        $store_id = $this->db->select('store_id')->from('users')->where('id',$this->auth['id'])->limit(1)->get()->row_array();
        $data['data']['store_id'] = $store_id['store_id'];
        $data['template'] = 'qlch/index';
        $this->load->view('layout/index', isset($data) ? $data : null);
    }

}