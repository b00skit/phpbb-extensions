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
    'ACP_BOOSKIT_TWOFACTOR_TITLE'       => 'Two-Factor Authentication',
    'ACP_BOOSKIT_TWOFACTOR_SETTINGS'    => '2FA Configuration',
    'ACP_BOOSKIT_TWOFACTOR_EXPLAIN'     => 'Configure group policies for Two-Factor Authentication (TOTP), prompt suggestions, enforcement rules, and access control.',

    // Settings fields
    'ACP_2FA_ENABLE'                    => 'Enable Two-Factor Authentication',
    'ACP_2FA_ENABLE_EXPLAIN'            => 'Master switch to enable or disable the 2FA system across the board.',
    'ACP_2FA_ISSUER'                    => 'Authenticator App Issuer Name',
    'ACP_2FA_ISSUER_EXPLAIN'            => 'Name displayed in Authenticator apps (e.g. Google Authenticator). Defaults to your Board Name if left empty.',
    'ACP_2FA_COLOR'                     => 'Authenticator Primary Color',
    'ACP_2FA_COLOR_EXPLAIN'             => 'Primary theme accent color for 2FA buttons, icons, and focus highlights (default: #2563eb).',
    'ACP_2FA_LOGO_URL'                  => 'Custom Logo URL',
    'ACP_2FA_LOGO_URL_EXPLAIN'          => 'Optional URL/link to a custom logo image. When provided, replaces the default shield icon in 2FA prompts and setup wizards.',
    'ACP_2FA_SHARED_SESSION'            => 'Shared Session Authentication',
    'ACP_2FA_SHARED_SESSION_EXPLAIN'    => 'When enabled, completing 2FA verification for ACP or any restricted control panel (UCP & MCP) will automatically grant 2FA authorization across all other control panels for that session. Standard initial login does <strong>not</strong> grant shared panel access.',

    'ACP_2FA_GROUPS_SUGGEST'            => 'Suggestion Prompt Groups',
    'ACP_2FA_GROUPS_SUGGEST_EXPLAIN'    => 'Members of these groups who haven’t enabled 2FA will receive a friendly suggestion banner/prompt that they can skip for 30 days.',

    'ACP_2FA_GROUPS_ENFORCE'            => 'Enforced 2FA Groups',
    'ACP_2FA_GROUPS_ENFORCE_EXPLAIN'    => 'Members of these groups are strictly required to set up 2FA. They will not be able to perform actions on the board until 2FA setup is complete.',

    'ACP_2FA_GROUPS_REMEMBER'           => 'Disallow "Remember Trusted Devices" (Exclusion List)',
    'ACP_2FA_GROUPS_REMEMBER_EXPLAIN'   => 'Select groups that are <strong>NOT ALLOWED</strong> to use the "Remember this device for 30 days" feature. If no groups are checked, all users who have enabled 2FA will be allowed to remember their devices.',

    'ACP_2FA_GROUPS_UCP'                => 'Require 2FA for UCP Access',
    'ACP_2FA_GROUPS_UCP_EXPLAIN'        => 'Select groups that require 2FA verification before accessing User Control Panel (UCP).',

    'ACP_2FA_GROUPS_MCP'                => 'Require 2FA for MCP Access',
    'ACP_2FA_GROUPS_MCP_EXPLAIN'        => 'Select groups that require 2FA verification before accessing Moderator Control Panel (MCP).',

    'ACP_2FA_GROUPS_ACP'                => 'Require 2FA for ACP Access',
    'ACP_2FA_GROUPS_ACP_EXPLAIN'        => 'Select groups that require 2FA verification before accessing Administration Control Panel (ACP).',

    'ACP_2FA_GROUPS_OAUTH'              => 'GTA:W OAuth 2FA Groups',
    'ACP_2FA_GROUPS_OAUTH_EXPLAIN'      => 'Select groups that require 2FA verification when logging in via GTA:W OAuth.',
    'ACP_2FA_OAUTH_NOT_INSTALLED'       => 'GTA:W OAuth extension is not currently active.',

    // ACP Manage Users Dropdown & Overview Keys
    'ACP_USER_TFA_MANAGE'               => 'Two-Factor Authentication',
    'ACP_USER_TWOFACTOR'                => 'Two-Factor Authentication',
    'ACP_USER_2FA'                      => 'Two-Factor Authentication',
    'ACP_USER_MANAGE_2FA'               => 'Two-Factor Authentication',
    'ACP_USER_BOOSKIT_TWOFACTOR'        => 'Two-Factor Authentication',
]);
