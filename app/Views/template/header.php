<header class="header">
    <div class="header-content">
        <div class="logo">
            <h1><i class="fas fa-hospital"></i> Hospital Management System</h1>
        </div>
        <div class="user-info">
            <div class="fas fa-user-circle"></div>
            <div>
                <div style="font-weight: 600;">
                    <?= \App\Helpers\UserHelper::getDisplayName() ?>
                </div>
                <div style="font-size: 0.9rem;opacity:0.8">
                    <?= \App\Helpers\UserHelper::getDisplayRole() ?>
                </div>
            </div>
            <a href="<?=site_url('logout')?>" class="logout-btn" onclick="handleLogout()">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</header>