
<div class="login-container" id="login-form">
    <div class=" app-views-layout-auth.php">
        <center style="    font-size: 40px;
    color: #fff;"><img src="public/templates/images/Logo-monpos.png" alt=""></center>
    </div>
    <div class="login-frame clearfix">
        <center> <h3 class="heading col-md-10 col-md-offset-1 padd-0"><i class="fa fa-refresh"></i> Khôi phục mật khẩu!</h3></center>
        <ul class="validation-summary-errors col-md-10 col-md-offset-1">
            <?php echo validation_errors(); ?>
        </ul>
        <div class="form-sigin">
            <form class="form-horizontal login-form frm-sm" method="post" action="">
                <input type="hidden" name="data[token]" value="<?= $user['token_pass'] ?>">
                <input type="hidden" name="data[email]" value="<?= $user['email'] ?>">
                <div class="form-group input-icon">
                    <label for="inputPassword3" class="sr-only control-label">Mật khẩu mới </label>
                    <input type="password" name="data[password]"
                           value="<?php echo cms_common_input(isset($_post) ? $_post : [], 'password'); ?>"
                           class="form-control" id="inputPassword3" placeholder="Mật khẩu mới">
                    <i class="fa fa-lock icon-right"></i>
                    <!--                    <span>user: admin - pass: 12345678</span>-->
                </div>
                <div class="form-group input-icon">
                    <label for="inputPassword4" class="sr-only control-label">Xác nhận mật khẩu</label>
                    <input type="password" name="data[re_password]"
                           value="<?php echo cms_common_input(isset($_post) ? $_post : [], 'password'); ?>"
                           class="form-control" id="inputPassword4" placeholder="Xác nhận mật khẩu">
                    <i class="fa fa-lock icon-right"></i>
                    <!--                    <span>user: admin - pass: 12345678</span>-->
                </div>
                <div class="form-group">

                    <input type="submit" name="resetpass" value="Khôi phục" class="btn btn-primary btn-sm"/>
                </div>
            </form>
        </div>
    </div>

    <!--    <div class="link-action text-center">-->
    <!--        <div class="col-sm-6 col-xs-12">-->
    <!--            <a href="authentication/fg_password" style="display:inline-block; margin-top: 5px;" class="fg-passw">Quên-->
    <!--                mật khẩu</a>-->
    <!--        </div>-->
    <!--        <div class="col-sm-6 col-xs-12">-->
    <!--            <a href="authentication/register" style="display:inline-block; margin-top: 5px;" class="register">Đăng-->
    <!--                kí</a>-->
    <!--        </div>-->
    <!---->
    <!--    </div>-->
</div>