<?php

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

if (! function_exists('email_validator')) {
    
    /**
     * 验证邮件地址是否正确
     * 
     * @param string $email
     * 
     * @return bool
     */
    function email_validator($email)
    {
        return preg_match('/\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,14}/', $email) && (new EmailValidator())->isValid($email, new RFCValidation());
    }
}

if (! function_exists('cc_validator')) {

    /**
     * 验证 cc or bcc 邮件是否正常
     * 
     * @param array $data
     * 
     * @return array
     */
    function cc_validator($data)
    {
        $vals = [];

        foreach ($data as $cc) {
            if (is_array($cc) && isset($cc['email']) && email_validator($cc['email'])) {
                if (isset($cc['name'])) {
                    $vals[$cc['email']] = $cc['name'];
                } else {
                    $vals[] = $cc['email'];
                }
            } elseif (!is_array($cc) && email_validator($cc['email'])) {
                $vals[] = $cc;
            }
        }

        return $vals;
    }
}

if (! function_exists('retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * @param  int  $times
     * @param  \Closure  $callback
     * @param  int|float  $sleep
     * 
     * @return mixed
     *
     * @throws \Exception
     */
    function retry($times, \Closure $callback, $sleep = 0)
    {
        beginning:
        
        try {
            return $callback();
        } catch (\Exception $ex) {
            if ($times < 1) {
                throw $ex;
            }

            $times--;

            if ($sleep) {
                usleep($sleep * 1e6);
            }

            goto beginning;
        }
    }
}

if (! function_exists('str_after')) {
    /**
     * Return the remainder of a string after a given value.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    function str_after($subject, $search)
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }
}

if (! function_exists('toUnderScore')) {
    /**
     * @param string $method
     * 
     * @return string
     */
    function toUnderScore($method)
    {
        return trim(strtolower(preg_replace("/([A-Z])/", '_' . "$1", $method)), '_');
    }
}