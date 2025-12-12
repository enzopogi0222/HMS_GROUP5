<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="resetPasswordTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="resetPasswordTitle">
                <i class="fas fa-key" style="color:#4f46e5"></i> Reset Password
            </div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeResetPasswordModal()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="resetPasswordForm">
            <input type="hidden" id="rp_user_id" name="user_id">
            <div class="hms-modal-body">
                <div class="form-grid">
                    <div class="full">
                        <label class="form-label">User Information</label>
                        <div style="padding: 1rem; background: #f8fafc; border-radius: 4px; border: 1px solid #e5e7eb;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div><strong>Name:</strong> <span id="rp_user_name">-</span></div>
                                <div><strong>Email:</strong> <span id="rp_user_email">-</span></div>
                                <div><strong>Username:</strong> <span id="rp_user_username">-</span></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="rp_new_password">New Password*</label>
                        <input type="password" id="rp_new_password" name="new_password" class="form-input" required minlength="6" autocomplete="new-password">
                        <small class="form-text" style="color:#6b7280">Minimum 6 characters</small>
                        <small id="rp_err_password" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="rp_confirm_password">Confirm Password*</label>
                        <input type="password" id="rp_confirm_password" name="confirm_password" class="form-input" required minlength="6" autocomplete="new-password">
                        <small id="rp_err_confirm" style="color:#dc2626"></small>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeResetPasswordModal()">Cancel</button>
                <button type="submit" id="resetPasswordBtn" class="btn btn-primary">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

