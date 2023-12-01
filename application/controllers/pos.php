<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// controller control user authentication
class Pos extends CI_Controller
{
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = $this->cms_authentication->check();
    }

    public function index()
    {
        if ($this->auth == null)
            $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
        $store_id = $this->db->from('users')->where('id', $this->auth['id'])->limit(1)->get()->row_array();
        $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);
//        print_r($parent);die();
        $data['seo']['title'] = "Phần mềm quản lý bán hàng";
        $data['data']['user'] = $this->auth;
        $data['data']['sale'] = $this->db->from('users')->where('(id ='.$parent.' AND user_status = 1) OR (parent_id = '.$parent.' AND `user_status` = 1)')->get()->result_array();
        $store = $this->db->from('stores')->get()->result_array();
        $data['data']['store'] = $store;
        $store_id = $this->db->from('stores')->where('shop_id',$parent)->get()->result_array();
        $data['data']['store_id'] = $store_id;
        $this->load->view('layout/pos', isset($data) ? $data : null);
    }
}