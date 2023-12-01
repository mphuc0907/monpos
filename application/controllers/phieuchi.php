<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

//  https://codefly.vn/phan-mem-quan-ly-ban-hang/119
class Phieuchi extends CI_Controller
{
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = $this->cms_authentication->check();
    }

    public function index($page = 1)
    {
        if ($this->auth == null) $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
        $data['seo']['title'] = "Phần mềm quản lý bán hàng";
        $usser_id = $this->db->from('users')->where('id', $this->auth['id'])->limit(1)->get()->row_array();
        $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);

        $config = $this->cms_common->cms_pagination_custom();
        $total_thuchi = $this->db->from('thuchi')->where('shop_id', $parent)->where("loaiphieu", "phieuchi")->count_all_results();
        $config['base_url'] = 'cms_paging_listthuchi';
        $config['total_rows'] = $total_thuchi;
        $config['per_page'] = 10;
        $this->pagination->initialize($config);
        $data['_pagination_link'] = $this->pagination->create_links();

        $data['_list_thuchi'] = $this->db
            ->select('ID,store_id, thuchi_code, thuchi_date, notes,loaiphieu, hinhthuc,tongtien,user_init')
            ->from('thuchi')
            ->where("loaiphieu", "phieuchi")
            ->where('shop_id', $parent)
            ->limit($config['per_page'], ($page - 1) * $config['per_page'])
            ->order_by('thuchi_date', 'desc')
            ->get()
            ->result_array();
        $data['user'] = $this->auth;
        $data['_total_thuchi'] = $total_thuchi;
        $total_money = 0;
        //$total_debt = 0;
        foreach ($data['_list_thuchi'] as $key => $item) {
            $total_money += $item['tongtien'];
        }

        $data['total_money'] = $total_money;

        $store = $this->db->from('stores')->where('shop_id', $parent)->get()->result_array();
        $data['data']['store'] = $store;
        $store_id = $this->db->select('ID')->from('stores')->where('shop_id', $parent)->limit(1)->get()->row_array();
        if (!empty($store_id)) {
            $data['data']['store_id'] = $store_id['ID'];
        } else {
            $data['data']['store_id'] = 0;
        }
        $data['data']['user'] = $this->auth;
        $data['template'] = 'phieuchi/index';
        $this->load->view('layout/index', isset($data) ? $data : null);
    }

    //phieu chi
    public function cms_phieuchi()
    {
        $data = $this->input->post('data');
        $usser_id = $this->db->from('users')->where('id', $this->auth['id'])->limit(1)->get()->row_array();
        $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);

        $data = $this->cms_common_string->allow_post($data, ['thuchi_code', 'store_id', 'thuchi_date', 'user_init', 'notes', 'hinhthuc', 'loaiphieu', 'tongtien', 'deleted']);
        $store_id = $this->db->select('ID')->from('stores')->where('shop_id', $parent)->limit(1)->get()->row_array();
        if (!empty($store_id)) {
            $data['store_id'] = $store_id['ID'];
        } else {
            $data['store_id'] = 0;
        }
        $data['shop_id'] = $parent;
        $data['thuchi_date'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
        $data['user_init'] = $this->auth['id'];
        $data['loaiphieu'] = 'phieuchi';
        $data['thuchi_code'] = '';
        if ($data['thuchi_code'] == '') {
            $this->db->select_max('thuchi_code')->where('shop_id', $parent)->like('thuchi_code', 'PC');
            $max_thuchi_code = $this->db->get('thuchi')->row();
            $max_code = (int)(str_replace('PC', '', $max_thuchi_code->thuchi_code)) + 1;
            if ($max_code < 10)
                $data['thuchi_code'] = 'PC00000' . ($max_code);
            else if ($max_code < 100)
                $data['thuchi_code'] = 'PC0000' . ($max_code);
            else if ($max_code < 1000)
                $data['thuchi_code'] = 'PC000' . ($max_code);
            else if ($max_code < 10000)
                $data['thuchi_code'] = 'PC00' . ($max_code);
            else if ($max_code < 100000)
                $data['thuchi_code'] = 'PC0' . ($max_code);
            else if ($max_code < 1000000)
                $data['thuchi_code'] = 'PC' . ($max_code);

            $this->db->insert('thuchi', $data);
            $id = $this->db->insert_id();
            echo $this->messages = $id;
        } else {
            $count = $this->db->where('thuchi_code', $data['thuchi_code'])->from('thuchi')->where('shop_id', $parent)->where("loaiphieu", "phieuthu")->count_all_results();
            if ($count > 0) {
                echo $this->messages = "0";
            } else {
                $this->db->insert('thuchi', $data);
                $id = $this->db->insert_id();
                echo $this->messages = $id;
            }
        }
    }

    public function cms_paging_listthuchi($page = 1)
    {
        $config = $this->cms_common->cms_pagination_custom();
        $option = $this->input->post('data');
        $usser_id = $this->db->from('users')->where('id', $this->auth['id'])->limit(1)->get()->row_array();
        $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);


        $tungay = $option['date_from'];
        $denngay = $option['date_to'];

        if (empty($tungay)) {
            $tungay = "2020-04-01";
        }

        if (empty($denngay)) {
            $denngay = date("Y-m-d");
        }

        if ($option['option'] == 0) {
            $data['_list_thuchi'] = $this->db
                ->select('ID,store_id, thuchi_code, thuchi_date, notes,loaiphieu, hinhthuc,tongtien,user_init')
                ->from('thuchi')
                ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                ->where("(thuchi_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
                ->where("loaiphieu", "phieuchi")
                ->where("shop_id", $parent)
                ->where("thuchi_date >=", $tungay . " 00:00:00")
                ->where("thuchi_date <=", $denngay . " 23:59:59")
                ->order_by('thuchi_date', 'desc')
                ->get()
                ->result_array();
//            print_r($this->db);die();
        } else {

            $data['_list_thuchi'] = $this->db
                ->select('ID,store_id, thuchi_code, thuchi_date, notes,loaiphieu, hinhthuc,tongtien,user_init')
                ->from('thuchi')
                ->limit($config['per_page'], ($page - 1) * $config['per_page'])
                ->where("(thuchi_code LIKE '%" . $option['keyword'] . "%')", NULL, FALSE)
                ->where("hinhthuc =", $option['option'])
                ->where("loaiphieu", "phieuchi")
                ->where("shop_id", $parent)
                ->where("thuchi_date >=", $tungay . " 00:00:00")
                ->where("thuchi_date <=", $denngay . " 23:59:59")
                ->order_by('thuchi_date', 'desc')
                ->get()
                ->result_array();
        }


        $total_thuchi = $this->db->from('thuchi')->where("shop_id", $parent)->where("loaiphieu", "phieuchi")->count_all_results();
        $config['base_url'] = 'cms_paging_listthuchi';
        $config['total_rows'] = $total_thuchi;
        $config['per_page'] = 10;
        $this->pagination->initialize($config);
        $data['_pagination_link'] = $this->pagination->create_links();

        $data['_total_thuchi'] = $total_thuchi;
        $total_money = 0;
        //$total_debt = 0;
        foreach ($data['_list_thuchi'] as $key => $item) {
            $total_money += $item['tongtien'];
        }

        $data['total_money'] = $total_money;

        $data['user'] = $this->auth;
        if ($page > 1 && ($total_thuchi - 1) / ($page - 1) == 10)
            $page = $page - 1;

        $data['option'] = $option['option'];
        $data['page'] = $page;

        $this->load->view('phieuchi/list_phieuchiajax', isset($data) ? $data : null);
    }


    public function cms_delThuchi()
    {
        $usser_id = $this->db->from('users')->where('id', $this->auth['id'])->limit(1)->get()->row_array();
        $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
        $id = (int)$this->input->post('id');
        $thuchi = $this->db->from('thuchi')->where('ID', $id)->where('shop_id', $parent)->get()->row_array();
        if (!isset($thuchi) && count($thuchi) == 0) {
            echo $this->messages;

            return;
        } else {
            $this->db->where('ID', $id)->delete('thuchi');
            echo $this->messages = '1';
        }
    }


}