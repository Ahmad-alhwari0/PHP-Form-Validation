<?php

class Validator
{
    public static function email(string $email): bool
    {
        return preg_match(
            '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
            $email
        ) === 1;
    }
    public static function mobile(string $mobile): bool
    {
        return preg_match('/^[0-9]{14}$/', $mobile) === 1;
    }
    public static function name(string $name): bool
    {
        return preg_match('/^[\p{L}\p{M} ]{1,50}$/u', $name) === 1
            && trim($name) !== '';
    }
    public static function password(string $password): bool
    {
        return preg_match(
            '/\A(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\p{P}\p{S}])\S{8,}\z/u',
            $password
        ) === 1;
    }
    public static function dateOfBirth(int $day, int $month, int $year): bool
    {
        if (!checkdate($month, $day, $year)) {
            return false;
        }

        $timezone = new DateTimeZone('Asia/Amman');

        $birthDate = new DateTimeImmutable(
            sprintf('%04d-%02d-%02d', $year, $month, $day),
            $timezone
        );

        $limit = (new DateTimeImmutable('today', $timezone))
            ->modify('-16 years');

        return $birthDate < $limit;
    }
}
