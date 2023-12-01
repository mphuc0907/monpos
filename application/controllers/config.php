<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Config extends CI_Controller
{
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = $this->cms_authentication->check();
    }

    /*
     * Cấu hình hệ thống
    /****************************************/
    public function index()
    {
        if ($this->auth == null || !in_array(10, $this->auth['group_permission']))
            $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
        $user_id = $this->db->from('users')->where('id', $this->auth['id'])->limit(1)->get()->row_array();
        $data['seo']['title'] = "Phần mềm quản lý bán hàng";
        if ($user_id['group_id'] == 1) {
            $user = $this->db->select('users.id, username, email, display_name, user_status, group_name, group_id ')->from('users')->join('users_group', 'users_group.id = users.group_id')->where('users.parent_id',1)->get()->result_array();
        } else {
            $user = $this->db->select('users.id, username, email, display_name, user_status, group_name, group_id ')->from('users')->join('users_group', 'users_group.id = users.group_id')->where('users.parent_id',$this->auth['id'])->or_where('users.id',$this->auth['id'])->get()->result_array();
        }
        $data['data']['template'] = $this->db->select('content')->from('templates')->where('id', 1)->limit(1)->get()->row_array();
        $data['data']['list_template'] = $this->db->from('templates')->get()->result_array();
        $data['data']['_user'] = $user;
        $data['data']['user'] = $this->auth;
        $store = $this->db->from('stores')->get()->result_array();
        $data['data']['store'] = $store;
        $store_id = $this->db->select('store_id')->from('users')->where('id',$this->auth['id'])->limit(1)->get()->row_array();

        $data['data']['store_id'] = $store_id['store_id'];
        $data['template'] = 'setting/setting';
        $this->load->view('layout/index', isset($data) ? $data : null);
    }

    public function cms_save_template($id)
    {
        $id = (int)$id;
        $data = $this->input->post('data');
        $template = $this->db->from('templates')->where('id', $id)->get()->row_array();
        if (empty($prd_group) && count($template) == 0) {
            echo $this->messages = '0';
            return;
        }

        $data['updated'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
        $data['user_upd'] = $this->auth['id'];
        $this->db->where('id', $id)->update('templates', $data);
        echo $this->messages = '1';
    }

    public function cms_load_template($id)
    {
        $template = $this->db->from('templates')->where('id', $id)->get()->row_array();
        if (empty($prd_group) && count($template) == 0) {
            echo $this->messages = '0';
            return;
        }
       echo $this->message = $template['content'];
    }

    public function cms_crstore($store_name)
    {
        $option = $this->input->post('data');
        $store_id = $this->db->from('users')->where('id', $this->auth['id'])->limit(1)->get()->row_array();
        $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);
        $count = $this->db->where('stock_name', $option['store_name'])->where('shop_id',$parent)->from('stores')->count_all_results();
        if ($count == 0) {
            $data = ['stock_name' =>  $option['store_name'], 'user_init'=>$this->auth['id'],'shop_id' => $parent];
            $data['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
            $this->db->insert('stores', $data);
            echo $this->messages = '1';
        } else {
            echo $this->messages = 'Nhóm Chức năng ' . $option['store_name'] . ' đã tồn tại trong hệ thống.Vui lòng tạo tên nhóm khác.';
        }
    }
//    public function cms_deltore()
//    {
//        $option = $this->input->post('data');
//        $store_id = $this->db->from('users')->where('id', $this->auth['id'])->limit(1)->get()->row_array();
//        $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);
//        $count = $this->db->where('stock_name', $option['store_name'])->where('shop_id',$parent)->from('stores')->count_all_results();
//        if ($count == 0) {
//            $data = ['stock_name' =>  $option['store_name'], 'user_init'=>$this->auth['id'],'shop_id' => $parent];
//            $data['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
//            $this->db->insert('stores', $data);
//            echo $this->messages = '1';
//        } else {
//            echo $this->messages = 'Nhóm Chức năng ' . $option['store_name'] . ' đã tồn tại trong hệ thống.Vui lòng tạo tên nhóm khác.';
//        }
//    }
}

