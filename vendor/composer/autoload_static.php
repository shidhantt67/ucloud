<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita76fff44b182ebcd3013bd88abdc289e
{
    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'Twilio\\' => 7,
        ),
        'R' => 
        array (
            'Razorpay\\Tests\\' => 15,
            'Razorpay\\Api\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Twilio\\' => 
        array (
            0 => __DIR__ . '/..' . '/twilio/sdk/src/Twilio',
        ),
        'Razorpay\\Tests\\' => 
        array (
            0 => __DIR__ . '/..' . '/razorpay/razorpay/tests',
        ),
        'Razorpay\\Api\\' => 
        array (
            0 => __DIR__ . '/..' . '/razorpay/razorpay/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'Requests' => 
            array (
                0 => __DIR__ . '/..' . '/rmccue/requests/library',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita76fff44b182ebcd3013bd88abdc289e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita76fff44b182ebcd3013bd88abdc289e::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInita76fff44b182ebcd3013bd88abdc289e::$prefixesPsr0;
            $loader->classMap = ComposerStaticInita76fff44b182ebcd3013bd88abdc289e::$classMap;

        }, null, ClassLoader::class);
    }
}
