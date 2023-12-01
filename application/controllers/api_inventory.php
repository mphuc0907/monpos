<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_inventory extends CI_Controller
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

    public function inventory($id_invent = '')
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
        if (!empty($id_invent)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $sup = $this->db->from('stores')->where('ID', $id_invent)->where('shop_id', $parent)->get()->row_array();

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
                $count = $this->db->where('ID', $id_invent)->where('shop_id', $parent)->from('stores')->count_all_results();
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
                        $this->db->where('ID', $id_invent)->where('shop_id', $parent)->update('stores', $data);
                        $detail = $this->db->where('ID', $id_invent)->where('shop_id', $parent)->from('stores')->get()->row_array();
                        $output_data = array(
                            "error_code" => 0,
                            "data_store" => $detail
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                }
            } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
                $customer = $this->db->from('stores')->where('shop_id', $parent)->where('ID', $id_invent)->get()->row_array();
                if (!isset($customer) && count($customer) == 0) {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Kho không tồn tại, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $data['prd_inventory'] = 0;
                    $this->db->where('shop_id', $parent)->where('prd_inventory', $id_invent)->update('products', $data);
                    $this->db->where('ID', $id_invent)->where('shop_id', $parent)->delete('stores');
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
                $store = $this->db->from('stores')->where('shop_id', $parent)->get()->result_array();
                $output_data = array(
                    "error_code" => 0,
                    "data_store" => $store
                );
                echo json_encode($output_data, 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                if ($data['stock_name'] === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }

                $count = $this->db->where('stock_name', $data['stock_name'])->where('shop_id', $parent)->from('stores')->count_all_results();
                if ($count == 0) {
                    $data['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                    $data['user_init'] = $parent;
                    $data['shop_id'] = $parent;
                    $this->db->insert('stores', $data);
                    $detail = $this->db->where('stock_name', $data['stock_name'])->where('shop_id', $parent)->from('stores')->get()->row_array();
                    $output_data = array(
                        "error_code" => 0,
                        "data_store" => $detail
                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    echo json_encode(array(
                        "error_code" => 440,
                        "message" => $data['stock_name'] . " đã tồn tại, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
            }
        }
    }

    public function input_inventory($id_input = '')
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
        if (!empty($id_input)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $data = $this->db->from('input')->where('shop_id', $parent)->where('ID', $id_input)->get()->row_array();
                if (!empty($data)) {
                    echo json_encode(array(
                        "error_code" => 0,
                        "data" => $data
                    ), 200);
                    return;
                } else {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Phiếu nhập không tồn tại, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
            } elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
                $input_data = file_get_contents("php://input");
                $input_data = json_decode($input_data, true);
                if (!isset($input_data['lack_pay'])) {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "Trường dữ liệu bị thiếu, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($input_data['lack_pay'], 'number') === false) {
                    echo json_encode(array(
                        "error_code" => 101,
                        "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                $input = $this->db->from('input')->where('ID', $id_input)->where('shop_id', $parent)->get()->row_array();

                if (isset($input) && count($input)) {
                    $lack = $input['lack'] - $input_data['lack_pay'];
                    if ($lack < 0) {
                        $lack = 0;
                    }
                    $custom_pay = $input['payed'] + $input_data['lack_pay'];
                    if ($custom_pay > $input['total_price']) {
                        $custom_pay = $input['total_price'];
                    }
                    $this->db->where('ID', $id_input)->where('shop_id', $parent)->update('input', ['lack' => $lack, 'payed' => $custom_pay]);
                    if ($this->db->affected_rows() > 0) {
                        $input = $this->db->from('input')->where('ID', $id_input)->where('shop_id', $parent)->get()->row_array();
                        $output_data = array(
                            "error_code" => 0,
                            "message" => "Update thành công.",
                            "data_input" => $input,

                        );
                        echo json_encode($output_data, 200);
                    } else {
                        echo json_encode(array(
                            "error_code" => 500,
                            "message" => "Đã xảy ra lỗi khi cập nhật, vui lòng kiểm tra lại"
                        ), 200);
                        return;
                    }

                } else {
                    echo json_encode(array(
                        "error_code" => 404,
                        "message" => "Phiếu nhập không tồn tại, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }

            } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

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

                if (isset($_GET['status_input'])):
                    $status = $_GET['status_input'];
                else:
                    $status = '';
                endif;
                if ($status === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (empty($_GET['ends_date'])) {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($_GET['starts_date'], 'number') === false) {
                    echo json_encode(array(
                        "error_code" => 101,
                        "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (self::check_validate($_GET['ends_date'], 'number') === false) {
                    echo json_encode(array(
                        "error_code" => 101,
                        "message" => "Trường bạn truyền vào không đúng định dạng, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (empty($_GET['starts_date'])) {

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
                if ($status == '0') {
                    if ($_GET['starts_date'] != '' && $_GET['ends_date'] != '') {
                        $total_imports = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                            ->from('input')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 0])
                            ->where('input_time >=', $_GET['starts_date'])
                            ->where('input_time <=', $_GET['ends_date'])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_imports'] = $this->db
                            ->from('input')
                            ->order_by('created', 'desc')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 0])
                            ->where('input_time >=', $_GET['starts_date'])
                            ->where('input_time <=', $_GET['ends_date'])
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
                            ->order_by('created', 'desc')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 0])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    }
                } else if ($status == '1') {
                    if ($_GET['starts_date'] != '' && $_GET['ends_date'] != '') {
                        $total_imports = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                            ->from('input')
                            ->where('shop_id', $parent)
                            ->where(['deleted' => 1])
                            ->where('input_time >=', $_GET['starts_date'])
                            ->where('input_time <=', $_GET['ends_date'])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_imports'] = $this->db
                            ->from('input')
                            ->order_by('created', 'desc')
                            ->where(['deleted' => 1])
                            ->where('shop_id', $parent)
                            ->where('input_time >=', $_GET['starts_date'])
                            ->where('input_time <=', $_GET['ends_date'])
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
                            ->order_by('created', 'desc')
                            ->where(['deleted' => 1])
                            ->where('shop_id', $parent)
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->result_array();
                    }
                } else if ($status == '2') {
                    if ($_GET['starts_date'] != '' && $_GET['ends_date'] != '') {
                        $total_imports = $this->db
                            ->select('count(ID) as quantity, sum(total_money) as total_money, sum(lack) as total_debt')
                            ->from('input')
                            ->where(['deleted' => 0, 'lack >' => 0])
                            ->where('shop_id', $parent)
                            ->where('input_time >=', $_GET['starts_date'])
                            ->where('input_time <=', $_GET['ends_date'])
                            ->where("(input_code LIKE '%" . $keyword . "%')", NULL, FALSE)
                            ->get()
                            ->row_array();
                        $data['_list_imports'] = $this->db
                            ->from('input')
                            ->order_by('created', 'desc')
                            ->where(['deleted' => 0, 'lack >' => 0])
                            ->where('shop_id', $parent)
                            ->where('input_time >=', $_GET['starts_date'])
                            ->where('input_time <=', $_GET['ends_date'])
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
                    "total_imports" => $total_imports,
                    "data_imports" => $data['_list_imports']
                );
                echo json_encode($output_data, 200);
                return;
            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $input = file_get_contents("php://input");
                $input = json_decode($input, true);

                $detail_input_temp = $input['detail_input'];
                if (!empty($input['store_id'])) {
                    $store_id = $input['store_id'];
                } else {
                    $store_id = 0;
                }
                if (empty($input['input_date'])) {
                    $input['input_date'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                    $input['input_time'] = time();
                } else {
                    $input['input_date'] = gmdate("Y-m-d H:i:s", strtotime(str_replace('/', '-', $input['input_date'])) + 7 * 3600);
                    $input['input_time'] = strtotime($input['input_date']);
                }
                $total_price = 0;
                $total_quantity = 0;
                $this->db->trans_begin();
                $user_init = $id;
                $input['shop_id'] = $parent;
                if ($input['input_status'] == 1) {
                    foreach ($input['detail_input'] as $item) {

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
                } else {
                    foreach ($input['detail_input'] as $item) {
                        $total_price += ($item['price'] * $item['quantity']);
                        $total_quantity += $item['quantity'];
                    }
                }
                $input['total_quantity'] = $total_quantity;
                $input['total_price'] = $total_price;
                if (!isset($input['payed'])) {
                    $input['payed'] = 0;
                }

                if (!isset($input['discount']) || $input['discount'] == 'NaN') {
                    $input['discount'] = 0;
                }

                $lack = $total_price - $input['payed'] - $input['discount'];
                $input['total_money'] = $total_price - $input['discount'];
                $input['lack'] = $lack > 0 ? $lack : 0;
                $input['store_id'] = $store_id;
                $input['user_init'] = $id;
                $input['detail_input'] = json_encode($input['detail_input']);

                $this->db->select_max('input_code')->like('input_code', 'PN');
                $max_input_code = $this->db->get_where('input', array('shop_id' => $parent))->row();
                $max_code = (int)(str_replace('PN', '', $max_input_code->input_code)) + 1;
                if ($max_code < 10)
                    $input['input_code'] = 'PN000000' . ($max_code);
                else if ($max_code < 100)
                    $input['input_code'] = 'PN00000' . ($max_code);
                else if ($max_code < 1000)
                    $input['input_code'] = 'PN0000' . ($max_code);
                else if ($max_code < 10000)
                    $input['input_code'] = 'PN000' . ($max_code);
                else if ($max_code < 100000)
                    $input['input_code'] = 'PN00' . ($max_code);
                else if ($max_code < 1000000)
                    $input['input_code'] = 'PN0' . ($max_code);
                else if ($max_code < 10000000)
                    $input['input_code'] = 'PN' . ($max_code);
//        print_r($input);die();
                $this->db->insert('input', $input);
                $id_input = $this->db->insert_id();
                if ($input['input_status'] == 1) {
                    $temp = array();
                    $temp['transaction_code'] = $input['input_code'];
                    $temp['transaction_id'] = $id_input;
                    $temp['supplier_id'] = isset($input['supplier_id']) ? $input['supplier_id'] : 0;
                    $temp['date'] = $input['input_date'];
                    $temp['notes'] = $input['notes'];
                    $temp['user_init'] = $input['user_init'];
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
                }
                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    echo json_encode(array(
                        "error_code" => 500,
                        "message" => "Đã xảy ra lỗi khi cập nhật, vui lòng kiểm tra lại"
                    ), 200);
                    return;
                } else {
                    $this->db->trans_commit();

                    $data = $this->db->from('input')->where('shop_id', $parent)->where('ID', $id_input)->get()->row_array();
                    $output_data = array(
                        "error_code" => 0,
                        "data_imports" => $data
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }
        }
    }

    public function stock()
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
        $user = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($user['group_id'] == 1) ? 1 : (($user['parent_id'] == 1) ? $user['id'] : $user['parent_id']);

        if (!empty($_GET['keyword'])):
            $keyword = $_GET['keyword'];
        else:
            $keyword = '';
        endif;
        $prd_group_id = $_GET['prd_group_id'];
        $prd_manufacture_id = $_GET['prd_manufacture_id'];
        $store_id = $_GET['store_id'];
        if (isset($_GET['status_stock'])):
            $status = $_GET['status_stock'];
        else:
            $status = '';
        endif;

        if ($status === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($prd_group_id === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($prd_manufacture_id === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        if ($store_id === '') {
            echo json_encode(array(
                "error_code" => 110,
                "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
            ), 200);
            return;
        }
        $total_prd = 0;
        $data = null;
        if ($prd_group_id == '-1') {
            if ($prd_manufacture_id == '-1') {
                if ($status == '0') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where('deleted', 0)
                        ->where('inventory.shop_id', $parent)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where('deleted', 0)
                        ->where('inventory.shop_id', $parent)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                } else if ($status == '1') {
                    //hien thi thong tin ton kho option1 = -1, option2 = -1, option3 = 1
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity >' => 0, 'prd_status' => 1])
                        ->where('store_id', $store_id)
                        ->where('inventory.shop_id', $parent)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price,inventory.updated')
                        ->from('inventory')
                        ->where('inventory.shop_id', $parent)
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['products.deleted' => 0, 'inventory.quantity >' => 0, 'prd_status' => 1])
                        ->where('store_id', $store_id)
                        ->where('inventory.shop_id', $parent)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                } else if ($status == '2') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity ' => 0])
                        ->where('store_id', $store_id)
                        ->where('inventory.shop_id', $parent)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity ' => 0])
                        ->where('inventory.shop_id', $parent)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                }
            } else {
                if ($status == '0') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('store_id', $store_id)
                        ->where('inventory.shop_id', $parent)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('store_id', $store_id)
                        ->where('inventory.shop_id', $parent)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                } else if ($status == '1') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity >' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('store_id', $store_id)
                        ->where('inventory.shop_id', $parent)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity >' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('store_id', $store_id)
                        ->where('inventory.shop_id', $parent)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                } else if ($status == '2') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity ' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('store_id', $store_id)
                        ->where('inventory.shop_id', $parent)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity ' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('store_id', $store_id)
                        ->where('inventory.shop_id', $parent)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                }
            }
        } else {
            $temp = $this->getCategoriesByParentId($prd_group_id);
            $temp[] = $prd_group_id;
            if ($prd_manufacture_id == '-1') {
                if ($status == '0') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where('deleted', 0)
                        ->where('inventory.shop_id', $parent)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where('deleted', 0)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                } else if ($status == '1') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity >' => 0])
                        ->where('inventory.shop_id', $parent)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity >' => 0])
                        ->where('inventory.shop_id', $parent)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                } else if ($status == '2') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity ' => 0])
                        ->where('inventory.shop_id', $parent)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity ' => 0])
                        ->where('inventory.shop_id', $parent)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                }
            } else {
                if ($status == '0') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where('inventory.shop_id', $parent)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('inventory.shop_id', $parent)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                } else if ($status == '1') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity >' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('inventory.shop_id', $parent)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity >' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('inventory.shop_id', $parent)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                } else if ($status == '2') {
                    $total_prd = $this->db
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity ' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('inventory.shop_id', $parent)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->count_all_results();
                    $data['data']['_list_product'] = $this->db
                        ->select('products.ID,prd_code,prd_name,quantity,prd_sell_price,prd_origin_price')
                        ->from('inventory')
                        ->join('products', 'products.ID=inventory.product_id', 'INNER')
                        ->where(['deleted' => 0, 'quantity ' => 0, 'prd_manufacture_id' => $prd_manufacture_id])
                        ->where('inventory.shop_id', $parent)
                        ->where_in('prd_group_id', $temp)
                        ->where('store_id', $store_id)
                        ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                        ->order_by('inventory.created', 'desc')
                        ->get()->result_array();
                }
            }
        }
        $totaloinvent = $totalsinvent = $sls = 0;
        $tempdata = $data['data']['_list_product'];
        foreach ($tempdata as $item) {
            $sls += $item['quantity'];
            $totaloinvent += ($item['quantity'] * $item['prd_origin_price']);
            $totalsinvent += ($item['quantity'] * $item['prd_sell_price']);
        }

        $data['total_product'] = $sls;
        $data['total_funds'] = $totaloinvent;
        $data['total_price'] = $totalsinvent;
        $data['data']['_sl_product'] = $total_prd;
        echo json_encode(array(
            "error_code" => 0,
            "data" => $data
        ), 200);
        return;
    }
}