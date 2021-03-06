/*
 * Copyright (C) 2009 Automattic Inc.
 *
 * Based on nginx's basic auth module by Igor Sysoev and
 * nginx PAM auth module by  Sergio Talens-Oliag
 *
 */

#include <ngx_config.h>
#include <ngx_core.h>
#include <ngx_http.h>
#include <ngx_md5.h>
#include <mysql.h>

#include "crypt_private.h"

/* Module context data */
typedef struct {
    ngx_str_t  passwd;
} ngx_http_auth_mysql_ctx_t;

/* userinfo */
typedef struct {
    ngx_str_t  username;
    ngx_str_t  password;
} ngx_auth_mysql_userinfo;

/* Module configuration struct */
typedef struct {
    ngx_str_t realm;
    ngx_str_t host;
    ngx_uint_t port;
    ngx_str_t user;
    ngx_str_t password;
    ngx_str_t database;
    ngx_str_t table;
    ngx_str_t user_column;
    ngx_str_t password_column;
    ngx_str_t encryption_type_str;
    ngx_uint_t encryption_type;
    ngx_str_t allowed_users;
    ngx_str_t allowed_groups;
    ngx_str_t group_table;    
    ngx_str_t group_column;    
    ngx_str_t group_conditions;
    ngx_str_t conditions;
} ngx_http_auth_mysql_loc_conf_t;

/* Encryption types */
typedef struct {
    ngx_str_t id;
    ngx_uint_t (*checker)(ngx_http_request_t *r, ngx_str_t sent_password, ngx_str_t actual_password);
} ngx_http_auth_mysql_enctype_t;

/* Module handler */
static ngx_int_t ngx_http_auth_mysql_handler(ngx_http_request_t *r);

/* Function that authenticates the user via MySQL */
static ngx_int_t ngx_http_auth_mysql_authenticate(ngx_http_request_t *r,
    ngx_http_auth_mysql_ctx_t *ctx, ngx_str_t *passwd, void *conf);

static ngx_uint_t ngx_http_auth_mysql_check_plain(ngx_http_request_t *r, ngx_str_t sent_password, ngx_str_t actual_password);

static ngx_uint_t ngx_http_auth_mysql_check_md5(ngx_http_request_t *r, ngx_str_t sent_password, ngx_str_t actual_password);

static ngx_uint_t ngx_http_auth_mysql_check_phpass(ngx_http_request_t *r, ngx_str_t sent_password, ngx_str_t actual_password);

static ngx_int_t ngx_http_auth_mysql_set_realm(ngx_http_request_t *r,
    ngx_str_t *realm);

static void *ngx_http_auth_mysql_create_loc_conf(ngx_conf_t *cf);

static char *ngx_http_auth_mysql_merge_loc_conf(ngx_conf_t *cf,
    void *parent, void *child);

static ngx_int_t ngx_http_auth_mysql_init(ngx_conf_t *cf);

static u_char *ngx_http_auth_mysql_uchar(ngx_pool_t *pool, ngx_str_t *str);

static char *ngx_http_auth_mysql(ngx_conf_t *cf, void *post, void *data);

static u_char * ngx_http_auth_mysql_append2(ngx_pool_t *pool, u_char *base, u_char *append, u_char *append2);
static u_char * ngx_http_auth_mysql_append3(ngx_pool_t *pool, u_char *base, u_char *append, u_char *append2, u_char *append3);

/* function for shopex hash */

static ngx_int_t ngx_http_auth_mysql_shopex_hash(ngx_http_request_t  *r, u_char *username,u_char *salt, u_char *password, u_char *ret_hash);
static ngx_int_t ngx_http_auth_mysql_shopex_md5(ngx_pool_t *pool, u_char *plain_text, u_char  *ret_hash);

/* end of shopex */

static ngx_int_t  ngx_http_key_index;
static ngx_str_t  ngx_http_key_value = ngx_string("auth_mysql_condition_var");

static ngx_conf_post_handler_pt  ngx_http_auth_mysql_p = ngx_http_auth_mysql;

static ngx_http_auth_mysql_enctype_t ngx_http_auth_mysql_enctypes[] = {
    {
        ngx_string("none"),
        ngx_http_auth_mysql_check_plain
    },
    {
        ngx_string("md5"),
        ngx_http_auth_mysql_check_md5
    },
    {
        ngx_string("phpass"),
        ngx_http_auth_mysql_check_phpass
    }
};

static ngx_command_t ngx_http_auth_mysql_commands[] = {
    { ngx_string("auth_mysql_realm"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, realm),
    &ngx_http_auth_mysql_p },
        
    { ngx_string("auth_mysql_host"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, host),
    NULL },
    
    { ngx_string("auth_mysql_port"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_num_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, port),
    NULL },

    { ngx_string("auth_mysql_database"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, database),
    NULL },
    
    { ngx_string("auth_mysql_password"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, password),
    NULL },

    { ngx_string("auth_mysql_table"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, table),
    NULL },
    
    { ngx_string("auth_mysql_user"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, user),
    NULL },    
    
    { ngx_string("auth_mysql_password"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, password),
    NULL },    
    
    { ngx_string("auth_mysql_user_column"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, user_column),
    NULL },    
    
    { ngx_string("auth_mysql_password_column"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, password_column),
    NULL },
    
    { ngx_string("auth_mysql_encryption_type"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, encryption_type_str),
    NULL },
    
    { ngx_string("auth_mysql_allowed_users"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, allowed_users),
    NULL },
    
    { ngx_string("auth_mysql_allowed_groups"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, allowed_groups),
    NULL },
    
    { ngx_string("auth_mysql_group_table"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, group_table),
    NULL },
    
    { ngx_string("auth_mysql_group_column"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, group_column),
    NULL },

    { ngx_string("auth_mysql_group_conditions"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, group_conditions),
    NULL },

    { ngx_string("auth_mysql_conditions"),
    NGX_HTTP_MAIN_CONF|NGX_HTTP_SRV_CONF|NGX_HTTP_LOC_CONF|NGX_HTTP_LMT_CONF|NGX_CONF_TAKE1,
    ngx_conf_set_str_slot,
    NGX_HTTP_LOC_CONF_OFFSET,
    offsetof(ngx_http_auth_mysql_loc_conf_t, conditions),
    NULL }
};


static ngx_http_module_t  ngx_http_auth_mysql_module_ctx = {
    NULL,                                  /* preconfiguration */
    ngx_http_auth_mysql_init,                /* postconfiguration */

    NULL,                                  /* create main configuration */
    NULL,                                  /* init main configuration */

    NULL,                                  /* create server configuration */
    NULL,                                  /* merge server configuration */

    ngx_http_auth_mysql_create_loc_conf,     /* create location configuration */
    ngx_http_auth_mysql_merge_loc_conf       /* merge location configuration */
};


ngx_module_t  ngx_http_auth_mysql_module = {
    NGX_MODULE_V1,
    &ngx_http_auth_mysql_module_ctx,         /* module context */
    ngx_http_auth_mysql_commands,            /* module directives */
    NGX_HTTP_MODULE,                       /* module type */
    NULL,                                  /* init master */
    NULL,                                  /* init module */
    NULL,                                  /* init process */
    NULL,                                  /* init thread */
    NULL,                                  /* exit thread */
    NULL,                                  /* exit process */
    NULL,                                  /* exit master */
    NGX_MODULE_V1_PADDING
};

static ngx_int_t
ngx_http_auth_mysql_handler(ngx_http_request_t *r)
{
    ngx_int_t  rc;
    ngx_http_auth_mysql_ctx_t  *ctx;
    ngx_http_auth_mysql_loc_conf_t  *alcf;

    alcf = ngx_http_get_module_loc_conf(r, ngx_http_auth_mysql_module);

    if (alcf->realm.len == 0) {
        return NGX_DECLINED;
    }

    ctx = ngx_http_get_module_ctx(r, ngx_http_auth_mysql_module);

    if (ctx) {
        return ngx_http_auth_mysql_authenticate(r, ctx, &ctx->passwd, alcf);
    }

    /* Decode http auth user and passwd, leaving values on the request */
    rc = ngx_http_auth_basic_user(r);

    if (rc == NGX_DECLINED) {
        return ngx_http_auth_mysql_set_realm(r, &alcf->realm);
    }

    if (rc == NGX_ERROR) {
        return NGX_HTTP_INTERNAL_SERVER_ERROR;
    }

    /* Check user & password using MySQL */
    return ngx_http_auth_mysql_authenticate(r, ctx, &ctx->passwd, alcf);
}

static ngx_int_t
ngx_http_auth_mysql_authenticate(ngx_http_request_t *r,
    ngx_http_auth_mysql_ctx_t *ctx, ngx_str_t *passwd, void *conf)
{
    ngx_http_auth_mysql_loc_conf_t  *alcf = conf;

    ngx_auth_mysql_userinfo  uinfo;

    size_t   len;
    ngx_int_t auth_res;
    ngx_int_t found_in_allowed = 0;
    u_char  *uname_buf, *p, *next_username;
    ngx_str_t actual_password;

    u_char *query_buf;
    u_char *table;
    u_char *user_column;    
    u_char *password_column;
    u_char *conditions;    
    u_char *esc_user;

    MYSQL *conn, *mysql_result;
    MYSQL_RES *query_result;

    /**
     * Get username and password, note that r->headers_in.user contains the
     * string 'user:pass', so we need to copy the username
     **/
    for (len = 0; len < r->headers_in.user.len; len++) {
    if (r->headers_in.user.data[len] == ':') {
            break;
    }
    }
    uname_buf = ngx_palloc(r->pool, len+1);
    if (uname_buf == NULL) {
        return NGX_HTTP_INTERNAL_SERVER_ERROR;
    }
    p = ngx_cpymem(uname_buf, r->headers_in.user.data , len);
    *p ='\0';

    uinfo.username.data = uname_buf;
    uinfo.username.len  = len;
    
    uinfo.password.data = r->headers_in.passwd.data;
    uinfo.password.len  = r->headers_in.passwd.len;

    /* Check if the user is among allowed users */
    if (ngx_strcmp(alcf->allowed_users.data, "") != 0) {
        found_in_allowed = 0;        
        char* allowed_users = (char*)ngx_http_auth_mysql_uchar(r->pool, &alcf->allowed_users);
        while ((next_username = (u_char*)strsep(&allowed_users, " \t")) != NULL) {
            if (ngx_strcmp(next_username, uinfo.username.data) == 0) {
                found_in_allowed = 1;
                break;
            }
        }
    }

    conn = mysql_init(NULL);
    if (conn == NULL) {
        ngx_log_error(NGX_LOG_ALERT, r->connection->log, 0,
              "auth_mysql: Could not initialize MySQL connection");
        return NGX_HTTP_INTERNAL_SERVER_ERROR;
    }
    
    mysql_result = mysql_real_connect(conn, (char*)alcf->host.data, (char*)alcf->user.data, (char*)alcf->password.data,
            (char*)alcf->database.data, alcf->port, NULL, 0);            
    if (mysql_result == NULL) {
        ngx_log_error(NGX_LOG_ALERT, r->connection->log, 0,
            "auth_mysql: Could not connect to MySQL server: %s", mysql_error(conn));
        mysql_close(conn);
        return NGX_HTTP_INTERNAL_SERVER_ERROR;
    }
    
    user_column = ngx_http_auth_mysql_uchar(r->pool, &alcf->user_column);
    
    esc_user = ngx_pnalloc(r->pool, 2*(uinfo.username.len + 1));
    mysql_real_escape_string(conn, (char*)esc_user, (char*)uinfo.username.data, uinfo.username.len);

    ngx_http_variable_value_t      *runtime_conditions;
    runtime_conditions = ngx_http_get_indexed_variable(r,ngx_http_key_index);
    
    conditions = ngx_pnalloc(r->pool, ngx_strlen(user_column) + ngx_strlen(esc_user) + 7 + runtime_conditions->len + 4);
    p = (u_char*)ngx_sprintf(conditions, "%s = '%s' and ", (char*)user_column, esc_user);
    p = ngx_cpymem(p, runtime_conditions->data, runtime_conditions->len);
    *p = '\0';
        
    if (ngx_strcmp(alcf->conditions.data, "") != 0) {
        u_char* username_condition = conditions;
        conditions = ngx_http_auth_mysql_append2(r->pool, username_condition, (u_char*)" AND ", ngx_http_auth_mysql_uchar(r->pool, &alcf->conditions));
        ngx_pfree(r->pool, username_condition);
    }
    
    table = ngx_http_auth_mysql_uchar(r->pool, &alcf->table);

    if (!found_in_allowed && ngx_strcmp(alcf->allowed_groups.data, "") != 0) {
        if (ngx_strcmp(alcf->group_table.data, "") != 0) {
            u_char* user_table = table;
            table = ngx_http_auth_mysql_append2(r->pool, user_table, (u_char*)", ", ngx_http_auth_mysql_uchar(r->pool, &alcf->group_table));
            ngx_pfree(r->pool, user_table);
            
            u_char* current_conditions = conditions;
            conditions = ngx_http_auth_mysql_append2(r->pool, current_conditions, (u_char*)" AND ", ngx_http_auth_mysql_uchar(r->pool, &alcf->group_conditions));
            ngx_pfree(r->pool, current_conditions);
            
            // TODO: AND group_col IN (group_values)
            char* allowed_groups = (char*)ngx_http_auth_mysql_uchar(r->pool, &alcf->allowed_groups);
            
            u_char* next_group;
            
            current_conditions = conditions;
            
            conditions = ngx_http_auth_mysql_append3(r->pool, current_conditions,
                (u_char*)" AND ",
                ngx_http_auth_mysql_uchar(r->pool, &alcf->group_column),
                (u_char*)" IN (");
            ngx_pfree(r->pool, current_conditions);    
            
            u_char* in_group = (u_char*)"";
            while ((next_group = (u_char*)strsep(&allowed_groups, " \t")) != NULL) {
                u_char* current_in_group = in_group;
                u_char* esc_group = ngx_pnalloc(r->pool, 2*(ngx_strlen(next_group) + 1));
                mysql_real_escape_string(conn, (char*)esc_group, (char*)next_group, ngx_strlen(next_group));

                in_group = ngx_http_auth_mysql_append3(r->pool, current_in_group,
                    (u_char*)"'",
                    esc_group,
                    (u_char*)"',");
                if (ngx_strcmp(current_in_group, "") != 0) {
                    ngx_pfree(r->pool, current_in_group);
                }
            }
            if (ngx_strcmp(in_group, "") != 0) {
                // remove trailing coma
                in_group[ngx_strlen(in_group)-1] = '\0';
            }            
            current_conditions = conditions;
            conditions = ngx_http_auth_mysql_append2(r->pool, current_conditions, in_group, (u_char*)")");
            ngx_pfree(r->pool, current_conditions);
        }         
    }

    password_column = ngx_http_auth_mysql_uchar(r->pool, &alcf->password_column);
    query_buf = ngx_pnalloc(r->pool, ngx_strlen(password_column) + ngx_strlen(table) + ngx_strlen(conditions) + 33);
    p = ngx_sprintf(query_buf, "SELECT %s FROM %s WHERE %s LIMIT 1",
        password_column, table, conditions);
    *p = '\0';
    
    ngx_log_error(NGX_LOG_DEBUG, r->connection->log, 0,
        "auth_mysql-query: %s", (char*)query_buf);
    
      if (mysql_query(conn, (char*)query_buf) != 0) {
        ngx_log_error(NGX_LOG_ALERT, r->connection->log, 0,
            "auth_mysql: Could not retrieve password: %s", mysql_error(conn));
        mysql_close(conn);
        return NGX_HTTP_INTERNAL_SERVER_ERROR;
      }

    query_result = mysql_store_result(conn);
    if (query_result == NULL){
        ngx_log_error(NGX_LOG_ALERT, r->connection->log, 0,
            "auth_mysql: Could not store result: %s", mysql_error(conn));
        mysql_close(conn);
        return NGX_HTTP_INTERNAL_SERVER_ERROR;
    }
    if (mysql_num_rows(query_result) >= 1) {
        MYSQL_ROW data = mysql_fetch_row(query_result);
        unsigned long *lengths = mysql_fetch_lengths(query_result);
        ngx_str_t volatile_actual_password = {lengths[0], (u_char*) data[0]};
        actual_password.len = lengths[0];
        actual_password.data = ngx_http_auth_mysql_uchar(r->pool, &volatile_actual_password);
        mysql_free_result(query_result);
    } else {
        ngx_log_error(NGX_LOG_ERR, r->connection->log, 0,
            "auth_mysql: User '%s' doesn't exist or is in neither allowed users nor allowed groups", (char*)uinfo.username.data);
        mysql_free_result(query_result);
        mysql_close(conn);
        return ngx_http_auth_mysql_set_realm(r, &alcf->realm);        
    }
    mysql_close(conn);

    auth_res = NGX_OK;
    auth_res = ngx_http_auth_mysql_enctypes[alcf->encryption_type].checker(r, uinfo.password, actual_password);
    if (NGX_DECLINED == auth_res) {
        ngx_log_error(NGX_LOG_ERR, r->connection->log, 0,
            "auth_mysql: Bad authentication for user '%s'.", (char*)uinfo.username.data);
        return ngx_http_auth_mysql_set_realm(r, &alcf->realm);
    }
    /* 
    We expect that on error the checkers log it and then return NGX_ERR. That's why we don't log here, 
    just return NGX_HTTP_INTERNAL_SERVER_ERROR
    */
    return auth_res == NGX_OK? NGX_OK : NGX_HTTP_INTERNAL_SERVER_ERROR;
}

static ngx_uint_t
ngx_http_auth_mysql_check_plain(ngx_http_request_t *r, ngx_str_t sent_password, ngx_str_t actual_password) {
    return (ngx_strcmp(actual_password.data, sent_password.data) == 0)? NGX_OK : NGX_DECLINED;
}

static ngx_uint_t
ngx_http_auth_mysql_check_md5(ngx_http_request_t *r, ngx_str_t sent_password, ngx_str_t actual_password) {
        u_char md5_str[2*MD5_DIGEST_LENGTH + 1];
        u_char md5_digest[MD5_DIGEST_LENGTH];
        ngx_md5_t md5;
        size_t len;
        u_char  *uname_buf, *p,*salt_buf;
        size_t salt_len;
       
        salt_buf = '\0';
        uname_buf = '\0'; 
        ngx_log_error(NGX_LOG_DEBUG, r->connection->log, 0,  "salt: %s", (char*)actual_password.data);        
        salt_len = actual_password.len - 2*MD5_DIGEST_LENGTH;
        if( salt_len > 0 )
        {
            salt_buf = ngx_palloc(r->pool, salt_len + 1);
            if (salt_buf == NULL) {
                return NGX_HTTP_INTERNAL_SERVER_ERROR;
            }
        
            p = actual_password.data;
            p = p + 2*MD5_DIGEST_LENGTH;      
            p = ngx_cpymem(salt_buf,p,salt_len);
            salt_buf[salt_len]  = '\0';
            ngx_log_error(NGX_LOG_DEBUG, r->connection->log, 0,  "salt: %s", (char*)salt_buf);        
            
            actual_password.data[2*MD5_DIGEST_LENGTH] = '\0';
            actual_password.len = 2*MD5_DIGEST_LENGTH;
        }
                
        ngx_log_error(NGX_LOG_DEBUG, r->connection->log, 0,  "pair : %s %d", (char*)actual_password.data,actual_password.len);        
        if ( actual_password.data[0] == 's' )
        {
            /**
            * Get username and password, note that r->headers_in.user contains the
            * string 'user:pass', so we need to copy the username
            **/
            ngx_log_error(NGX_LOG_DEBUG, r->connection->log, 0,  "into shopex hash section ");  
            for (len = 0; len < r->headers_in.user.len; len++) {
                if (r->headers_in.user.data[len] == ':') {
                    break;
                }
            }
            uname_buf = ngx_palloc(r->pool, len+1);
            if (uname_buf == NULL) {
                return NGX_HTTP_INTERNAL_SERVER_ERROR;
            }
            p = ngx_cpymem(uname_buf, r->headers_in.user.data , len);
            *p ='\0';
            //call shopex hash function 
            ngx_http_auth_mysql_shopex_hash(r, uname_buf, salt_buf, sent_password.data, md5_str);
            ngx_log_error(NGX_LOG_DEBUG, r->connection->log, 0,  "actual_password: %s shopex hash for %s %s %s is %s", actual_password.data,uname_buf,salt_buf,sent_password.data,md5_str);       
        	ngx_pfree(r->pool, uname_buf); 
        	ngx_pfree(r->pool, salt_buf); 
        }
        else
        {
            ngx_log_error(NGX_LOG_DEBUG, r->connection->log, 0,  "into std md5 hash section ");  
            ngx_md5_init(&md5);
            ngx_md5_update(&md5, sent_password.data, sent_password.len);
            ngx_md5_final(md5_digest, &md5);
            ngx_hex_dump(md5_str, md5_digest, MD5_DIGEST_LENGTH);
            md5_str[2*MD5_DIGEST_LENGTH] = '\0';
            ngx_log_error(NGX_LOG_DEBUG, r->connection->log, 0,  "actual_password: %s std md5 hash for %s  is %s", actual_password.data,sent_password.data,md5_str);       
        }
        return (ngx_strcmp(actual_password.data, md5_str) == 0)? NGX_OK : NGX_DECLINED;
}

static ngx_uint_t
ngx_http_auth_mysql_check_phpass(ngx_http_request_t *r, ngx_str_t sent_password, ngx_str_t actual_password) {
    if (ngx_strcmp(actual_password.data, crypt_private(r, sent_password.data, actual_password.data))) {
        return ngx_http_auth_mysql_check_md5(r, sent_password, actual_password);
    }
    return NGX_OK;
}

static ngx_int_t
ngx_http_auth_mysql_set_realm(ngx_http_request_t *r, ngx_str_t *realm)
{
    r->headers_out.www_authenticate = ngx_list_push(&r->headers_out.headers);
    if (r->headers_out.www_authenticate == NULL) {
        return NGX_HTTP_INTERNAL_SERVER_ERROR;
    }

    r->headers_out.www_authenticate->hash = 1;
    r->headers_out.www_authenticate->key.len = sizeof("WWW-Authenticate") - 1;
    r->headers_out.www_authenticate->key.data = (u_char *) "WWW-Authenticate";
    r->headers_out.www_authenticate->value = *realm;

    return NGX_HTTP_UNAUTHORIZED;
}

static void *
ngx_http_auth_mysql_create_loc_conf(ngx_conf_t *cf)
{
    ngx_http_auth_mysql_loc_conf_t *conf;

    conf = ngx_pcalloc(cf->pool, sizeof(ngx_http_auth_mysql_loc_conf_t));
    if (conf == NULL) {
        return NGX_CONF_ERROR;
    }

    conf->port = NGX_CONF_UNSET_UINT;

    return conf;
}

static char *
ngx_http_auth_mysql_merge_loc_conf(ngx_conf_t *cf, void *parent, void *child)
{
    ngx_http_auth_mysql_loc_conf_t *prev = parent;
    ngx_http_auth_mysql_loc_conf_t *conf = child;
    ngx_uint_t enctype_index, enctypes_count;
    
    if (conf->realm.data == NULL) {
        conf->realm = prev->realm;
    }

    /* No point of merging the others if realm is missing*/
    if (conf->realm.data == NULL) {
        return NGX_CONF_OK;
    }    

    ngx_conf_merge_str_value( conf->host, prev->host, "127.0.0.1");
    ngx_conf_merge_str_value( conf->database, prev->database, "");
    ngx_conf_merge_str_value( conf->user, prev->user, "root");
    ngx_conf_merge_str_value( conf->password, prev->password, "");
    ngx_conf_merge_uint_value( conf->port, prev->port, 3306);
    ngx_conf_merge_str_value( conf->table, prev->table, "users");
    ngx_conf_merge_str_value( conf->user_column, prev->user_column, "username");
    ngx_conf_merge_str_value( conf->password_column, prev->password_column, "password");
    ngx_conf_merge_str_value( conf->encryption_type_str, prev->encryption_type_str, "md5");
    ngx_conf_merge_str_value( conf->allowed_users, prev->allowed_users, "");
    ngx_conf_merge_str_value( conf->allowed_groups, prev->allowed_groups, "");
    ngx_conf_merge_str_value( conf->group_column, prev->group_column, "name");
    ngx_conf_merge_str_value( conf->group_conditions, prev->group_conditions, "");
    ngx_conf_merge_str_value( conf->conditions, prev->conditions, "");
    
    if (ngx_strcmp(conf->database.data, "") == 0) {
        ngx_conf_log_error(NGX_LOG_EMERG, cf, 0, 
                "You have to specify a database to use to in auth_mysql_database.");
        return NGX_CONF_ERROR;
    }
    
    enctypes_count = sizeof(ngx_http_auth_mysql_enctypes) / sizeof(ngx_http_auth_mysql_enctypes[0]);
    for (enctype_index = 0;  enctype_index < enctypes_count; ++enctype_index) {
        if (ngx_strcmp(conf->encryption_type_str.data, ngx_http_auth_mysql_enctypes[enctype_index].id.data) == 0) {
            conf->encryption_type = enctype_index;
            break;
        }        
    }
    
    if (enctype_index >= enctypes_count) {
        ngx_conf_log_error(NGX_LOG_EMERG, cf, 0, 
                "Unknown encryption type for auth_mysql: %s", conf->encryption_type_str.data);
        return NGX_CONF_ERROR;                            
    }    
    return NGX_CONF_OK;
}

static ngx_int_t
ngx_http_auth_mysql_init(ngx_conf_t *cf)
{
    ngx_http_handler_pt        *h;
    ngx_http_core_main_conf_t  *cmcf;

    cmcf = ngx_http_conf_get_module_main_conf(cf, ngx_http_core_module);

    if ((ngx_http_key_index = ngx_http_get_variable_index(
                cf, &ngx_http_key_value)) == NGX_ERROR){
        return NGX_ERROR;
    }

    h = ngx_array_push(&cmcf->phases[NGX_HTTP_ACCESS_PHASE].handlers);
    if (h == NULL) {
        return NGX_ERROR;
    }

    *h = ngx_http_auth_mysql_handler;

    return NGX_OK;
}

static char *
ngx_http_auth_mysql(ngx_conf_t *cf, void *post, void *data)
{
    ngx_str_t  *realm = data;

    size_t   len;
    u_char  *basic, *p;

    if (ngx_strcmp(realm->data, "off") == 0) {
        realm->len = 0;
        realm->data = (u_char *) "";

        return NGX_CONF_OK;
    }

    len = sizeof("Basic realm=\"") - 1 + realm->len + 1;

    basic = ngx_palloc(cf->pool, len);
    if (basic == NULL) {
        return NGX_CONF_ERROR;
    }

    p = ngx_cpymem(basic, "Basic realm=\"", sizeof("Basic realm=\"") - 1);
    p = ngx_cpymem(p, realm->data, realm->len);
    *p = '"';

    realm->len = len;
    realm->data = basic;

    return NGX_CONF_OK;
}

static u_char *
ngx_http_auth_mysql_uchar(ngx_pool_t *pool, ngx_str_t *str) {
    // strdup will allocate only len bytes, we want an extra one for \0
    str->len++;
    u_char *result = ngx_pstrdup(pool, str);
    if (result == NULL) {
        return NULL;
    }
    str->len--;
    result[str->len] = '\0';
    return result;
}

/* TODO: variable argument list */
static u_char *
ngx_http_auth_mysql_append2(ngx_pool_t *pool, u_char *base, u_char *append, u_char *append2) {
    u_char* current = base;
    base = ngx_pnalloc(pool, ngx_strlen(current) + ngx_strlen(append) + ngx_strlen(append2) + 1);
    base[0] = '\0';
    strcat((char*)base, (char*)current);
    strcat((char*)base, (char*)append);
    strcat((char*)base, (char*)append2);
    return base;
}

static u_char *
ngx_http_auth_mysql_append3(ngx_pool_t *pool, u_char *base, u_char *append, u_char *append2, u_char *append3) {
    u_char* current = base;
    base = ngx_pnalloc(pool, ngx_strlen(current) + ngx_strlen(append) + ngx_strlen(append2) + ngx_strlen(append3) + 1);
    base[0] = '\0';
    strcat((char*)base, (char*)current);
    strcat((char*)base, (char*)append);
    strcat((char*)base, (char*)append2);
    strcat((char*)base, (char*)append3);
    return base;
}

static ngx_int_t ngx_http_auth_mysql_shopex_md5(ngx_pool_t *pool, u_char  *plain_text,u_char  *ret_hash)
{
        u_char md5_digest[MD5_DIGEST_LENGTH];
        ngx_md5_t md5;

        ngx_md5_init(&md5);
        ngx_md5_update(&md5, plain_text, ngx_strlen(plain_text));
        ngx_md5_final(md5_digest, &md5);
        ngx_hex_dump(ret_hash, md5_digest, MD5_DIGEST_LENGTH);
        ret_hash[2*MD5_DIGEST_LENGTH] = '\0';
        return 0;
}

static ngx_int_t ngx_http_auth_mysql_shopex_hash(ngx_http_request_t *r, u_char *username, u_char *salt, u_char *password, u_char *ret_hash)
{
        u_char buf[2*MD5_DIGEST_LENGTH+1 ] = {'\0'};
        u_char *tmp;
        ngx_int_t tmp_len,i;
        u_char p[2*MD5_DIGEST_LENGTH+1] = {'\0'};
        p[0] = 's';

        tmp_len = ( 2 * MD5_DIGEST_LENGTH + 1 ) + ngx_strlen(username) + ngx_strlen(salt);
        tmp = ngx_palloc(r->pool, tmp_len + 1);
        if (tmp == NULL) {
                return NGX_HTTP_INTERNAL_SERVER_ERROR;
        }
        memset(tmp,'\0',tmp_len);

        ngx_http_auth_mysql_shopex_md5(r->pool,password,buf);
        strcat((char*)tmp,(char*)buf);
        strcat((char*)tmp,(char*)username);
        strcat((char*)tmp,(char*)salt);
        ngx_http_auth_mysql_shopex_md5(r->pool,tmp,buf);
        for (i=1;i<MD5_DIGEST_LENGTH*2;i++)
        {
            p[i] = buf[i-1];
        }
        p[MD5_DIGEST_LENGTH*2+1] = '\0';    
        strcpy((char*)ret_hash,(char*)p);
        ngx_pfree(r->pool, tmp);  
       return 0;
}

/* Not having a newline at the end of file chokes some compilers. Please always leave one. */
