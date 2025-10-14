<?php

namespace App\Enums;

enum AuditAction: string
{
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case FAILED_LOGIN = 'failed_login';
    case CREATE_USER = 'create_user';
    case UPDATE_USER = 'update_user';
    case DELETE_USER = 'delete_user';
    case ACTIVATE_USER = 'activate_user';
    case DEACTIVATE_USER = 'deactivate_user';
    case CHANGE_PASSWORD = 'change_password';
    case VERIFY_TOKEN = 'verify_token';
    case REFRESH_TOKEN = 'refresh_token';
}