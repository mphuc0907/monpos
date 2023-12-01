<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_customer extends CI_Controller
{
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = $this->cms_authentication->check();
    }

    public function GetToken()
    {
        $get = getallheaders();
        if (!empty($get['authorization']) || !empty($get['Authorization'])) {
            if (isset($get['authorization'])) {
                $token = str_replace('Bearer ', '', $get['authorization']);
            } else {
                $token = str_replace('Bearer ', '', $get['Authorization']);
            }
            return $token;
        } else {
            $output_data = array(
                "error_code" => 403,
                "message" => "Bạn cần phải đăng nhập"
            );
            return json_encode($output_data, 200);
        }

    }

    public function Getid()
    {
        $token = self::GetToken();

        $user = $this->db->where('token_login', $token)->from('users')->get()->row_array();
        if (empty($user)) {
            return 0;
        } else {
            return $user['id'];
        }
    }

    public function check_validate($value, $type = 'text')
    {

        switch ($type) {
            case 'email':
                return self::validate_email($value);
            case 'phone':
                return self::validate_vietnamese_phone_number($value);
            case 'date':
                return self::validate_date_of_birth($value);
            case 'text':
                return self::validate_vietnamese_string($value);
            case 'number':
                return self::validate_numeric($value);
            //Thêm các case khác cho các loại kiểm tra khác nếu cần thiết
            default:
                return false;
        }
    }
    function validate_numeric($value) {
        return is_numeric($value);
    }
    public function validate_email($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    public function validate_vietnamese_phone_number($value)
    {
        $pattern = '/^(0|\+84)\d{9,10}$/'; // Kiểm tra số điện thoại bắt đầu bằng 0 hoặc +84, và có 9-10 chữ số
        return preg_match($pattern, $value) === 1;
    }
    public function validate_date_of_birth($value)
    {
        $date = date_parse_from_format('Y/m/d', $value); // Phân tích chuỗi ngày tháng theo định dạng "Y/m/d"
        return $date['error_count'] === 0 && checkdate($date['month'], $date['day'], $date['year']);
    }
    public function validate_vietnamese_string($value)
    {
        $pattern = '/^[\p{L} .-]+$/u'; // Kiểm tra giá trị chỉ chứa chữ cái, chữ có dấu, " ", ".", "-"
        return preg_match($pattern, $value) === 1;
    }

    public function customer($id_cus = '')
    {
        $id = self::Getid();
        if ($id == 0) {

            $output_data = array(
                "error_code" => 400,
                "message" => "Vui lòng đăng nhập lại"
            );
            echo json_encode($output_data, 200);
            return;

        }
        $store_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($store_id['group_id'] == 1) ? 1 : (($store_id['parent_id'] == 1) ? $store_id['id'] : $store_id['parent_id']);
        if (!empty($id_cus)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $cus = $this->db->from('customers')->where('shop_id', $parent)->where('id', $id_cus)->get()->row_array();
                if (!isset($cus) && count($cus) == 0) {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Khách hàng không tồn tại, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $data['_list_cus'] = $cus;
                    $data['customer_id'] = $id_cus;
                    $total_orders = $this->db
                        ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('orders')
                        ->where('deleted', 0)
                        ->where('lack', 0)
                        ->where('customer_id', $id_cus)
                        ->get()
                        ->row_array();
                    $data['_list_orders'] = $this->db
                        ->from('orders')
                        ->order_by('created', 'desc')
                        ->where('deleted', 0)
                        ->where('lack', 0)
                        ->where('customer_id', $id_cus)
                        ->get()
                        ->result_array();
                    $total_orders_lack = $this->db
                        ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('orders')
                        ->where(['deleted' => 0, 'lack >' => 0])
                        ->where('customer_id', $id_cus)
                        ->get()
                        ->row_array();
                    $data['_list_orders_lack'] = $this->db
                        ->from('orders')
                        ->order_by('created', 'desc')
                        ->where(['deleted' => 0, 'lack >' => 0])
                        ->where('customer_id', $id_cus)
                        ->get()
                        ->result_array();
                    $output_data = array(
                        "error_code" => 0,
                        "detail_customer" => $data['_list_cus'],
                        "order_cusomter" => $total_orders,
                        "list_order_detail" => $data['_list_orders'],
                        "order_cusomter_lack" => $total_orders_lack,
                        "list_order_detail_lack" => $data['_list_orders_lack']
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $name = $data['customer_name'];
                $email = $data['customer_email'];
                $phone = $data['customer_phone'];
                $date_birth = $data['customer_birthday'];
//                var_dump($check);die();
                if (self::check_validate($email, 'email') === false) {
                    echo json_encode(array(
                        "error_code" => 109,
                        "message" => "Email không đúng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($phone, 'phone') === false) {
                    echo json_encode(array(
                        "error_code" => 107,
                        "message" => "Số điện thoại không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($date_birth, 'date') === false) {
                    echo json_encode(array(
                        "error_code" => 111,
                        "message" => "Ngày sinh không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($name, 'text') === false) {
                    echo json_encode(array(
                        "error_code" => 108,
                        "message" => $name . " không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $check_customer = $this->db->where('ID', $id_cus)->from('customers')->where('shop_id', $parent)->get()->row_array();
                if(empty($check_customer)){
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => " không tồn tại khách hàng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $data = $this->cms_common_string->allow_post($data, ['customer_name', 'customer_phone', 'customer_email', 'customer_addr', 'notes', 'customer_birthday', 'customer_gender']);
                $data['customer_birthday'] = gmdate("Y-m-d H:i:s", strtotime(str_replace('/', '-', $data['customer_birthday'])) + 7 * 3600);
                $data['updated'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                $data['user_upd'] = $parent;
                $this->db->where('ID', $id_cus)->where('shop_id', $parent)->update('customers', $data);
                if ($this->db->affected_rows() > 0) {
                    $customer = $this->db->where('ID', $id_cus)->from('customers')->where('shop_id', $parent)->get()->row_array();
                    echo json_encode(array(
                        "error_code" => 0,
                        "data" => $customer
                    ), 200);
                    return;
                } else {
                    echo json_encode(array(
                        "error_code" => 500,
                        "message" => "Đã xảy ra lỗi khi cập nhật, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
            }elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
                $customer = $this->db->from('customers')->where('shop_id',$parent)->where('ID', $id_cus)->get()->row_array();
                if (!isset($customer) && count($customer) == 0) {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => " không tồn tại khách hàng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $this->db->where('ID', $id_cus)->where('shop_id', $parent)->delete('customers');
                    if ($this->db->affected_rows() > 0) {
                        echo json_encode(array(
                            "error_code" => 0,
                            "message" => "Xóa khách hàng thành công"
                        ), 200);
                        return;
                    } else {
                        echo json_encode(array(
                            "error_code" => 500,
                            "message" => "Đã xảy ra lỗi khi cập nhật, vui lòng kiểm tra lại"
                        ), 200);
                        return;
                    }

                }
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {

                if (!empty($_GET['status_customer']) || $_GET['status_customer'] == 0 ):
                    $status = $_GET['status_customer'];
                else:
                  
                    $status = '';
                endif;

                if (!empty($_GET['keyword'])):
                    $keyword = $_GET['keyword'];
                else:
                    $keyword = '';
                endif;
                if ($status === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if ($status == 0) {
                    $total_customer = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'LEFT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('customers.ID')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'LEFT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->group_by('customers.ID')
                        ->get()
                        ->result_array();
                    $total_customer['quantity'] = count($temp);
                    $data['_list_customer'] = $this->db
                        ->select('customers.ID,customer_code,customer_name,customer_phone,customer_addr,max(sell_date) as sell_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'LEFT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->order_by('customers.created', 'desc')
                        ->group_by('customers.ID')
                        ->get()
                        ->result_array();
                } else if ($status == 1) {
                    $total_customer = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('customers.ID')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->group_by('customers.ID')
                        ->get()
                        ->result_array();
                    $total_customer['quantity'] = count($temp);
                    $data['_list_customer'] = $this->db
                        ->select('customers.ID,customer_code,customer_name,customer_phone,customer_addr,max(sell_date) as sell_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->order_by('customers.created', 'desc')
                        ->group_by('customers.ID')
                        ->get()
                        ->result_array();
                } else {
                    $total_customer = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('customers')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->order_by('customers.created', 'desc')
                        ->group_by('customers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('customers.ID')
                        ->from('customers')
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->group_by('customers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->result_array();
                    $total_customer['quantity'] = count($temp);
                    $data['_list_customer'] = $this->db
                        ->select('customers.ID,customer_code,customer_name,customer_phone,customer_addr,max(sell_date) as sell_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('customers')
                        ->where("(customer_code LIKE '%" . $keyword . "%' OR customer_name LIKE '%" . $keyword . "%' OR customer_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('customers.shop_id', $parent)
                        ->join('orders', 'orders.customer_id=customers.ID and cms_orders.deleted=0', 'RIGHT')
                        ->order_by('customers.created', 'desc')
                        ->group_by('customers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->result_array();
                }

                $output_data = array(
                    "error_code" => 0,
                    "count_customer" => $total_customer,
                    "list_customer" => $data['_list_customer']
                );
                echo json_encode($output_data, 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $name = $data['customer_name'];
                $email = $data['customer_email'];
                $phone = $data['customer_phone'];
                $date_birth = $data['customer_birthday'];
//                var_dump($check);die();
                if (self::check_validate($email, 'email') === false) {
                    echo json_encode(array(
                        "error_code" => 109,
                        "message" => "Email không đúng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($phone, 'phone') === false) {
                    echo json_encode(array(
                        "error_code" => 107,
                        "message" => "Số điện thoại không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($date_birth, 'date') === false) {
                    echo json_encode(array(
                        "error_code" => 111,
                        "message" => "Ngày sinh không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($name, 'text') === false) {
                    echo json_encode(array(
                        "error_code" => 108,
                        "message" => $name . " không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }

                $data = $this->cms_common_string->allow_post($data, ['customer_code', 'customer_name', 'customer_phone', 'customer_email', 'customer_addr', 'notes', 'customer_birthday', 'customer_gender']);
                $data['customer_birthday'] = gmdate("Y-m-d H:i:s", strtotime(str_replace('/', '-', $data['customer_birthday'])) + 7 * 3600);
                $data['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                $data['user_init'] = $parent;
                $data['shop_id'] = $parent;
                if ($data['customer_code'] == '') {
                    $this->db->select_max('customer_code')->like('customer_code', 'KH');
                    $max_customer_code = $this->db->get_where('customers', array('shop_id' => $parent))->row();
                    $max_code = (int)(str_replace('KH', '', $max_customer_code->customer_code)) + 1;
                    if ($max_code < 10)
                        $data['customer_code'] = 'KH00000' . ($max_code);
                    else if ($max_code < 100)
                        $data['customer_code'] = 'KH0000' . ($max_code);
                    else if ($max_code < 1000)
                        $data['customer_code'] = 'KH000' . ($max_code);
                    else if ($max_code < 10000)
                        $data['customer_code'] = 'KH00' . ($max_code);
                    else if ($max_code < 100000)
                        $data['customer_code'] = 'KH0' . ($max_code);
                    else if ($max_code < 1000000)
                        $data['customer_code'] = 'KH' . ($max_code);

                    $this->db->insert('customers', $data);
                    $id_custom = $this->db->insert_id();
                    $customer = $this->db->where('ID', $id_custom)->from('customers')->where('shop_id', $parent)->get()->row_array();
                    $output_data = array(
                        "error_code" => 0,
                        "detail_customer" => $customer
                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    $count = $this->db->where('customer_code', $data['customer_code'])->from('customers')->where('shop_id', $parent)->count_all_results();
                    if ($count > 0) {
                        echo json_encode(array(
                            "error_code" => 440,
                            "message" => "Mã khách hàng đã tồn tại, vui lòng kiểm tra lại"
                        ), 200);
                        return;
                    } else {
                        $this->db->insert('customers', $data);
                        $id_custom = $this->db->insert_id();
                        $customer = $this->db->where('ID', $id_custom)->from('customers')->where('shop_id', $parent)->get()->row_array();
                        $output_data = array(
                            "error_code" => 0,
                            "detail_customer" => $customer,
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                }
            }
        }
    }
}