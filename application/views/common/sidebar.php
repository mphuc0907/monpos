<div class="sidebar sidebar-fixed hidden-xs hidden-sm hidden-md" id="sidebar">
    <ul class="nav nav-pills nav-list nav-stacked">
        <li id="dashboard"><a href="pos" style="margin: 10px 30px;color: #1E1E1E;background-color: #ffb752!important;text-align: center">Pos bán hàng</a></li>
        <?php if (in_array(1, $user['group_permission'])) : ?>
            <li id="dashboard"><a href="dashboard"><i class="icons iconcs-list"></i>Tổng quan</a></li>
        <?php endif; ?>
        <?php if (in_array(2, $user['group_permission'])) : ?>
            <li id="orders"><a href="orders"><i class="icons iconcs-shopping-cart"></i>Đơn hàng</a></li>
        <?php endif; ?>
        <?php if (in_array(3, $user['group_permission'])) : ?>
            <li id="product"><a href="product"><i class="icons iconcs-barcode"></i>Sản phẩm</a></li>
        <?php endif; ?>
        <?php if (in_array(4, $user['group_permission'])) : ?>
            <li id="customer"><a href="customer"><i class="icons iconcs-users"></i>Khách hàng - NCC</a></li>
        <?php endif; ?>
        <?php if (in_array(5, $user['group_permission'])) : ?>
            <li id="import"><a href="import"><i class="icons iconcs-truck"></i>Nhập kho</a></li>
        <?php endif; ?>
        <?php if (in_array(6, $user['group_permission'])) : ?>
            <li id="inventory"><a href="inventory"><i class="icons iconcs-list-alt"></i>Tồn kho</a></li>
        <?php endif; ?>
        <?php if (in_array(7, $user['group_permission'])) : ?>
            <li id="revenue"><a href="revenue"><i class="icons iconcs-signal"></i>Doanh số</a></li>
        <?php endif; ?>

        <?php if (in_array(9, $user['group_permission'])) : ?>
            <li id="profit"><a href="profit"><i class="icons iconcs-usd"></i>Lợi nhuận</a></li>
        <?php endif; ?>
        <?php if (in_array(8, $user['group_permission'])) : ?>
            <!--            <li><a href="#"> class="icons iconcs-" i<i class="fa fa-file-text"></i>Thu chi</a></li>-->
            <li id="phieuchi"><a href="phieuchi"><i class="icons iconcs-file-text"></i>Phiếu chi</a></li>
            <li id="phieuthu"><a href="phieuthu"><i class="icons iconcs-file-text_2"></i>Phiếu thu</a></li>
        <?php endif; ?>

        <!--            <li id="phieuthu"><a href="qlch"><i class="fa fa-file-text"></i>Quản lý cửa hàng</a></li>-->

        <?php if (in_array(10, $user['group_permission'])) : ?>
            <li id="config"><a href="config"><i class="icons iconcs-cogs"></i>Thiết lập</a></li>
        <?php endif; ?>
    </ul>
</div>