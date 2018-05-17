<?php 
# Coderbyte

/*
Using the PHP language, have the function FirstFactorial(num) take the num parameter being passed and return the factorial of it (e.g. if num = 4, return (4 * 3 * 2 * 1)). For the test cases, the range will be between 1 and 18 and the input will always be an integer.

使用 PHP 语言，有 FirstFactorial(num) 函数， 传递 num 参数，返回它的阶乘（例如 if num = 4, return (4 * 3 * 2 * 1)）。 对于测试用例，范围介于1到18之间，输入的是一个整数

*/

# 我的
function FirstFactorial( $num ) {
    for( $i=$num-1; $i>=1; $i-- ) {
        $num *= $i;
    }
    return $num;
}

/*
Using the PHP language, have the function FirstReverse(str) take the str parameter being passed and return the string in reversed order. For example: if the input string is "Hello World and Coders" then your program should return the string sredoC dna dlroW olleH. 

使用 PHP 语言，有 FirstReverse(str) 函数，传一个字符串参数以相反的顺序返回字符串。例如 如果输入的字符串是 “Hello World” 那么程序应该返回字符串 dlrowW olleH

备注
PHP系统函数 strrev() 可以实现
*/

# 我的
function FirstReverse( $str ) {
    $new_str = '';
    for( $i=strlen($str)-1; $i>=0; $i-- ) {
        $new_str .= $str[ $i ];
    }
    return $new_str;
}

/*
Using the PHP language, have the function LetterChanges(str) take the str parameter being passed and modify it using the following algorithm. Replace every letter in the string with the letter following it in the alphabet (ie. c becomes d, z becomes a). Then capitalize every vowel in this new string (a, e, i, o, u) and finally return this modified string. 

使用 PHP 语言，有 LetterChanges(str) 函数，对传入的参数使用下面的算法对其进行修改。 用字母表中的字母替换字符串中的每一个字母 例如 c 变成 d ,z 变成 a 然后把 新字符串（a, e, i, o, u）中的字母大写，最后返回这个新修改的字符串
*/

# 我的
function LetterChanges( $str ) {
    $new_str = array_map( function( $s ) {
        $codes = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t'
            ,'u','v','w','x','y','z');
        if( ($i = array_search( $s, $codes ))!==false ) {
            $i = $i!=count( $codes )-1 ? $i+1 : 0;
            $s = $codes[ $i ];
        }
        $temp = array( 'a', 'e', 'i', 'o', 'u' );
        if( in_array( $s, $temp ) ) {
            $s = strtoupper( $s );
        }
        return $s;
    }, str_split( $str ));
    return join( '', $new_str );
}
