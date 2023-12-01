<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_supplier extends CI_Controller
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
            //Thêm các case khác cho các loại kiểm tra khác nếu cần thiết
            default:
                return false;
        }
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

    public function supplier($id_sup = '')
    {
        $id = self::Getid();
//        print_r($id);die();
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
        if (!empty($id_sup)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $sup = $this->db->from('suppliers')->where('id', $id_sup)->where('shop_id', $parent)->get()->row_array();

                if (empty($sup)) {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Không tồn tại Nhà cung cấp, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $output_data = array(
                        "error_code" => 0,
                        "detail_supplier" => $sup
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $name = $data['supplier_name'];
                $email = $data['supplier_email'];
                $phone = $data['supplier_phone'];
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
                if (self::check_validate($name, 'text') === false) {
                    echo json_encode(array(
                        "error_code" => 108,
                        "message" => $name . " không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $check_customer = $this->db->where('ID', $id_sup)->from('suppliers')->where('shop_id', $parent)->get()->row_array();
                if(empty($check_customer)){
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => " không tồn tại Nhà cung cấp, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $data = $this->cms_common_string->allow_post($data, ['supplier_name', 'supplier_phone', 'supplier_email', 'supplier_addr', 'notes', 'tax_code']);
                $data['updated'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                $data['user_upd'] = $parent;
                $this->db->where('ID', $id_sup)->where('shop_id', $parent)->update('suppliers', $data);
                if ($this->db->affected_rows() > 0) {
                    $customer = $this->db->where('ID', $id_sup)->from('suppliers')->where('shop_id', $parent)->get()->row_array();
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
                $customer = $this->db->from('suppliers')->where('shop_id',$parent)->where('ID', $id_sup)->get()->row_array();
                if (empty($customer)) {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => " không tồn tại khách hàng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $this->db->where('ID', $id_sup)->where('shop_id', $parent)->delete('suppliers');
                    if ($this->db->affected_rows() > 0) {
                        echo json_encode(array(
                            "error_code" => 0,
                            "message" => "Xóa nhà cung cấp thành công"
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
                if (isset($_GET['status_supplier'])):
                    $status = $_GET['status_supplier'];
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
                    $total_supplier = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'LEFT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('suppliers.ID')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'LEFT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->group_by('suppliers.ID')
                        ->get()
                        ->result_array();
                    $total_supplier['quantity'] = count($temp);
                    $data['_list_supplier'] = $this->db
                        ->select('supplier_code,suppliers.ID,supplier_name,supplier_phone,supplier_addr,max(input_date) as input_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('suppliers')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'LEFT')
                        ->order_by('suppliers.created', 'desc')
                        ->group_by('suppliers.ID')
                        ->get()
                        ->result_array();
                } else if ($status == 1) {
                    $total_supplier = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('suppliers.ID')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->group_by('suppliers.ID')
                        ->get()
                        ->result_array();
                    $total_supplier['quantity'] = count($temp);
                    $data['_list_supplier'] = $this->db
                        ->select('supplier_code,suppliers.ID,supplier_name,supplier_phone,supplier_addr,max(input_date) as input_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->order_by('suppliers.created', 'desc')
                        ->group_by('suppliers.ID')
                        ->get()
                        ->result_array();
                } else {
                    $total_supplier = $this->db
                        ->select('sum(total_money) as total_money, sum(lack) as total_debt')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->group_by('suppliers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->row_array();
                    $temp = $this->db
                        ->select('suppliers.ID')
                        ->from('suppliers')
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->group_by('suppliers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->result_array();
                    $total_supplier['quantity'] = count($temp);
                    $data['_list_supplier'] = $this->db
                        ->select('supplier_code,suppliers.ID,supplier_name,supplier_phone,supplier_addr,max(input_date) as input_date,sum(total_money) as total_money,sum(lack) as total_debt')
                        ->from('suppliers')
                        ->where("(supplier_code LIKE '%" . $keyword . "%' OR supplier_name LIKE '%" . $keyword . "%' OR supplier_phone LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->where('suppliers.shop_id', $parent)
                        ->join('input', 'input.supplier_id=suppliers.ID and cms_input.deleted=0', 'RIGHT')
                        ->order_by('suppliers.created', 'desc')
                        ->group_by('suppliers.ID')
                        ->having('sum(lack) > 0')
                        ->get()
                        ->result_array();
                }
                $output_data = array(
                    "error_code" => 0,
                    "count_supplier" => $total_supplier,
                    "list_supplier" => $data['_list_supplier']
                );
                echo json_encode($output_data, 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $name = $data['supplier_name'];
                $email = $data['supplier_email'];
                $phone = $data['supplier_phone'];
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
                if (self::check_validate($name, 'text') === false) {
                    echo json_encode(array(
                        "error_code" => 108,
                        "message" => $name . " không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $data = $this->cms_common_string->allow_post($data, ['supplier_code', 'supplier_name', 'supplier_phone', 'supplier_email', 'supplier_addr', 'tax_code', 'notes']);
                $data['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                $data['user_init'] = $parent;
                $data['shop_id'] = $parent;
                if ($data['supplier_code'] == '') {
                    $this->db->select_max('supplier_code')->like('supplier_code', 'NCC');
                    $max_supplier_code = $this->db->get_where('suppliers', array('shop_id' => $parent))->row();
                    $max_code = (int)(str_replace('NCC', '', $max_supplier_code->supplier_code)) + 1;
                    if ($max_code < 10)
                        $data['supplier_code'] = 'NCC0000' . ($max_code);
                    else if ($max_code < 100)
                        $data['supplier_code'] = 'NCC000' . ($max_code);
                    else if ($max_code < 1000)
                        $data['supplier_code'] = 'NCC00' . ($max_code);
                    else if ($max_code < 10000)
                        $data['supplier_code'] = 'NCC0' . ($max_code);
                    else if ($max_code < 100000)
                        $data['supplier_code'] = 'NCC' . ($max_code);

                    $this->db->insert('suppliers', $data);
                    $id_supplier = $this->db->insert_id();
                    $customer = $this->db->where('ID', $id_supplier)->from('suppliers')->where('shop_id', $parent)->get()->row_array();
                    $output_data = array(
                        "error_code" => 0,
                        "detail_supplier" => $customer
                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    $count = $this->db->where('supplier_code', $data['supplier_code'])->where('shop_id', $parent)->from('suppliers')->count_all_results();
                    if ($count > 0) {
                        echo json_encode(array(
                            "error_code" => 440,
                            "message" => "Mã nhà cung cấp đã tồn tại, vui lòng kiểm tra lại"
                        ), 200);
                        return;
                    } else {
                        $this->db->insert('suppliers', $data);
                        $id_supplier = $this->db->insert_id();
                        $customer = $this->db->where('ID', $id_supplier)->from('suppliers')->where('shop_id', $parent)->get()->row_array();
                        $output_data = array(
                            "error_code" => 0,
                            "detail_supplier" => $customer
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                }
            }
        }
    }
}