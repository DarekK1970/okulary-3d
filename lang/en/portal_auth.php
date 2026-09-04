<?php

return [
    'common' => [
        'account' => 'User account',
        'my_account' => 'My account',
        'security' => 'Account security',
    ],

    'fields' => [
        'name' => 'Name / username',
        'email' => 'Email address',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'current_password' => 'Current password',
        'new_password' => 'New password',
    ],

    'login' => [
        'title' => 'Sign in',
        'description' => 'Sign in to use your account, community gallery and shop features.',
        'forgot' => 'Forgot your password?',
        'remember' => 'Remember me on this device',
        'submit' => 'Sign in',
        'no_account' => 'Do not have an account yet?',
        'register' => 'Create account',
    ],

    'register' => [
        'title' => 'Create account',
        'description' => 'One account for the 3D gallery, tools, orders and community features.',
        'password_help' => 'Password must be at least 8 characters long and contain letters and numbers.',
        'terms' => 'I accept the <a href="#">terms</a> and <a href="#">privacy policy</a>.',
        'submit' => 'Create account',
        'have_account' => 'Already have an account?',
        'login' => 'Sign in',
    ],

    'forgot' => [
        'title' => 'Recover access',
        'description' => 'Enter the email address used during registration. We will send you a link to set a new password.',
        'submit' => 'Send reset link',
        'back' => 'Back to sign in',
    ],

    'reset' => [
        'title' => 'Set a new password',
        'description' => 'Enter a new password for your account.',
        'submit' => 'Save new password',
    ],

    'account' => [
        'title' => 'My account',
        'welcome' => 'Welcome, :name. Here you can manage the basic details of your account.',
        'logout' => 'Sign out',
        'admin_panel' => 'Administration panel',
        'profile_title' => 'Profile details',
        'password_title' => 'Change password',
        'password_description' => 'For security, enter your current password as well.',
        'role' => 'Role',
        'save_profile' => 'Save details',
        'save_password' => 'Change password',
    ],

    'roles' => [
        'user' => 'User',
        'editor' => 'Editor',
        'admin' => 'Administrator',
        'super_admin' => 'Super Administrator',
    ],

    'wallet' => [
        'title' => 'Your TOKEN_LENS wallet',
        'description' => 'Use tokens for AI generation and marketplace services.',
        'empty' => 'There are no wallet transactions yet.',
        'insufficient' => 'You do not have enough TOKEN_LENS.',
        'types' => ['grant' => 'Token grant', 'admin_adjustment' => 'Balance adjustment', 'ai_video' => 'AI video generation', 'marketplace_order' => 'Marketplace order'],
        'header_balance' => 'Your TOKEN_LENS: :count',
        'valid_until' => 'valid until :date',
        'no_expiry' => 'no expiry date',
    ],

    'messages' => [
        'registered' => 'Your account has been created.',
        'logged_out' => 'You have been signed out.',
        'profile_updated' => 'Profile details have been saved.',
        'password_updated' => 'Your password has been changed.',
        'account_suspended' => 'This account has been suspended. Contact the administrator.',
    ],
];
