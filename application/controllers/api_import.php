<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_import extends CI_Controller
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

    function validate_numeric($value)
    {
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

    public function convert_number_to_words($number)
    {
        $hyphen = ' ';
        $conjunction = '  ';
        $separator = ' ';
        $negative = 'âm ';
        $decimal = ' phẩy ';
        $dictionary = array(
            0 => 'Không',
            1 => 'Một',
            2 => 'Hai',
            3 => 'Ba',
            4 => 'Bốn',
            5 => 'Năm',
            6 => 'Sáu',
            7 => 'Bảy',
            8 => 'Tám',
            9 => 'Chín',
            10 => 'Mười',
            11 => 'Mười một',
            12 => 'Mười hai',
            13 => 'Mười ba',
            14 => 'Mười bốn',
            15 => 'Mười năm',
            16 => 'Mười sáu',
            17 => 'Mười bảy',
            18 => 'Mười tám',
            19 => 'Mười chín',
            20 => 'Hai mươi',
            30 => 'Ba mươi',
            40 => 'Bốn mươi',
            50 => 'Năm mươi',
            60 => 'Sáu mươi',
            70 => 'Bảy mươi',
            80 => 'Tám mươi',
            90 => 'Chín mươi',
            100 => 'trăm',
            1000 => 'ngàn',
            1000000 => 'triệu',
            1000000000 => 'tỷ',
            1000000000000 => 'nghìn tỷ',
            1000000000000000 => 'ngàn triệu triệu',
            1000000000000000000 => 'tỷ tỷ'
        );

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int)$number < 0) || (int)$number < 0 - PHP_INT_MAX) {
            trigger_error(
                'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }

        if ($number < 0) {
            return $negative . $this->convert_number_to_words(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int)($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->convert_number_to_words($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int)($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convert_number_to_words($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string)$fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }

    public function import($id_import = '')
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
        if (!empty($id_import)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $sup = $this->db->from('stores')->where('ID', $id_import)->where('shop_id', $parent)->get()->row_array();

                if (empty($sup)) {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Không tồn tại kho, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $output_data = array(
                        "error_code" => 0,
                        "detail_inventory" => $sup
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            } elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                if ($data['stock_name'] === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $count = $this->db->where('ID', $id_import)->where('shop_id', $parent)->from('stores')->count_all_results();
                if ($count == 0) {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Dữ liệu không tồn tại, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $detail = $this->db->where('stock_name', $data['stock_name'])->where('shop_id', $parent)->from('stores')->get()->row_array();
                    if (!empty($detail)) {
                        echo json_encode(array(
                            "error_code" => 440,
                            "message" => $data['stock_name'] . " đã tồn tại, vui lòng kiểm tra lại"
                        ), 200);
                        return;
                    } else {
                        $data['updated'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                        $data['user_upd'] = $parent;
                        $this->db->where('ID', $id_import)->where('shop_id', $parent)->update('stores', $data);
                        $detail = $this->db->where('ID', $id_import)->where('shop_id', $parent)->from('stores')->get()->row_array();
                        $output_data = array(
                            "error_code" => 0,
                            "data_store" => $detail
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                }
            } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
                $customer = $this->db->from('stores')->where('shop_id', $parent)->where('ID', $id_import)->get()->row_array();
                if (!isset($customer) && count($customer) == 0) {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Kho không tồn tại, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $data['prd_inventory'] = 0;
                    $this->db->where('shop_id', $parent)->where('prd_inventory', $id_import)->update('products', $data);
                    $this->db->where('ID', $id_import)->where('shop_id', $parent)->delete('stores');
                    if ($this->db->affected_rows() > 0) {
                        echo json_encode(array(
                            "error_code" => 0,
                            "message" => "Xóa kho thành công"
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
                if ($_GET['status_input'] === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (empty($_GET['date_from']) || empty($_GET['date_to'])) {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (!empty($_GET['keyword'])):
                    $keyword = $_GET['keyword'];
                else:
                    $keyword = '';
                endif;
                $status = $_GET['status_input'];
                if ($status === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $page = 1;
                if (!empty($_GET['paged'])) {
                    $page = $_GET['paged'];
                }
                $limit = 10;
                $offset = ($page - 1) * $limit;
                if ($status == '0') {
                    if ($_GET['date_from'] != '' && $_GET['date_to'] != '') {
                        $total_imports = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                            ->from('input')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 0])
                            ->where('input_date >=', $_GET['date_from'])
                            ->where('input_date <=', $_GET['date_to'])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_imports'] = $this->db
                            ->from('input')
                            ->limit($limit, $offset)
                            ->order_by('created', 'desc')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 0])
                            ->where('input_date >=', $_GET['date_from'])
                            ->where('input_date <=', $_GET['date_to'])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    } else {
                        $total_imports = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                            ->from('input')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 0])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_imports'] = $this->db
                            ->from('input')
                            ->limit($limit, $offset)
                            ->order_by('created', 'desc')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 0])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    }
                } else if ($status == '1') {
                    if ($_GET['date_from'] != '' && $_GET['date_to'] != '') {
                        $total_imports = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                            ->from('input')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 1])
                            ->where('input_date >=', $_GET['date_from'])
                            ->where('input_date <=', $_GET['date_to'])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_imports'] = $this->db
                            ->from('input')
                            ->limit($limit, $offset)
                            ->order_by('created', 'desc')
                            ->where(['deleted' => 1])
                            ->where('shop_id', $parent)
                            ->where('input_date >=', $_GET['date_from'])
                            ->where('input_date <=', $_GET['date_to'])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    } else {
                        $total_imports = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                            ->from('input')
                            ->where(['deleted' => 1])
                            ->where('shop_id', $parent)
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_imports'] = $this->db
                            ->from('input')
                            ->limit($limit, $offset)
                            ->order_by('created', 'desc')
                            ->where(['deleted' => 1])
                            ->where('shop_id', $parent)
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    }
                } else if ($status == '2') {
                    if ($_GET['date_from'] != '' && $_GET['date_to'] != '') {
                        $total_imports = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                            ->from('input')
                            ->where(['deleted' => 0, 'lack >' => 0])
                            ->where('shop_id', $parent)
                            ->where('input_date >=', $_GET['date_from'])
                            ->where('input_date <=', $_GET['date_to'])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_imports'] = $this->db
                            ->from('input')
                            ->limit($limit, $offset)
                            ->order_by('created', 'desc')
                            ->where(['deleted' => 0, 'lack >' => 0])
                            ->where('shop_id', $parent)
                            ->where('input_date >=', $_GET['date_from'])
                            ->where('input_date <=', $_GET['date_to'])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    } else {
                        $total_imports = $this->db
                            ->from('input')
                            ->where(['deleted' => 0, 'lack >' => 0])
                            ->where('shop_id', $parent)
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_imports'] = $this->db
                            ->from('input')
                            ->limit($limit, $offset)
                            ->order_by('created', 'desc')
                            ->where(['deleted' => 0, 'lack >' => 0])
                            ->where('shop_id', $parent)
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    }
                }
                $output_data = array(
                    "error_code" => 0,
                    "count_import" => $total_imports,
                    "list_import" => $data['_list_imports']
                );
                echo json_encode($output_data, 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $input_data = file_get_contents("php://input");
                $input_data = json_decode($input_data, true);

                $store = $this->db->from('stores')->where('shop_id', $parent)->where('ID', $input_data['store_id'])->limit(1)->get()->row_array();
                if (!empty($store)) {
                    $store_id = $store['ID'];
                } else {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Kho không tồn tại"
                    ), 200);
                    return;
                }
                $detail_input_temp = $input_data['detail_input'];
                if (empty($input_data['input_date'])) {
                    $input_data['input_date'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                    $input_data['input_time'] = time();
                } else {
                    $input_data['input_date'] = gmdate("Y-m-d H:i:s", strtotime(str_replace('/', '-', $input_data['input_date'])) + 7 * 3600);
                    $input_data['input_time'] = strtotime($input_data['input_date']);
                }
                $total_price = 0;
                $total_quantity = 0;
                $this->db->trans_begin();
                $user_init = $id;
                $input_data['shop_id'] = $parent;


                foreach ($input_data['detail_input'] as $item) {

                    $inventory_quantity = $this->db->select('quantity')->from('inventory')->where(['store_id' => $store_id, 'product_id' => $item['id'], 'shop_id' => $parent])->get()->row_array();
                    if (!empty($inventory_quantity)) {
                        $this->db->where(['store_id' => $store_id, 'product_id' => $item['id'], 'shop_id' => $parent])->update('inventory', ['quantity' => $inventory_quantity['quantity'] + $item['quantity'], 'user_upd' => $user_init]);
                    } else {
                        $inventory = ['store_id' => $store_id, 'product_id' => $item['id'], 'quantity' => $item['quantity'], 'user_init' => $user_init, 'shop_id' => $parent];
                        $this->db->insert('inventory', $inventory);
                    }

                    $product = $this->db->select('prd_sls,prd_origin_price')->from('products')->where('shop_id', $parent)->where('ID', $item['id'])->get()->row_array();
                    $sls['prd_sls'] = $product['prd_sls'] + $item['quantity'];
                    $total_price += ($item['price'] * $item['quantity']);
                    $total_quantity += $item['quantity'];
                    if ($item['price'] != $product['prd_origin_price']) {
                        $sls['prd_origin_price'] = (($product['prd_origin_price'] * $product['prd_sls']) + ($item['quantity'] * $item['price'])) / $sls['prd_sls'];
                    }
                    $this->db->where('ID', $item['id'])->update('products', $sls);
                }
                $input_data['total_quantity'] = $total_quantity;
                $input_data['total_price'] = $total_price;


                if (!isset($input_data['payed'])) {
                    $input_data['payed'] = 0;
                }

                if (!isset($input_data['discount']) || $input_data['discount'] == '') {
                    $input_data['discount'] = 0;
                }
                $input_data['input_status'] = 1;
                $lack = $total_price - $input_data['payed'] - $input_data['discount'];
                $input_data['total_money'] = $total_price - $input_data['discount'];
                $input_data['lack'] = $lack > 0 ? $lack : 0;
                $input_data['store_id'] = $store_id;
                $input_data['user_init'] = $id;
                $input_data['detail_input'] = json_encode($input_data['detail_input']);
                $year = date('Y');
                $input_data['nam_dat'] = $year;

                if (empty($input_data['input_code'])) {
                    $count_book = $this->db->select('count(*) as total')
                        ->where('shop_id', $parent)
                        ->where('nam_dat', $year)
                        ->from('input')
                        ->get()
                        ->row_array();
//                print_r($this->db->last_query());die();
                    $order_count = $count_book['total'] + 1;
                    $idcart = str_pad($order_count, 7, '0', STR_PAD_LEFT);
                    $cartcode = "PN_{$year}{$idcart}";
                    $input_data['input_code'] = $cartcode;
                }
//        print_r($input);die();
                $this->db->insert('input', $input_data);
                $id = $this->db->insert_id();
                $temp = array();
                $temp['transaction_code'] = $input_data['input_code'];
                $temp['transaction_id'] = $id;
                $temp['supplier_id'] = isset($input_data['supplier_id']) ? $input_data['supplier_id'] : 0;
                $temp['date'] = $input_data['input_date'];
                $temp['notes'] = $input_data['notes'];
                $temp['user_init'] = $input_data['user_init'];
                $temp['type'] = 2;
                $temp['store_id'] = $store_id;
                foreach ($detail_input_temp as $item) {
                    $report = $temp;
                    $stock = $this->db->select('quantity')->from('inventory')->where('shop_id', $parent)->where(['store_id' => $store_id, 'product_id' => $item['id']])->get()->row_array();
                    $report['product_id'] = $item['id'];
                    $report['price'] = $item['price'];
                    $report['input'] = $item['quantity'];
                    $report['stock'] = $stock['quantity'];
                    $report['shop_id'] = $parent;
                    $report['total_money'] = $report['price'] * $report['input'];
                    $this->db->insert('report', $report);
                }
                if ($this->db->trans_status() === FALSE) {
                    echo json_encode(array(
                        "error_code" => 500,
                        "message" => "Thêm bản ghi không thành công"
                    ), 200);
                    return;
                } else {
                    $this->db->trans_commit();
                    $data = $this->db->where('shop_id', $parent)
                        ->where('id', $id)
                        ->from('input')
                        ->get()
                        ->row_array();
                    echo json_encode(array(
                        "error_code" => 0,
                        "data" => $data
                    ), 200);
                    return;
                }
            }
        }
    }
}