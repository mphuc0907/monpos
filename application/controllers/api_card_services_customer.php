<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_card_services_customer extends CI_Controller
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
//    public function generateRandomString($length = 10) {
//        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
//        $charactersLength = strlen($characters);
//        $randomString = '';
//        for ($i = 0; $i < $length; $i++) {
//            $randomString .= $characters[random_int(0, $charactersLength - 1)];
//        }
//        return $randomString;
//    }
    //API thẻ dịch vụ của khách hàng
    public function card_services_customer($id_card_customer = "") {
        if (!empty($id_card_customer)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo json_encode(array (
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            $id_custom = "";
            if (isset($_GET['id_custom'])) {
                $id_custom = $_GET['customerID'] ;
            }

            $id = self::Getid();
            if ($id == 0){
                $output_data = array(
                    "error_code" => 400,
                    "message" => "Vui lòng đăng nhập lại"
                );
                echo json_encode($output_data, 200);
                return;
            }
            $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
            $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
            if (isset($_GET['id_custom'])) {
                $customer = $this->db->from('customers')->where('ID', $id_custom)->where('shop_id', $parent);
            }
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
//                $query = "SELECT * FROM `cms_card_services_customer` WHERE shopID = '".$parent."' AND customerID = '".$id_card_customer."' AND ID = '".$parent."' AND deleted = 0";
//                $card_customer = $query->get()->row_array();
                $card_customer =  $this->db->from('card_services_customer')
                    ->where('shopID',$parent)
                    ->where('deleted', 0)
                    ->where('ID', $id_card_customer)
//                    ->where('customerID', $customer['ID'])
                    ->get()
                    ->row_array();
//                print_r($this->db->last_query());die();
                if (empty($card_customer)) {
                    $output_data = array(
                        "error_code" => 404,
                        "message" => "Thẻ dịch vụ khách hàng không tồn tại, vui lòng kiểm tra lại"
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                $output_data = array(
                    "error_code" => 0,
                    "data_card_services_customer" => $card_customer
                );
                echo json_encode($output_data, 200);
                return;
            }
            elseif ($_SERVER['REQUEST_METHOD'] == "PUT") {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $data = $this->cms_common_string->allow_post($data, ['card_code', 'card_name', 'price', 'quantity', 'quantity_remaining', 'experied_date', 'image', 'note', 'discount', 'discount_starttime', 'discount_endtime', 'shopID']);
                $this->db->where('ID', $id_card_customer)->update('card_services_customer', $data);
                $card_customer = $this->db->from('card_services_customer')->where('shopID',$parent)->where('ID', $id_card_customer)->get()->row_array();
//                print_r($card_customer);die();
                $output_data = array(
                    "error_code" => 0,
                    "data_card_services_customer" => $card_customer,
                );
                echo json_encode($output_data, 200);
                return;
            }
            elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
//                        print_r($id_card_customer);die();
                $card_customer = $this->db->from('card_services_customer')->where('shopID',$parent)->where('ID', $id_card_customer)->get()->row_array();
//print_r($card_customer);die;
                if (!empty($card_customer) && count($card_customer)) {
//                    print_r($id_card_customer);die();
                    $this->db->where('ID', $id_card_customer)->update('card_services_customer', ['deleted' => 1]);
                    $output_data = array(
                        "error_code" => 0,
                        "message" => 'Xóa thẻ dịch vụ thành công',
                    );
                    echo json_encode($output_data, 200);
                    return;
                }else {
                    $output_data = array(
                        "error_code" => 404,
                        "message" => 'Không tìm thấy thẻ dịch vụ vui lòng kiểm tra lại',
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
//                 if (empty($id_custom)){
//                     $output_data = array(
//                         "error_code" => 405,
//                         "message" => "Chưa chọn khách hàng của thẻ muốn xóa!"
//                     );
//                     echo json_encode($output_data, 200);
//                     return;
//                 }else {
//
//                 }
//                print_r($card_customer);die();

            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] == "PUT" || $_SERVER['REQUEST_METHOD'] == "DELETE") {
                echo json_encode(array(
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            $id = self::Getid();
            if ($id == 0) {
                $output_data = array(
                    "error_code" => 400,
                    "message" => "Vui lòng đăng nhập lại"
                );
                echo json_encode($output_data, 200);
                return;
            }
            if (!empty($_GET['keyword'])):
                $keyword = $_GET['keyword'];
            else:
                $keyword = '';
            endif;
            $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
            $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
            if ($_SERVER['REQUEST_METHOD'] == "GET")
            {
                $page = 1;
                if (!empty($_GET['paged'])) {
                    $page = $_GET['paged'];
                }
                $limit = 10;
                $offset = ($page - 1) * $limit;

//                print_r($parent);die();
                $card_customer = $this->db->from('card_services_customer') ->where("(card_name LIKE '%" . $keyword . "%' or card_code LIKE '%" . $keyword . "%')", NULL, FALSE)->where('deleted', 0)->where('shopID', $parent)->limit($limit, $offset)->get()->result_array();
//                print_r($this->db->last_query());die();
                if (empty($card_customer)) {
                    $output_data = array(
                        "error_code" => 404,
                        "message" =>'Không tồn tại thẻ dịch vụ, vui lòng kiểm tra lại!'
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                $output_data = array(
                    "error_code" => 0,
                    "data" =>$card_customer
                );
                echo json_encode($output_data, 200);
                return;
            }
            elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
//                $data['level'] = 0;
                if ($data['card_code'] == "") {
//                    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $this->db->select_max('card_code')->like('card_code', 'CSV');
                    $max_card_code = $this->db->get_where('card_services_customer',array('shopID' => $parent))->row();
//                    print_r($max_card_code);die();
                    $max_code = (int)(str_replace('CSV', '', $max_card_code->card_code)) + 1;
                    if ($max_code < 10)
                        $data['card_code'] = 'CSV0000' . ($max_code);
                    else if ($max_code < 100)
                        $data['card_code'] = 'CSV000' . ($max_code);
                    else if ($max_code < 1000)
                        $data['card_code'] = 'CSV00' . ($max_code);
                    else if ($max_code < 10000)
                        $data['card_code'] = 'CSV0' . ($max_code);
                    else if ($max_code < 100000)
                        $data['card_code'] = 'CSV' . ($max_code);
                }
                $id_card = $data['id_card'];

                $card = $this->db->from('card_services')->where('ID', $id_card)->where('shopID', $parent)->get()->row_array();
//                print_r($card);
                $card_customers = $this->db->from('card_services_customer')->where(['shopID'=> $parent, 'card_code' => $data['card_code']])->get()->row_array();

//                print_r($data['card_code']);die();


                if (!empty($card_customers['card_code'])) {

                    $output_data = array(
                        "error_code" => 440,
                        "message" => 'Mã thẻ dịch vụ đã tồn tại! Vui lòng chọn mã thẻ dịch vụ khác',
                    );
                    echo json_encode($output_data, 200);
                    return;
                }else {
                $data['shopID'] = $parent;
                $data['card_name'] = $card['card_name'];
                $data['price'] = $card['price'];
                $data['quantity'] = $card['quantity'];
                $data['quantity_remaining'] = $card['quantity'];
//                $data['experied_date'] = $card['experied_date'];
                $data['image'] = $card['image'];
                $data['discount'] = $card['discount'];
                $data['discount_starttime'] = $card['discount_startime'];
                $data['discount_endtime'] = $card['discount_endtime'];

//                    $data['customerID'] = $parent;
                $this->db->insert('card_services_customer', $data);
                    $card_buy['user_buyers'] = $card['user_buyers'] + 1;
                    $this->db->where('ID', $id_card)->update('card_services', $card_buy );


                $cartsvi_group_2 = $this->db->from('card_services_customer')->where('shopID', $parent)->where('card_code',$data['card_code'])->get()->row_array();

                    // Thêm dữ liệu vào hóa đơn
                   $data_oder['output_code'] = $cartsvi_group_2['card_code'];
                    $data_oder['customer_id'] = $cartsvi_group_2['customerID'];
                    $data_oder['store_id'] = $parent;
                    $data_oder['shop_id']= $parent;
                    $data_oder['total_price'] = $cartsvi_group_2['price'];
                    if ($cartsvi_group_2['note'] == "" || !empty($cartsvi_group_2['note'])) {
                        $data_oder['notes'] = "None";
                    }else {
                        $data_oder['notes'] = $cartsvi_group_2['note'];
                    }
                    $data_oder['payment_method'] = 1;
                    $data_oder['customer_pay'] = $cartsvi_group_2['price'];
                    $data_oder['total_origin_price'] = $cartsvi_group_2['price'];
                    $data_oder['total_money'] = $cartsvi_group_2['price'];
                    $data_oder['sale_id'] = $parent;
                    $data_oder['total_quantity'] = "1";
                    $data_oder['user_init'] = $parent;
                    $data_oder['sell_date'] = date("Y/m/d h:i:s");
                    $data_oder['sell_time'] = strtotime( $data_oder['sell_date'] );
                    $data_oder['created'] = date("Y/m/d h:i:s" );
                    $data_oder['updated'] = date("Y/m/d h:i:s" );
//                    if ($data['coupon'] == "" || !empty($data['coupon'])) {
//                        $data_oder['coupon'] = "NaN";
//                    }else {
//                        $data_oder['coupon'] = $data['coupon'];
//                    }
                    $data_oder['detail_order'] = json_encode($cartsvi_group_2);
                    $this->db->insert('orders', $data_oder);
//                    print_r($data_oder['detail_order']);die();
                $output_data = array(
                    "error_code" => 0,
                    "data_cartsvi" => $cartsvi_group_2,
                );

                echo json_encode($output_data, 200);
                return;
                }
            }
        }

    }

    // API thêm lượt sử dụng cho thẻ
    public function card_services_customer_use($id_card_customer_user = ""){
        if (!empty($id_card_customer_user)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo json_encode(array (
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            $id_custom = "";
            if (isset($_GET['id_custom'])) {
                $id_custom = $_GET['customerID'] ;
            }

            $id = self::Getid();
            if ($id == 0){
                $output_data = array(
                    "error_code" => 400,
                    "message" => "Vui lòng đăng nhập lại"
                );
                echo json_encode($output_data, 200);
                return;
            }
            $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
            $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
            if ($_SERVER['REQUEST_METHOD'] == "PUT")
            {

                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $data = $this->cms_common_string->allow_post($data, ['quantity']);

                $services_use = $this->db->from("card_services_customer")->where("ID", $id_card_customer_user)->where("shopID", $parent)->where("deleted", 0)->get()->row_array();
                if (!empty($services_use)) {
                $data_user['quantity_remaining'] = $services_use['quantity_remaining'] - $data['quantity'];
                $note = $services_use['quantity_remaining'] - $data['quantity'];
                $date_now = date("Y-m-d H:i:s");

                if ($services_use['experied_date'] < $date_now){
                    $output_data = array(
                        "error_code" => 405,
                        "message" => "Thẻ đã hết hạn sử dụng",
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                else {
                    if ($services_use['quantity_remaining'] <= 0 || $note <= -1) {
                        $output_data = array(
                            "error_code" => 405,
                            "message" => "SỐ lượt sử dụng thẻ đã hết hoặc số lượt bạn sử dụng quá nhiều với số lượt còn lại của thẻ",
                        );
                        echo json_encode($output_data, 200);
                        return;
                    } else {
                        // update lượt sử dụng
                        $this->db->where('ID', $id_card_customer_user)->update('card_services_customer', $data_user);
                        $data_history = file_get_contents("php://input");
                        $data_history = json_decode($data_history, true);
                        $services_use = $this->db->from("card_services_customer")->where("ID", $id_card_customer_user)->where("shopID", $parent)->get()->row_array();
                        //end update

                        // Thêm mới lịch sử sửa dụng
                            //đoạn tạo mã lịch sự sử dụng thẻ
                            $this->db->select_max('code_history')->like('code_history', 'SHU');
                            //tìm kiếm thẻ và ko trùng lặp thẻ
                            $max_card_code = $this->db->get_where('card_services_history_used',array('shopID' => $parent))->row();
                            $max_code = (int)(str_replace('SHU', '', $max_card_code->code_history)) + 1;
                            if ($max_code < 10)
                                $data_history['code_history'] = 'SHU0000' . ($max_code);
                            else if ($max_code < 100)
                                $data_history['code_history'] = 'SHU000' . ($max_code);
                            else if ($max_code < 1000)
                                $data_history['code_history'] = 'SHU00' . ($max_code);
                            else if ($max_code < 10000)
                                $data_history['code_history'] = 'SHU0' . ($max_code);
                            else if ($max_code < 100000)
                                $data_history['code_history'] = 'SHU' . ($max_code);

                        //kết thúc đoạn tạo mã

                        //Data đưa vào bảng history used
                        if (empty($data_history['time_created'])) {
                            $data_history['time_created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
                        } else {
                            $data_history['time_created'] = gmdate("Y-m-d H:i:s", strtotime(str_replace('/', '-', $data_history['time_created'])) + 7 * 3600);;
                        }
                        $data_history['time_created'] = strtotime($data_history['time_created']);
                        // print_r($data_history['time_used']);die;
                        $data_history['cart_services_ID'] = $services_use['ID'];
                        $data_history['customerID'] = $services_use['customerID'];
                        $data_history['shopID'] = $parent;
                        $data_history['quantity'] = $services_use['quantity'];
                        $data_history['quantity_remaining'] = $services_use['quantity_remaining'];
                        $data_history['amount_used'] = $data['quantity'];

                        //Câu lệnh data vào bảng
                        $this->db->insert("cms_card_services_history_used", $data_history);
                        $output_data = array(
                            "error_code" => 0,
                            "code_history" => $data_history['code_history'],
                            "quantity" => $data_history['quantity'],
                            "quantity_remaining" => $data_history['quantity_remaining'],
                            "amount_used" => $data_history['amount_used'],
                            "message" => "Sử dụng thành công. Số lượt sử dụng còn lại của bạn là: ". $services_use['quantity_remaining'],
                        );
                        //kết thúc
                        echo json_encode($output_data, 200);
                        return;
                    }
                }
                }
                else {
                    $output_data = array(
                        "error_code" => 405,
                        "message" => "Thẻ không tồn tại hoặc đã bị xóa khỏi hệ thống!Vui lòng thử lại",
                    );
                    echo json_encode($output_data, 200);
                    return;
                }

            }
        } else {
            echo json_encode(array (
                "error_code" => 405,
                "message" => "Chưa chọn thẻ khách hàng sửa dụng"
            ), 200);
            return;
        }


    }
    public function card_customer_id($id_customer = "") {
        if (!empty($id_customer)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                echo json_encode(array (
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
            if (isset($_GET['id_custom'])) {
                $id_custom = $_GET['customerID'] ;
            }

            $id = self::Getid();
            if ($id == 0){
                $output_data = array(
                    "error_code" => 400,
                    "message" => "Vui lòng đăng nhập lại"
                );
                echo json_encode($output_data, 200);
                return;
            }
            if (!empty($_GET['keyword'])):
                $keyword = $_GET['keyword'];
            else:
                $keyword = '';
            endif;
            $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
            $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
            if ($_SERVER['REQUEST_METHOD'] == "GET")
            {
                $page = 1;
                if (!empty($_GET['paged'])) {
                    $page = $_GET['paged'];
                }
                $limit = 10;
                $offset = ($page - 1) * $limit;

//                print_r($parent);die();
                $card_services_customs= $this->db->from('card_services_customer') ->where("(card_name LIKE '%" . $keyword . "%' or card_code LIKE '%" . $keyword . "%')", NULL, FALSE)->where('customerID', $id_customer)->where('shopID', $parent)->where("deleted", 0)->limit($limit, $offset)->get()->result_array();
//                 print_r($this->db->last_query());die();
                if (empty($card_services_customs)) {
                    $output_data = array(
                        "error_code" => 404,
                        "message" =>'Khách hàng chưa có thẻ dịch vụ, vui lòng kiểm tra lại!'
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                $output_data = array(
                    "error_code" => 0,
                    "data" => $card_services_customs
                );
                echo json_encode($output_data, 200);
                return;
            }
            else {
                echo json_encode(array (
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
                return;
            }
        }else {
            echo json_encode(array (
                "error_code" => 405,
                "message" => "Chưa chọn khách hàng "
            ), 200);
            return;
        }
    }
}