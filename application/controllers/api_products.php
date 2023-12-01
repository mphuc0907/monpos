<?php use App\Gettoken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

if (!defined('BASEPATH')) exit('No direct script access allowed');


class api_products extends CI_Controller
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

    public function products($id_sp = '')
    {
        if (!empty($id_sp)) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

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
            $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
            $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                $product = $this->db->from('products')->where('shop_id', $parent)->where('ID', $id_sp)->get()->row_array();
                if (empty($product)) {
                    $output_data = array(
                        "error_code" => 404,
                        "message" => "sản phẩm không tồn tại, vui lòng kiểm tra lại"
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                $output_data = array(
                    "error_code" => 0,
                    "data_products" => $product,
                );
                echo json_encode($output_data, 200);
                return;
            }elseif ($_SERVER['REQUEST_METHOD'] == 'PUT'){
                $input_data = file_get_contents("php://input");
                $input_data = json_decode($input_data, true);
                $input_data = $this->cms_common_string->allow_post($input_data, ['prd_code', 'prd_name', 'prd_sls', 'prd_inventory', 'prd_allownegative', 'prd_origin_price', 'prd_sell_price', 'prd_group_id', 'prd_manufacture_id', 'prd_vat', 'prd_image_url', 'prd_descriptions', 'display_website', 'prd_new','prd_status', 'prd_hot', 'prd_highlight']);
                $input_data['user_upd'] = $id;
                $this->db->where('ID', $id_sp)->update('products', $input_data);
                $product = $this->db->from('products')->where('shop_id', $parent)->where('ID', $id_sp)->get()->row_array();
                $output_data = array(
                    "error_code" => 0,
                    "data_products" => $product,
                );
                echo json_encode($output_data, 200);
                return;
            }elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE'){
                $product = $this->db->from('products')->where('shop_id',$parent)->where('deleted',0)->where('ID', $id_sp)->get()->row_array();
//                dd($product);die();
                if (!empty($product) && count($product)) {
                    $this->db->where('ID', $id_sp)->update('products', ['deleted' => 1]);
                    $output_data = array(
                        "error_code" => 0,
                        "message" => 'Xóa sản phẩm thành công',
                    );
                    echo json_encode($output_data, 200);
                    return;

                } else {
                    $output_data = array(
                        "error_code" => 404,
                        "message" => 'Không tìm thấy sản phẩm vui lòng kiểm tra lại',
                    );
                    echo json_encode($output_data, 200);
                    return;
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
            $id = self::Getid();
            if ($id == 0) {

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
                if (empty($_GET['group_product_id'])) {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }
                if (empty($_GET['manufacture_id'])) {

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
                $status = $_GET['status_products'];
                $group_product_id = $_GET['group_product_id'];
                $manufacture_id = $_GET['manufacture_id'];
                if ($status === '') {
                    echo json_encode(array(
                        "error_code" => 110,
                        "message" => "API thiếu dữ liệu vui lòng kiểm tra lại"
                    ), 200);
                    return;
                }

                if ($status == '0') {
                    if ($group_product_id == '-1') {
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 1, 'deleted' => 0])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 1, 'deleted' => 0])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 1, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 1, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()->result_array();
                        }
                    } else {
                        $temp = $this->getCategoriesByParentId($group_product_id);
                        $temp[] = $group_product_id;
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 1, 'deleted' => 0])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 1, 'deleted' => 0])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 1, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 1, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        }
                    }
                } else if ($status == '1') {
                    if ($group_product_id == '-1') {
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 0, 'deleted' => 0])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 0, 'deleted' => 0])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 0, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 0, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        }
                    } else {
                        $temp = $this->getCategoriesByParentId($group_product_id);
                        $temp[] = $group_product_id;
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 0, 'deleted' => 0])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 0, 'deleted' => 0])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['prd_status' => 0, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['prd_status' => 0, 'deleted' => 0, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        }
                    }
                } else if ($status == '2') {
                    if ($group_product_id == '-1') {
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['deleted' => 1])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 1])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['deleted' => 1, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 1, 'prd_manufacture_id' => $manufacture_id])
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        }
                    } else {
                        $temp = $this->getCategoriesByParentId($group_product_id);
                        $temp[] = $group_product_id;
                        if ($manufacture_id == '-1') {
                            $total_prd = $this->db
                                ->from('products')
                                ->where('deleted', 1)
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where('deleted', 1)
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        } else {
                            $total_prd = $this->db
                                ->from('products')
                                ->where(['deleted' => 1, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->count_all_results();
                            $data['data']['_list_product'] = $this->db
                                ->select('ID,prd_code,prd_name,prd_sls,prd_sell_price,prd_group_id,prd_manufacture_id,prd_image_url,prd_status')
                                ->from('products')
                                ->order_by('created', 'desc')
                                ->where(['deleted' => 1, 'prd_manufacture_id' => $manufacture_id])
                                ->where_in('prd_group_id', $temp)
                                ->where("(prd_code LIKE '%" . $keyword . "%' OR prd_name LIKE '%" . $keyword . "%')", NULL, FALSE)
                                ->where('products.shop_id', $parent)
                                ->get()
                                ->result_array();
                        }
                    }
                }
                $output_data = array(
                    "error_code" => 0,
                    "count_products" => $total_prd,
                    "list_orders" => $data['data']['_list_product']
                );
                echo json_encode($output_data, 200);
                return;

            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $input_data = file_get_contents("php://input");
                $input_data = json_decode($input_data, true);
                $store = $this->db->from('stores')->where('shop_id', $parent)->limit(1)->get()->row_array();
                if(!empty($store)){
                    $store_id = $store['ID'];
                }else{
                    $store_id = 0;
                }
                $input_data = $this->cms_common_string->allow_post($input_data, ['prd_code', 'prd_parent_id', 'prd_name', 'prd_sls', 'prd_inventory', 'prd_allownegative', 'prd_origin_price', 'prd_sell_price', 'prd_group_id', 'prd_manufacture_id', 'prd_vat', 'prd_image_url', 'prd_descriptions', 'display_website', 'prd_new', 'prd_hot', 'prd_highlight']);
                $check_code = $this->db->select('ID')->from('products')->where('prd_code', $input_data['prd_code'])->where('shop_id', $parent)->get()->row_array();
                if (!empty($check_code) && count($check_code)) {
                    $output_data = array(
                        "error_code" => 520,
                        "message" => 'Mã sản phẩm ' . $input_data['prd_code'] . ' đã tồn tại trong hệ thống. Vui lòng chọn mã khác.'
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
                $this->db->trans_begin();
                $input_data['user_init'] = $id;
                if ($input_data['prd_code'] == '') {
                    $this->db->select_max('prd_code')->like('prd_code', 'SP');
                    $max_product_code = $this->db->get_where('products',array('shop_id' => $parent))->row();
                    $max_code = (int)(str_replace('SP', '', $max_product_code->prd_code)) + 1;
                    if ($max_code < 10)
                        $input_data['prd_code'] = 'SP0000' . ($max_code);
                    else if ($max_code < 100)
                        $input_data['prd_code'] = 'SP000' . ($max_code);
                    else if ($max_code < 1000)
                        $input_data['prd_code'] = 'SP00' . ($max_code);
                    else if ($max_code < 10000)
                        $input_data['prd_code'] = 'SP0' . ($max_code);
                    else if ($max_code < 100000)
                        $input_data['prd_code'] = 'SP' . ($max_code);
                }
                $quantity = $input_data['prd_sls'];
                unset($input_data['prd_parent_id']);
                unset($input_data['store_id']);
                $input_data['shop_id'] = $parent;
                $this->db->insert('products', $input_data);
                $product_id = $this->db->insert_id();
                $user_init = $input_data['user_init'];
                $inventory = ['store_id' => $store_id, 'product_id' => $product_id, 'quantity' => $quantity, 'user_init' => $user_init,'shop_id' => $parent];
                $this->db->insert('inventory', $inventory);
                // thêm bảng báo cáo
                $report = array();
                $report['transaction_code'] = $input_data['prd_code'];
                $report['notes'] = 'Khai báo hàng hóa';
                $report['user_init'] = $input_data['user_init'];
                $report['type'] = 1;
                $report['store_id'] = $store_id;
                $report['shop_id'] = $parent;
                $report['product_id'] = $product_id;
                $report['input'] = $quantity;
                $report['stock'] = $quantity;


                $this->db->insert('report', $report);
                //kết thúc
                //$picture = array();
                //$picture['id'] = 0;

                $order_2 = $this->db->from('products')->where('ID', $product_id)->limit(1)->get()->row_array();
                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    $output_data = array(
                        "error_code" => 500,
                        "message" => 'Lỗi thêm sản phẩm, vui lòng kiểm tra lại',
                    );
                    echo json_encode($output_data, 200);
                    return;
                } else {
                    $this->db->trans_commit();
                    $output_data = array(
                        "error_code" => 0,
                        "data_products" => $order_2,
                    );
                    echo json_encode($output_data, 200);
                    return;
                }
            }

        }
    }

    public function products_restore($id_sp){
        if ($_SERVER['REQUEST_METHOD'] != 'PUT') {

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
        $usser_id = $this->db->from('users')->where('id', $id)->limit(1)->get()->row_array();
        $parent = ($usser_id['group_id'] == 1) ? 1 : (($usser_id['parent_id'] == 1) ? $usser_id['id'] : $usser_id['parent_id']);
        $product = $this->db->from('products')->where('shop_id',$parent)->where('deleted',1)->where('ID', $id_sp)->get()->row_array();
        if (!empty($product) && count($product)) {
            $this->db->where('ID', $id_sp)->update('products', ['deleted' => 0]);
            $product = $this->db->from('products')->where('shop_id',$parent)->where('ID', $id_sp)->get()->row_array();
            $output_data = array(
                "error_code" => 0,
                "message" => 'Khôi phục sản phẩm thành công',
                "data_products" => $product,
            );
            echo json_encode($output_data, 200);
            return;

        } else {
            $output_data = array(
                "error_code" => 404,
                "message" => 'Không tìm thấy sản phẩm vui lòng kiểm tra lại',
            );
            echo json_encode($output_data, 200);
            return;
        }
    }
}