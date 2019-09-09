<?php

return [
    /*
     * 默认配置，将会合并到各模块中
     */
    // 默认路由前缀
    'routePrefix' => env('LOCAL_ROUTE_PREFIX', 'do/'),
    // 微信帐号
    'wechatAccout' => env('WECHAT_ACCOUT', 'default'),
    // 微信授权域
    'wechatScope' => env('WECHAT_SCOPE', 'snsapi_userinfo'),
    // 微信本地测试用户数据
    'wechatDevUser' => [
        'openid' => 'ovN33wigB1RY49fRzxH1B1RY49fRzxH1',
        'nickname' => '微信本地开发测试帐号',
        'sex' => '1',
        'province' => '广东',
        'city' => '广州',
        'country' => '中国',
        'headimgurl' => 'http://thirdwx.qlogo.cn/mmopen/vi_32/yMAjo9GGg9D0DkpOTJ901zKS5th9ZFPG1xkwQZBLJ4TKxPWsZmQUGM6h2ZIFykicWdJlNY3gibN15HSQiaR7EgMqw/132',
    ],
];
