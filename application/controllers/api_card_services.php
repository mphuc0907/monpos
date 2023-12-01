<?php

use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_card_services extends CI_Controller
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

    public function cart_services($id_card = '')  {
//        dd('ygasyasgyxga');
        if (!empty($id_card)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                echo json_decode(array (
                    "error_code" => 405,
                    "message" => "Phương thức truyền vào không đúng vui lòng kiểm tra lại"
                ), 200);
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
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                if (!empty($_GET['customerID']) || !empty($_GET['cart_services_ID'])) {
                    $customerID = $_GET['customerID'];
                    $cart_services_ID = $_GET['cart_services_ID'];
                }else {
                    $cart_services = $this->db->from('card_services')->where('shopID', $parent)->where('ID', $id_card)->where('deleted', 0)->get()->row_array();
//                dd($cart_services);
                    if (empty($cart_services)) {
                        $output_data = array(
                            "error_code" => 404,
                            "message" => "Thẻ dịch vụ không tồn tại, vui lòng kiểm tra lại"
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                    $output_data = array(
                        "error_code" => 0,
                        "data_card_services" => $cart_services,
                    );
                    echo json_encode($output_data, 200);
                    return;
                }

            }elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {

                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
                $cart = $this->db->from('card_services')->where('shopID', $parent)->where('ID', $id_card)->get()->row_array();
                $pro = $this->db->from('products')->where('shop_id', $parent)->where('prd_code', $cart['card_code'])->get()->row_array();


                $data = $this->cms_common_string->allow_post($data, ['card_code', 'card_name', 'price', 'quantity', 'quantity_remaining', 'experied_date', 'image', 'note', 'discount', 'discount_startime', 'discount_endtime', 'customerID', 'shopID']);
                $data_product['prd_code'] = $data['card_code'];
                $data_product['prd_name'] = $data['card_name'];
                $data_product['prd_origin_price'] = $data['price'];
                $data_product['prd_sell_price'] = $data['price'];
                $data_product['prd_image_url'] = $data['image'];
                $data_product['updated'] = date("Y/m/d h:i:s" );
                $this->db->where('ID', $pro['ID'])->update('products', $data_product);
                $this->db->where('ID', $id_card)->update('card_services', $data);
                $cart_services =$this->db->from('card_services')->where('shopID', $parent)->where('ID', $id_card)->get()->row_array();
                $output_data = array(
                    "error_code" => 0,
                    "data_card_services" => $cart_services,
                );
                echo json_encode($output_data, 200);
                return;
            }elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
                $cart_services = $this->db->from('card_services')->where('shopID', $parent)->where('ID', $id_card)->get()->row_array();
//                print_r($cart_services->__toString());die();
                $pro = $this->db->from('products')->where('shop_id', $parent)->where('prd_code', $cart_services['card_code'])->get()->row_array();

                if (!empty($cart_services) && count($cart_services)) {
                    $this->db->where('ID', $id_card)->update('card_services', ['deleted' => 1]);
                    $this->db->where('ID', $pro['ID'])->update('products', ['deleted' => 1]);
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
            }
        }else {
            if ($_SERVER['REQUEST_METHOD'] == "PUT" || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
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
            if ($_SERVER['REQUEST_METHOD'] == "GET") {
               
                if($keyword != ""){
                   
                    $cart_services = $this->db->from('card_services')  // Specifies the table 'card_services' in the database.
                    ->where("(card_name LIKE '%" . $keyword . "%' or card_code LIKE '%" . $keyword . "%')", NULL, FALSE)  // Adds a WHERE condition with a LIKE comparison for 'card_name' column and the provided $keyword.
//                    ->where("(card_code LIKE '%" . $keyword . "%')", NULL, FALSE)  // Adds another WHERE condition with a LIKE comparison for 'card_code' column and the provided $keyword.
                    ->where('deleted', 0)  // Adds a WHERE condition to filter rows where 'deleted' column is 0.
                    ->where('shopID', $parent)  // Adds another WHERE condition to filter rows where 'shopID' column matches the value in the $parent variable.
                    ->get()  // Executes the query.
                    ->result_array();  // Fetches the result set as an array.
                    if (empty($cart_services)) {
                        $output_data = array(
                            "error_code" => 404,
                            "message" =>'Không tồn tại thẻ dịch vụ, vui lòng kiểm tra lại!'
                        );
                        echo json_encode($output_data, 200);
                        return;
                    }
                    $output_data = array(
                        "error_code" => 0,
                        "data" =>$cart_services
                    );
                    echo json_encode($output_data, 200);
                    return;
                }else {
                    $cart_services = $this->db->from('card_services')->where('deleted', 0)->where('shopID', $parent)->get()->result_array();
                    //                print_r($cart_services);
                    
                                    if (empty($cart_services)) {
                                        $output_data = array(
                                            "error_code" => 404,
                                            "message" =>'Không tồn tại thẻ dịch vụ, vui lòng kiểm tra lại!'
                                        );
                                        echo json_encode($output_data, 200);
                                        return;
                                    }
                                    $output_data = array(
                                        "error_code" => 0,
                                        "data" =>$cart_services
                                    );
                                    echo json_encode($output_data, 200);
                                    return;
                }
                
            }elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $data = file_get_contents("php://input");
                $data = json_decode($data, true);
//                $data['level'] = 0;
                if (empty($data['card_name'] && $data['price'] && $data['quantity'] && $data['quantity_remaining'] && $data['image'])) {

                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if ($data['card_code'] == "") {
                    $this->db->select_max('card_code')->like('card_code', 'CSV');
                    $max_card_code = $this->db->get_where('card_services',array('shopID' => $parent))->row();
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
                if (!empty($data['discount'])) {
                    if (empty($data['discount_startime'])) {
                        echo json_encode(array(
                            "error_code" => 110,
                            "message" => "Vui lòng nhập thêm thời gian bắt đầu!"
                        ), 200);
                        return;
                    }elseif (empty($data['discount_endtime'])) {
                        echo json_encode(array(
                            "error_code" => 110,
                             "message" => "Vui lòng nhập thêm thời gian kết thúc!"
                        ), 200);
                        return;
                    }
                }


                $cartsvi = $this->db->from('card_services')->where(['shopID'=>$parent, 'card_code' => $data['card_code']])->get()->row_array();
//                print_r(count($cartsvi));die();
                if (!empty($cartsvi) && count($cartsvi)) {
                    $output_data = array(
                        "error_code" => 440,
                        "message" => 'Đã tồn tại thẻ dịch vụ, vui lòng kiểm tra lại',
                    );
                    echo json_encode($output_data, 200);
                    return;
                }else {
                    $data['shopID'] = $parent;
//                    $data['customerID'] = $parent;
                    $this->db->insert('card_services', $data);
                    //Data truyền vào product
                    $data_pr['prd_code'] = $data['card_code'];
                    $data_pr['prd_name'] = $data['card_name'];
                    $data_pr['prd_origin_price'] = $data['price'];
                    $data_pr['prd_image_url'] = $data['image'];
                    $data_pr['created'] = date("Y/m/d h:i:s" );
                    $data_pr['updated'] = date("Y/m/d h:i:s" );
                    $data_pr['prd_sell_price'] = $data['price'];
                    $data_pr['shop_id'] = $parent;
                    $data_pr['user_init'] = $parent;
                    //Kết thúc truyền vào data
                    $this->db->insert('products', $data_pr);
                    $cartsvi_group_2 = $this->db->from('card_services')->where('shopID', $parent)->where('card_code',$data['card_code'])->get()->row_array();
                    $pro_group_2 = $this->db->from('products')->where('shop_id', $parent)->where('prd_code',$data_pr['prd_code'])->get()->row_array();
                    //Data truyền vào report
                    $data_rp['transaction_code'] = $data['card_code'];
                    $data_rp['transaction_id'] = $id;
                    $data_rp['shop_id'] = $parent;
                    $data_rp['product_id'] = $pro_group_2['ID'];
                    $data_rp['total_money'] =$data['price'];
                    $data_rp['origin_price'] = $data['price'];
                    $data_rp['notes'] = 'Khai báo hàng hóa'. $data['card_name'];;
                    $data_rp['price'] = $data['price'];
                    $data_rp['user_init'] = $parent;
                    $data_rp['type'] = 1;
                    $data_rp['store_id'] = $parent;
                    $data_rp['created'] = date("Y/m/d h:i:s" );
                    $data_rp['updated'] = date("Y/m/d h:i:s" );
                     //Kết thúc truyền vào data

                    $this->db->insert('report', $data_rp);

                    $output_data = array(
                        "error_code" => 0,
                        "data_cartsvi" => $cartsvi_group_2,
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
//                $dat
            }
        }
    }
}