<?php
/**
 *
 * @package booskit/twofactor
 * @license MIT
 *
 */

if (!defined('IN_PHPBB')) {
    exit;
}

if (empty($lang) || !is_array($lang)) {
    $lang = [];
}

$lang = array_merge($lang, [
    // General
    'BOOSKIT_2FA_TITLE'                => 'Two-Factor Authentication',
    'BOOSKIT_2FA_SUBTITLE'             => 'Enhance your account security using Time-based One-Time Passwords (TOTP)',
    'BOOSKIT_2FA_STATUS'               => 'Two-Factor Status',
    'BOOSKIT_2FA_ENABLED'              => 'Enabled &amp; Active',
    'BOOSKIT_2FA_DISABLED'             => 'Disabled',
    'BOOSKIT_2FA_ENFORCED'             => 'Enforced by Group Policy',

    // Verification prompt
    'BOOSKIT_2FA_VERIFY_TITLE'         => 'Two-Factor Authentication Check',
    'BOOSKIT_2FA_VERIFY_HEADER'        => 'Two-Factor Security Verification',
    'BOOSKIT_2FA_VERIFY_EXPLAIN'       => 'Please enter the 6-digit security code from your authenticator app (Google Authenticator, Microsoft Authenticator, Authy, 1Password, etc.) to proceed.',
    'BOOSKIT_2FA_ENTER_CODE'           => '6-Digit Code',
    'BOOSKIT_2FA_VERIFY_BUTTON'        => 'Verify &amp; Continue',
    'BOOSKIT_2FA_INVALID_CODE'         => 'The 6-digit verification code you entered is invalid or has expired. Please try again.',
    'BOOSKIT_2FA_USE_BACKUP'           => 'Can’t access your authenticator app? Use a backup key',
    'BOOSKIT_2FA_USE_TOTP'             => 'Use 6-digit authenticator code instead',
    'BOOSKIT_2FA_ENTER_BACKUP'         => 'Emergency Backup Key',
    'BOOSKIT_2FA_BACKUP_EXPLAIN'       => 'Enter one of your single-use backup keys (formatted like XXXX-XXXX-XXXX).',
    'BOOSKIT_2FA_REMAINING_BACKUP'       => 'Remaining unused backup keys: %d',
    'BOOSKIT_2FA_REMAINING_BACKUP_LABEL' => 'unused backup keys remaining',
    'BOOSKIT_2FA_REMAINING_BACKUP_SHORT' => 'unused backup keys',
    'BOOSKIT_2FA_LOGGED_IN_AS'           => 'Verifying session for',
    'BOOSKIT_2FA_REMEMBER_DEVICE'        => 'Remember this device for 30 days',
    'BOOSKIT_2FA_REMEMBERED_DEVICES'     => 'Remembered Devices (30 Days)',
    'BOOSKIT_2FA_REVOKE_DEVICES'         => 'Revoke all remembered devices',
    'BOOSKIT_2FA_REVOKE_DEVICES_SUCCESS' => 'All remembered trusted devices have been revoked.',

    // Setup Wizard
    'BOOSKIT_2FA_SETUP_TITLE'          => 'Set Up Two-Factor Authentication',
    'BOOSKIT_2FA_SETUP_HEADER'         => 'Protect Your Account with 2FA',
    'BOOSKIT_2FA_SETUP_ENFORCED_NOTICE'=> 'Your user group requires Two-Factor Authentication to access this forum. Please complete the setup below to continue.',
    'BOOSKIT_2FA_STEP_1'               => 'Step 1: Scan QR Code',
    'BOOSKIT_2FA_STEP_1_EXPLAIN'       => 'Scan this QR code with your authenticator app (such as Google Authenticator, Microsoft Authenticator, Authy, Bitwarden, 1Password, Apple Passwords).',
    'BOOSKIT_2FA_MANUAL_ENTRY'         => 'Can’t scan the QR code? Enter this secret key manually in your app:',
    'BOOSKIT_2FA_COPY_KEY'             => 'Copy Secret Key',
    'BOOSKIT_2FA_COPIED'               => 'Copied!',
    'BOOSKIT_2FA_STEP_2'               => 'Step 2: Verify &amp; Activate',
    'BOOSKIT_2FA_STEP_2_EXPLAIN'       => 'Enter the 6-digit code shown in your authenticator app to confirm setup.',
    'BOOSKIT_2FA_ACTIVATE_BUTTON'      => 'Activate Two-Factor Authentication',

    // Backup Keys
    'BOOSKIT_2FA_BACKUP_KEYS_TITLE'    => 'Save Your Emergency Backup Keys',
    'BOOSKIT_2FA_BACKUP_KEYS_HEADER'   => 'Emergency Backup Keys',
    'BOOSKIT_2FA_BACKUP_KEYS_WARNING'  => 'These backup keys allow you to sign in if you lose access to your authenticator device. Each key can only be used once. Store them in a secure place (password manager, safe deposit, or print them out).',
    'BOOSKIT_2FA_COPY_ALL_KEYS'        => 'Copy All Keys',
    'BOOSKIT_2FA_DOWNLOAD_KEYS'        => 'Download Keys (.txt)',
    'BOOSKIT_2FA_PRINT_KEYS'           => 'Print Keys',
    'BOOSKIT_2FA_CONTINUE_TO_BOARD'    => 'I Have Saved These Keys &mdash; Continue',
    'BOOSKIT_2FA_KEYS_RESET_BY_ADMIN'  => 'An administrator has reset your backup keys. Please save your new emergency backup keys below.',

    // Suggestion Prompt
    'BOOSKIT_2FA_SUGGESTION_TITLE'     => 'Enhance Your Account Security',
    'BOOSKIT_2FA_SUGGESTION_TEXT'      => 'Two-Factor Authentication adds an extra layer of protection against unauthorized access to your account.',
    'BOOSKIT_2FA_SUGGESTION_SETUP_NOW' => 'Set Up 2FA Now',
    'BOOSKIT_2FA_SUGGESTION_REMIND'    => 'Remind me in 30 days',

    // UCP Management
    'BOOSKIT_2FA_STATUS_ACTIVE'        => 'Active &amp; Protected',
    'BOOSKIT_2FA_ENABLED_ON'           => 'Enabled on:',
    'BOOSKIT_2FA_LAST_LOGIN'           => 'Last successful 2FA login:',
    'BOOSKIT_2FA_LAST_IP'              => 'Last 2FA IP address:',
    'BOOSKIT_2FA_LAST_METHOD'          => 'Last verification method:',
    'BOOSKIT_2FA_REGEN_BACKUP'         => 'Regenerate Backup Keys',
    'BOOSKIT_2FA_REGEN_BACKUP_EXPLAIN' => 'Generating new backup keys will invalidate all previous backup keys.',
    'BOOSKIT_2FA_DISABLE_2FA'          => 'Disable Two-Factor Authentication',
    'BOOSKIT_2FA_DISABLE_EXPLAIN'      => 'Disabling 2FA will remove two-factor protection from your account.',
    'BOOSKIT_2FA_CONFIRM_PASSWORD'     => 'Current Password or 6-digit 2FA Code:',
    'BOOSKIT_2FA_CONFIRM_EXPLAIN'      => 'To confirm this action, please enter your forum password or a current 6-digit authenticator code.',
    'BOOSKIT_2FA_SETUP_SUCCESS'        => 'Two-Factor Authentication has been successfully activated on your account!',
    'BOOSKIT_2FA_DISABLED_SUCCESS'     => 'Two-Factor Authentication has been disabled for your account.',
    'BOOSKIT_2FA_REGEN_BACKUP_SUCCESS' => 'New emergency backup keys have been generated.',
    'BOOSKIT_2FA_CONFIRM_FAILED'       => 'The password or authenticator code you entered was incorrect.',

    // Audit Logs & ACP User Overview
    'LOG_BOOSKIT_2FA_CONFIG_UPDATED'          => '<strong>Updated Two-Factor Authentication configuration settings</strong>',
    'LOG_BOOSKIT_2FA_AUTH_SUCCESS'            => '<strong>Two-Factor Authentication succeeded</strong> for user &raquo; %1$s (Access: %2$s, Method: %3$s)',
    'LOG_BOOSKIT_2FA_AUTH_FAILED'             => '<strong>Two-Factor Authentication failed (invalid code)</strong> for user &raquo; %1$s (Access: %2$s, Method: %3$s)',
    'LOG_BOOSKIT_2FA_DEVICE_REMEMBERED'       => '<strong>Remembered trusted device (IP: %2$s)</strong> for 30 days for user &raquo; %1$s',
    'LOG_BOOSKIT_2FA_SETUP_ACTIVATED'         => '<strong>Activated Two-Factor Authentication (TOTP)</strong> on user account &raquo; %s',
    'LOG_BOOSKIT_2FA_SETUP_FAILED'            => '<strong>Failed Two-Factor Authentication setup attempt</strong> for user &raquo; %s (Invalid confirmation code)',
    'LOG_BOOSKIT_2FA_USER_DISABLED'           => '<strong>Disabled Two-Factor Authentication</strong> on user account &raquo; %s',
    'LOG_BOOSKIT_2FA_BACKUP_REGENERATED'      => '<strong>Regenerated emergency backup keys</strong> for user &raquo; %s',
    'LOG_BOOSKIT_2FA_BACKUP_ACKNOWLEDGED'     => '<strong>Acknowledged &amp; saved new emergency backup keys</strong> for user &raquo; %s',
    'LOG_BOOSKIT_2FA_DEVICES_REVOKED'         => '<strong>Revoked all remembered trusted devices</strong> for user &raquo; %s',
    'LOG_BOOSKIT_2FA_SUGGESTION_DISMISSED'    => '<strong>Dismissed 2FA setup suggestion prompt</strong> for 30 days for user &raquo; %s',
    'LOG_BOOSKIT_2FA_ADMIN_REMOVED'           => '<strong>Administrator disabled Two-Factor Authentication</strong> for user &raquo; %s',
    'LOG_BOOSKIT_2FA_ADMIN_RESET_BACKUP'      => '<strong>Administrator reset emergency backup keys</strong> for user &raquo; %s',
    'LOG_BOOSKIT_2FA_ADMIN_RESET_REMEMBER'    => '<strong>Administrator revoked remembered trusted devices</strong> for user &raquo; %s',

    'BOOSKIT_2FA_METHOD_TOTP'                 => 'Authenticator App (TOTP)',
    'BOOSKIT_2FA_METHOD_BACKUP'               => 'Emergency Backup Key',

    'BOOSKIT_2FA_ADMIN_USER_HEADING'          => 'Two-Factor Authentication Status',
    'BOOSKIT_2FA_ADMIN_DISABLE_BUTTON'        => 'Disable 2FA for this user',
    'BOOSKIT_2FA_ADMIN_RESET_BACKUP_BUTTON'   => 'Reset Backup Keys (prompt user on next login)',
    'BOOSKIT_2FA_ADMIN_RESET_REMEMBER_BUTTON' => 'Reset Remember Memory (clear trusted IPs)',
    'BOOSKIT_2FA_ADMIN_CONFIRM_REMOVE'        => 'Are you sure you want to disable and remove Two-Factor Authentication for this user? This will remove their secret and backup keys.',
    'BOOSKIT_2FA_ADMIN_CONFIRM_RESET_BACKUP'  => 'Are you sure you want to reset backup keys for this user? New backup keys will be generated and the user will be presented with them on their next login.',
    'BOOSKIT_2FA_ADMIN_CONFIRM_RESET_REMEMBER'=> 'Are you sure you want to reset and clear all remembered trusted IPs for this user? They will be required to enter 2FA again on their next login or module access.',
    'BOOSKIT_2FA_ADMIN_REMOVED_SUCCESS'       => 'Two-Factor Authentication has been disabled and cleared for this user.',
    'BOOSKIT_2FA_ADMIN_RESET_BACKUP_SUCCESS'  => 'Backup keys have been reset. The user will be shown their new backup keys upon next login.',
    'BOOSKIT_2FA_ADMIN_RESET_REMEMBER_SUCCESS'=> 'All remembered trusted IPs for this user have been cleared.',
    'BOOSKIT_2FA_TRUSTED_IPS_ACTIVE'          => 'trusted IP(s) currently remembered',

    // ACP Manage Users Module Keys
    'ACP_USER_TFA_MANAGE'        => 'Two-Factor Authentication',
    'ACP_USER_TWOFACTOR'         => 'Two-Factor Authentication',
    'ACP_USER_2FA'               => 'Two-Factor Authentication',
    'ACP_USER_MANAGE_2FA'        => 'Two-Factor Authentication',
    'ACP_USER_BOOSKIT_TWOFACTOR' => 'Two-Factor Authentication',
]);
