<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Mibao\LaravelFramework\Models\WechatUser;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    $openid = $faker->uuid;
    return [
        'appid' => 'mibao',
        'openid' => $openid,
        'password' => $openid,
        'nickname' => $faker->name,
        'sex' => (int) $faker->biasedNumberBetween($min = 0, $max = 2, $function = 'sqrt'),
        'language' => 'zh',
        'province' => $faker->state,
        'city' => $faker->city,
        'country' => '中国',
        'headimgurl' => $faker->url,
    ];
});
