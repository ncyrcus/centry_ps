<?php

function endsWith($haystack, $needle) {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

function startWith($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

function generateLinkRewrite($name) {
    $order = array("\r\n", "\n", "\r", " ", "_");
    $replace = "-";
    $newstr = str_replace($order, $replace, $name);
    return preg_replace("/[^a-zA-Z0-9-]/", "", $newstr);
}
