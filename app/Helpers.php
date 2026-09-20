<?php

use App\Services\ProposalHelper;

if (!function_exists('defined_id')) {
    function defined_id($value, $digits): string { return ProposalHelper::defined_id($value, $digits); }
}
if (!function_exists('formatDefinedId')) {
    function formatDefinedId($data) { return ProposalHelper::formatDefinedId($data); }
}
if (!function_exists('createSlug')) {
    function createSlug($string) { return ProposalHelper::createSlug($string); }
}
if (!function_exists('terbilang')) {
    function terbilang($angka) { return ProposalHelper::terbilang($angka); }
}
if (!function_exists('getFileAsBase64')) {
    function getFileAsBase64(string $filePath) { return ProposalHelper::getFileAsBase64($filePath); }
}
