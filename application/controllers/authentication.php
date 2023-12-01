<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

// controller control user authentication
class Authentication extends CI_Controller
{
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = $this->cms_authentication->check();
    }

    /* default login when acess manager system */
    public function index()
    {
        if ($this->auth != null) $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
        $data['seo']['title'] = "Login - Phần mềm quản lý bán hàng";

        if ($this->input->post('login')) {
            $_post = $this->input->post('data');
            $data['data']['_post'] = $_post;

            $this->form_validation->set_error_delimiters('<li>', '</li>');
            $this->form_validation->set_rules('data[username]', 'tên đăng nhập', 'trim|required|min_length[3]|max_length[100]|regex_match[/^([a-z0-9_@\.])+$/i]|callback__check_user');
            $this->form_validation->set_rules('data[password]', 'mật khẩu', 'trim|required|min_length[1]|callback__check_password[' . $_post['username'] . ']');
            if ($this->form_validation->run() == true) {
                $user = $this->db->select('username,password,salt')->where('username', $_post['username'])->or_where('email', $_post['username'])->from('users')->get()->row_array();
                CMS_Cookie::put('user_logged' . CMS_PREFIX, CMS_Cookie::encode(json_encode($user)), COOKIE_EXPIRY);
                CMS_Session::put('username', $user['username']);
                $check_user = $this->db->where('username', $_post['username'])->or_where('email', $_post['username'])->from('users')->get()->row_array();
                if ($check_user->parent_id != 1) {
                    $user_2 = $this->db->where('username', $_post['parent_user'])->or_where('email', $_post['parent_user'])->from('users')->get()->row_array();
                    if (!empty($user_2) && $user_2->id == $check_user->parent_id ) {
                        $this->db->where('username', $user['username'])->update('users', ['logined' => gmdate("Y:m:d H:i:s", time() + 7 * 3600), 'ip_logged' => $_SERVER['SERVER_ADDR']]);
                    }
                } else {
                    $this->db->where('username', $user['username'])->update('users', ['logined' => gmdate("Y:m:d H:i:s", time() + 7 * 3600), 'ip_logged' => $_SERVER['SERVER_ADDR']]);
                }
                $this->db->where('username', $user['username'])->update('users', ['logined' => gmdate("Y:m:d H:i:s", time() + 7 * 3600), 'ip_logged' => $_SERVER['SERVER_ADDR']]);
                $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
            }
        }
        $data['template'] = 'auth/login';
        $this->load->view('layout/auth', isset($data) ? $data : null);
    }

//    public function register()
//    {
//        if ($this->auth != null) $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
//        $data['seo']['title'] = "Đăng ký - Phần mềm quản lý bán hàng";
//
//        if ($this->input->post('signup')) {
//            $_post = $this->input->post('data');
//            $data['data']['_post'] = $_post;
////            print_r( $data['data']['_post']);die();
//            $this->form_validation->set_error_delimiters('<li>', '</li>');
//            $this->form_validation->set_rules('data[display_name]', 'Tên khách hàng', 'trim|required|min_length[3]|max_length[100]|regex_match(/^[a-zA-Z\s]+$/)');
//            $this->form_validation->set_rules('data[username]', 'tên đăng nhập', 'trim|required|min_length[3]|max_length[100]|regex_match[/^([a-z0-9_@\.])+$/i]');
//            $this->form_validation->set_rules('data[email]', 'Email', 'trim|required|min_length[3]|max_length[100]|valid_email');
//            $this->form_validation->set_rules('data[password]', 'mật khẩu', 'trim|required|min_length[8]|=');
//            $this->form_validation->set_rules('data[re_password]', 'Nhập lại mật khẩu', 'trim|required|callback__check_repassword[' . $_post['password'] . ']');
//            if ($_post['password'] != $_post['re_password']) {
////                print_r(1);die();
//                $data['error']= 'Mật khẩu và xác nhận mật khẩu không trùng khớp';
//
//            }else {
//                if ($this->form_validation->run() == true) {
//                    $user = $this->db->select('username,password,salt')->where('username', $_post['username'])->or_where('email', $_post['email'])->from('users')->get()->row_array();
//                    if (empty($user)) {
//                        $user['display_name'] = $_post['display_name'];
//                        $user['username'] = $_post['username'];
//                        $user['email'] = $_post['email'];
//                        $user['group_id'] = 4;
//
//                        $user['salt'] = $this->cms_common_string->random(69);
//                        $user['password'] = $this->cms_common_string->password_encode($_post['password'], $user['salt']);
//                        $user['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
//                        $this->db->insert('users', $user);
//                    } else {
//
//                    }
//                    $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
//                }
//            }
//        }
//        $data['template'] = 'auth/signup';
////        print_r($data);die();
//        $this->load->view('layout/auth', isset($data) ? $data : null);
//    }

    public function _check_password($password, $username)
    {
        if ($this->_check_user($username) == true) {
            $user = $this->db->select('username,password,salt')->where('username', $username)->or_where('email', $username)->from('users')->get()->row_array();
            $password = $this->cms_common_string->password_encode($password, $user['salt']);
            if ($password != $user['password']) {
                $this->form_validation->set_message('_check_password', 'Mật khẩu không chính xác.');
                return false;
            }
        }
        return true;
    }

    public function _check__repassword($re_password, $password)
    {
//        print_r(1);die();
        if ($password != $re_password) {
            $this->form_validation->set_message('_check__repassword', 'Xác nhận Mật khẩu và mật khẩu không chính xác.');
            return false;
        }
        return true;
    }

    public function _check_user($username)
    {

        $count = $this->db->where('user_status', 1)->where('username', $username)->or_where('email', $username)->from('users')->count_all_results();
        if ($count == 0) {
            $this->form_validation->set_message('_check_user', 'Tài khoản đăng nhập không hợp lệ.');//tự tạo câu lệnh xuất riêng vs hàm riêng
            return false;
        }
        return true;
    }

    /* Create Root account */

    public function root_create_account()
    {


        $data['username'] = "Adminstrator";
        $data['salt'] = $this->cms_common_string->random(69);
        $data['password'] = $this->cms_common_string->password_encode('Adminstrator', $data['salt']);
        $data['created'] = gmdate("Y:m:d H:i:s", time() + 7 * 3600);
        $data['email'] = "frdevhero@gmail.com";
        $data['display_name'] = "Adminstrator";
        $data['user_status'] = 0;
        $data['group_id'] = 4;
        $this->db->insert('users', $data);
    }

    public function fg_password()
    {
        if ($this->auth != null) $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
        $data['seo']['title'] = "Lấy lại mật khẩu - Phần mềm quản lý bán hàng";

        if ($this->input->post('forgot')) {
            $_post = $this->input->post('data');
            $data['data']['_post'] = $_post;
            $this->form_validation->set_error_delimiters('<span>', '</span>');
            $this->form_validation->set_rules('data[email]', 'Email', 'trim|required|min_length[3]|max_length[150]|regex_match[/^([a-z0-9_@\.])+$/i]|callback__email');
            if ($this->form_validation->run() == true) {

                $_post = $this->cms_common_string->allow_post($_post, ['email']);
                $user = $this->db->select('username,password,salt')->where('email', $_post['email'])->from('users')->get()->row_array();
                if (isset($user) && !empty($user)) {

                    $dataup['recode'] = $this->cms_common_string->random(69, true);
                    $dataup['code_time_out'] = time() + 3600;
                    $html = "<div class='alert-container' style='background: #DDD; padding: 20px 0; font-family: Helvetica Neue, Helvetica, Arial, sans-serif; color:#464646;font-size: 14px;'>
                            <div class='alert' style='background: #fff; width: 80%; margin: 20px auto;'>
                                <div class='alert-heading' style='background: #0B87C9; color: #fff; padding: 15px 10px; font-size: 20px; font-family: tahoma,arial, sans-serif;'>

                                </div>
                                <div class='alert-body' style='padding: 25px 20px;'>
                                    Phần mềm xin chào,<br /><br />

                                    Bạn vừa yêu cầu lấy lại thông tin tài khoản!<br /><br />

                                    Xin hãy bấm vào liên kết để hoàn tất quá trình!<br /><br />
                                    <div class='link' style='margin: 0 auto; text-align: center;'>
                                     <a href='" . CMS_BASE_URL . 'authentication/reset/?email=' . urlencode($_post['email']) . '&code=' . $dataup['recode'] . "' style='display: inline-block; margin: 0 auto; padding: 10px 90px; background: #0B87C9; color: #fff; text-decoration: none; text-align: center; '>Lấy lại mật khẩu</a>
                                    </div>
                                    <br/><br/>
                                        Hoặc bạn có thể copy liên kết sau vào trình duyệt  " . CMS_BASE_URL . 'authentication/reset/?email=' . base64_encode(urlencode($_post['email'])) . '&code=' . $dataup['recode'] . "
                                    <br/><br/>
                                    Xin cám ơn!
                                </div>
                                <div class='alert-footer' style='padding: 25px 20px; border-top: 1px solid #ddd;' >
                                    Quangna.vn - 128 Nguyễn Trãi P. 17, Q. Gang Thép, Thái Nguyên. ĐT: 1900 2045
                                </div>
                            </div>
                          </div>";
                    $param = array('name' => 'PhongTran', 'from' => 'phongtt7@gmail.com', 'password' => '01257388742', 'to' => $_post['email'], 'subject' => 'Lấy lại thông tin tài khoản - phongtran.info', 'message' => $html);
//                    mail( $_post['email'],'Lấy lại mât khẩu',$html,array(
//                        'From' => 'webmaster@example.com',
//                        'Reply-To' => 'webmaster@example.com',
//                        'X-Mailer' => 'PHP/' . phpversion()
//                    ));
//                    die();
                    if ($this->cms_common->sentMail($param)) {
                        $this->db->where('username', $user['username'])->update('users', $dataup);
                        $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'authentication/alert/?email=' . base64_encode($_post['email']) . '');
                    }

                }


            }
        }

        $data['template'] = 'auth/fg_pass';
        $this->load->view('layout/auth', isset($data) ? $data : null);
    }

    public function _email($email)
    {
        $count = $this->db->where('email', $email)->from('users')->count_all_results();
        if ($count == 0) {
            $this->form_validation->set_message('_email', 'Email Không tồn tại.');//tự tạo câu lệnh xuất riêng vs hàm riêng
            return false;
        }

        return true;
    }

    public function alert()
    {
        $data['seo']['title'] = "Lấy lại mật khẩu - Phần mềm quản lý bán hàng";

        $data['template'] = 'auth/alert';
        $this->load->view('layout/auth', isset($data) ? $data : null);
    }

    /*
     * Reset password
     ****************************************************/
    public function reset()
    {
        if ($this->auth != null) $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
        $data['seo']['title'] = "Lấy lại mật khẩu - Phần mềm quản lý bán hàng";

        $mail = urldecode($this->input->get('email'));
        $code = $this->input->get('code');

        if (isset($mail) && !empty($mail) && isset($mail) && !empty($code)) {
            $user = $this->db->select('username, recode, code_time_out')->where(['email' => $mail, 'recode' => $code])->from('users')->get()->row_array();
            if (!isset($user) || (count($user) == 0) || $user['code_time_out'] <= time()) $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'authentication/n_link');
            if ($this->input->post('reset')) {
                $_post = $this->input->post('data');
                $data['data']['_post'] = $_post;
                $this->form_validation->set_error_delimiters('<li>', '</li>');
                $this->form_validation->set_rules('data[email]', 'email', 'trim|required|min_length[3]|max_length[100]|regex_match[/^([a-z0-9_@\.])+$/i]');
                $this->form_validation->set_rules('data[password]', 'mật khẩu', 'trim|required|min_length[6]');
                if ($this->form_validation->run() == true) {
                    $_post = $this->cms_common_string->allow_post($_post, ['email', 'password']);
                    $_post['salt'] = $this->cms_common_string->random(69, true);//tạo ra một chuỗi ngẫu nhiên
                    $_post['password'] = $this->cms_common_string->password_encode($_post['password'], $_post['salt']);//mã hóa mật khẩu bằng cách nối chuỗi theo thứ tự định sẵn.
                    $_post['updated'] = gmdate("Y:m:d H:i:s", time() + 60);
                    $_post['recode'] = '';
                    $_post['code_time_out'] = '';
                    $this->db->where('username', $user['username'])->update('users', $_post);
                    $this->cms_common_string->cms_jsredirect('Thay đổi tài khoản thành công!', CMS_BASE_URL . 'backend');
                }
            }
        }
        $data['template'] = "auth/reset";
        $this->load->view('layout/auth', isset($data) ? $data : null);

    }

    /*
     * Alert link expired
     ****************************************************/
    public function n_link()
    {
        $data['seo']['title'] = 'Thông báo - Phần mềm quan lý bán hàng';

        $data['template'] = 'auth/n_link';
        $this->load->view('layout/auth', isset($data) ? $data : null);
    }

    public function logout()
    {
        if ($this->auth == null) $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
        CMS_Cookie::delete('user_logged' . CMS_PREFIX);
        $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'backend');
    }
    public function forgot(){
        $email = $_GET['email'];
        $token = $_GET['token'];
        if(empty($email) ||empty($token)){
            $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'authentication');
        }
        $time = time();
        $user = $this->db->from('users')->where('email',$email)->where('token_pass',$token)->get()->row_array();

        if(empty($user)){
            $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'authentication');
        }else{
            if($user['time_pass'] < $time ){
                $this->cms_common_string->cms_redirect(CMS_BASE_URL . 'authentication');
            }else{
                $data['user'] =  $user;
                $data['template'] = "auth/reset_pass";
                $this->load->view('layout/auth', isset($data) ? $data : null);
            }
        }

        if ($this->input->post('resetpass')) {
            $_post = $this->input->post('data');
            $data['data']['_post'] = $_post;
            $this->form_validation->set_error_delimiters('<li>', '</li>');
            $this->form_validation->set_rules('data[email]', 'email', 'trim|required|min_length[3]|max_length[100]|regex_match[/^([a-z0-9_@\.])+$/i]');
            $this->form_validation->set_rules('data[password]', 'mật khẩu', 'trim|required|min_length[8]|=');
            $this->form_validation->set_rules('data[re_password]', 'Nhập lại mật khẩu', 'trim|required|callback__check_repassword[' . $_post['password'] . ']');
            if ($this->form_validation->run() == true) {
                $user = $this->db->where(['email' => $_post['email'], 'token_pass' => $_post['token'],'time_pass >= ' => time()])->from('users')->get()->row_array();
                if(!empty($user)) {
                    $_post = $this->cms_common_string->allow_post($_post, ['email','password']);
                    $_post['salt'] = $this->cms_common_string->random(69, true);//tạo ra một chuỗi ngẫu nhiên
                    $_post['password'] = $this->cms_common_string->password_encode($_post['password'], $_post['salt']);//mã hóa mật khẩu bằng cách nối chuỗi theo thứ tự định sẵn.
                    $_post['updated'] = gmdate("Y:m:d H:i:s", time() + 60);
                    $_post['token_pass'] = '';
                    $_post['time_pass'] = '';
                    $this->db->where('id', $user['id'])->update('users', $_post);
                    $this->cms_common_string->cms_jsredirect('Thay đổi tài khoản thành công!', CMS_BASE_URL . 'backend');
                }
            }

        }
        $data['template'] = "auth/reset_pass";
        $this->load->view('layout/auth', isset($data) ? $data : null);
    }
}