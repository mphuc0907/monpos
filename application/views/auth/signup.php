<?php

?>
<div class="login-container col-md-4 col-md-offset-4" id="login-form">
    <div class="login-frame clearfix">
        <center><h3 class="heading col-md-10 col-md-offset-1 padd-0"><i class="fa fa-lock"></i>Đăng ký tài khoản</h3></center>
        <ul class="validation-summary-errors col-md-10 col-md-offset-1">
            <?php echo validation_errors(); ?>
        </ul>
        <div class="col-md-10 col-md-offset-1">
            <form class="form-horizontal login-form frm-sm" method="post" action="">
                <div class="form-group input-icon">
                    <label for="input_display_name" class="sr-only control-label">Tên Công ty</label>
                    <input type="text" name="data[display_name]"
                           value="<?php echo cms_common_input(isset($_post) ? $_post : [], 'display_name'); ?>"
                           class="form-control" id="input_display_name" placeholder="Tên khách hàng">
                    <i class="fa fa-user icon-right"></i>
                </div>
                <div class="form-group input-icon">
                    <label for="inputUsername" class="sr-only control-label">Mã Tài khoản của công ty</label>
                    <input type="text" name="data[username]"
                           value="<?php echo cms_common_input(isset($_post) ? $_post : [], 'username'); ?>"
                           class="form-control" id="inputUsername" placeholder="Mã Đăng nhập">
                    <i class="fa fa-user icon-right"></i>
                </div>
                <div class="form-group input-icon">
                    <label for="inputEmail3" class="sr-only control-label">Email</label>
                    <input type="email" name="data[email]"
                           value="<?php echo cms_common_input(isset($_post) ? $_post : [], 'email'); ?>"
                           class="form-control" id="inputEmail3" placeholder="Email của bạn">
                    <i class="fa fa-envelope icon-right"></i>
                </div>
                <div class="form-group input-icon">
                    <label for="inputPassword3" class="sr-only control-label">Password</label>
                    <input type="password" name="data[password]"
                           value="<?php echo cms_common_input(isset($_post) ? $_post : [], 'password'); ?>"
                           class="form-control" id="inputPassword3" placeholder="Mật khẩu">
                    <i class="fa fa-lock icon-right"></i>
<!--                    <span>user: admin - pass: 12345678</span>-->
                </div>
                <div class="form-group input-icon">
                    <label for="inputre_password" class="sr-only control-label">Nhập lại Password</label>
                    <input type="password" name="data[re_password]"
                           value="<?php echo cms_common_input(isset($_post) ? $_post : [], 're_password'); ?>"
                           class="form-control" id="inputre_password" placeholder="Nhập lại Mật khẩu">
                    <i class="fa fa-lock icon-right"></i>
<!--                    <span>user: admin - pass: 12345678</span>-->
                </div>

                <div class="form-group">

                    <input type="submit" name="signup" value="Đăng Ký" class="btn btn-primary btn-sm"/>
                </div>
            </form>
        </div>
    </div>

    <div class="link-action text-center">

        <div class="col-sm-6 col-xs-12">
            <a href="authentication" style="display:inline-block; margin-top: 5px;" class="register">Đăng
                nhập</a>
        </div>

    </div>
</div>
<script src="public/templates/js/main.js"></script>
<script>
    <?php

    if(!empty($error)) : ?>

    $(document).ready(function () {
        $('.validation-summary-errors').append('<li><?= $error ?> </li>');
    })

    <?php $error = '';  ?>
    <?php endif; ?>
</script>