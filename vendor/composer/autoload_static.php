<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6a789dc2784aede1694fa02772b6e058
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PM\\ProminentManager\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PM\\ProminentManager\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6a789dc2784aede1694fa02772b6e058::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6a789dc2784aede1694fa02772b6e058::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit6a789dc2784aede1694fa02772b6e058::$classMap;

        }, null, ClassLoader::class);
    }
}
